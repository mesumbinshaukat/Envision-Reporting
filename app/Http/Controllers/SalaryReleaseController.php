<?php

namespace App\Http\Controllers;

use App\Models\SalaryRelease;
use App\Models\Employee;
use App\Models\Bonus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Currency;
use App\Models\EmployeeAllowance;
use App\Traits\HandlesCurrency;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class SalaryReleaseController
 * @package App\Http\Controllers
 */
class SalaryReleaseController extends Controller
{
    use AuthorizesRequests, HandlesCurrency;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = auth()->id();
        $salaryReleases = SalaryRelease::where('user_id', $userId)->with(['employee', 'currency'])->latest()->paginate(10);
        $baseCurrency = $this->getBaseCurrency();
        return view('salary-releases.index', compact('salaryReleases', 'baseCurrency'));
    }

    public function create()
    {
        $userId = auth()->id();
        $employees = Employee::where('user_id', $userId)->get();
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('salary-releases.create', compact('employees', 'currencies', 'baseCurrency'));
    }

    public function preview(Request $request, \App\Services\OfficeScheduleService $scheduleService)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'nullable|string',
            'release_date' => 'nullable|date',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        
        // Get the salary release month (current month if not specified)
        $releaseMonth = $request->month ?? date('Y-m');
        
        // Use release_date if provided, otherwise use end of salary month
        $releaseDate = $request->release_date ?? date('Y-m-t', strtotime($releaseMonth . '-01'));
        
        // Check if salary already released for this month
        $alreadyReleased = $employee->salaryReleases()
            ->where('month', $releaseMonth)
            ->exists();
        
        // If releasing salary in November, only count November payments
        $salaryMonthDate = \Carbon\Carbon::parse($releaseMonth . '-01');
        $salaryMonthEnd = $salaryMonthDate->copy()->endOfMonth();
        
        // Get all invoices where this employee is the salesperson
        // Include payments from previous month only that haven't had commission paid
        $invoices = $employee->invoices()
            ->with(['client', 'currency', 'payments' => function($query) use ($salaryMonthDate, $salaryMonthEnd) {
                $query->where('payment_date', '>=', $salaryMonthDate->toDateString())
                      ->where('payment_date', '<=', $salaryMonthEnd->toDateString())
                      ->where('commission_paid', false);
            }])
            ->get();
        
        // Get base currency for conversion
        $baseCurrency = $this->getBaseCurrency();
        
        // Calculate commission based on unpaid payments from previous month (converted to base currency)
        $commissionAmount = 0;
        $commissionDetails = [];
        
        foreach($invoices as $invoice) {
            // Only calculate if employee has commission rate
            if($employee->commission_rate && $employee->commission_rate > 0) {
                $unpaidPayments = $invoice->payments->where('commission_paid', false);
                if($unpaidPayments->count() > 0) {
                    $paidAmount = $unpaidPayments->sum('amount');
                    
                    // Convert paid amount to base currency
                    if ($invoice->currency && !$invoice->currency->is_base) {
                        $paidAmountInBase = $invoice->currency->toBase($paidAmount);
                        $taxInBase = $invoice->currency->toBase($invoice->tax);
                        $invoiceAmountInBase = $invoice->currency->toBase($invoice->amount);
                    } else {
                        $paidAmountInBase = $paidAmount;
                        $taxInBase = $invoice->tax;
                        $invoiceAmountInBase = $invoice->amount;
                    }
                    
                    // Calculate commission after tax deduction (in base currency)
                    $taxPerPayment = $invoiceAmountInBase > 0 ? ($taxInBase / $invoiceAmountInBase) * $paidAmountInBase : 0;
                    $netAmount = $paidAmountInBase - $taxPerPayment;
                    $commissionRate = $employee->commission_rate / 100;
                    $invoiceCommission = $netAmount * $commissionRate;
                    $commissionAmount += $invoiceCommission;
                    
                    $commissionDetails[] = [
                        'id' => $invoice->id,
                        'client' => $invoice->client ? $invoice->client->name : 'N/A',
                        'currency' => $invoice->currency ? $invoice->currency->symbol : 'Rs.',
                        'paid_amount' => number_format($paidAmount, 2),
                        'paid_amount_formatted' => ($invoice->currency ? $invoice->currency->symbol : 'Rs.') . number_format($paidAmount, 2),
                        'paid_amount_base' => number_format($paidAmountInBase, 2),
                        'commission_rate' => $employee->commission_rate,
                        'commission' => number_format($invoiceCommission, 2),
                    ];
                }
            }
        }
        
        // Get unpaid bonuses (convert to base currency)
        $bonuses = $employee->bonuses()
            ->where('released', false)
            ->where('release_type', 'with_salary')
            ->with('currency')
            ->get();
        
        $bonusAmount = $bonuses->sum(function($bonus) {
            return $bonus->getAmountInBaseCurrency();
        });
        
        // Get active employee allowances (convert to base currency)
        $allowances = $employee->employeeAllowances()
            ->where('is_active', true)
            ->with(['allowanceType', 'currency'])
            ->get();
        
        $allowanceAmount = $allowances->sum(function($allowance) {
            return $allowance->getAmountInBaseCurrency();
        });
        
        $baseSalary = $employee->salary;
        
        // Calculate Advanced Deductions (Late & Leave)
        $globalSchedule = $scheduleService->getSchedule($employee->user);
        $divisor = $globalSchedule->salary_divisor ?? 30;
        $oneDaySalary = $baseSalary / $divisor;
        
        // Late Deduction
        $lateCountForDeduction = $globalSchedule->late_count_for_deduction ?? 3;
        $latesCount = \App\Models\Attendance::forEmployee($employee->employeeUser->id ?? 0)
            ->where('attendance_date', '>=', $salaryMonthDate->toDateString())
            ->where('attendance_date', '<=', $salaryMonthEnd->toDateString())
            ->where('is_late', true)
            ->count();
        $lateDeduction = floor($latesCount / $lateCountForDeduction) * $oneDaySalary;
        
        // Leave Deduction
        $expectedWorkingDays = $scheduleService->countExpectedWorkingDays($employee, $salaryMonthDate, $salaryMonthEnd);
        $actualPresentDays = \App\Models\Attendance::forEmployee($employee->employeeUser->id ?? 0)
            ->where('attendance_date', '>=', $salaryMonthDate->toDateString())
            ->where('attendance_date', '<=', $salaryMonthEnd->toDateString())
            ->count();
        $leavesTaken = max(0, $expectedWorkingDays - $actualPresentDays);
        $maxLeaves = $employee->max_monthly_leaves ?? 0;
        $extraLeaves = max(0, $leavesTaken - $maxLeaves);
        $leaveDeduction = $extraLeaves * $oneDaySalary;

        $deductions = $request->deductions ?? 0;
        $totalCalculated = $baseSalary + $commissionAmount + $bonusAmount + $allowanceAmount - $deductions - $lateDeduction - $leaveDeduction;
        
        // Get employee currency or base currency
        $currency = $employee->currency ?? $this->getBaseCurrency();
        $currencySymbol = $currency ? $currency->symbol : 'Rs.';
        
        return response()->json([
            'base_salary' => number_format($baseSalary, 2),
            'commission_amount' => number_format($commissionAmount, 2),
            'bonus_amount' => number_format($bonusAmount, 2),
            'allowance_amount' => number_format($allowanceAmount, 2),
            'deductions' => number_format($deductions, 2),
            'late_deduction' => number_format($lateDeduction, 2),
            'leave_deduction' => number_format($leaveDeduction, 2),
            'total_calculated' => number_format($totalCalculated, 2),
            'currency_symbol' => $currencySymbol,
            'already_released' => $alreadyReleased,
            'paid_invoices' => $commissionDetails,
            'bonuses' => $bonuses->map(function($bonus) use ($baseCurrency) {
                $bonusAmountInBase = $bonus->getAmountInBaseCurrency();
                $bonusCurrency = $bonus->currency ?? $baseCurrency;
                
                return [
                    'id' => $bonus->id,
                    'description' => $bonus->description ?? 'Bonus',
                    'currency' => $bonusCurrency->symbol,
                    'amount' => number_format($bonus->amount, 2),
                    'amount_formatted' => $bonusCurrency->symbol . number_format($bonus->amount, 2),
                    'amount_base' => number_format($bonusAmountInBase, 2),
                    'amount_base_formatted' => $baseCurrency->symbol . number_format($bonusAmountInBase, 2),
                ];
            }),
            'allowances' => $allowances->map(function($allowance) use ($baseCurrency) {
                $allowanceAmountInBase = $allowance->getAmountInBaseCurrency();
                $allowanceCurrency = $allowance->currency ?? $baseCurrency;
                
                return [
                    'id' => $allowance->id,
                    'type_label' => $allowance->allowanceType->label,
                    'currency' => $allowanceCurrency->symbol,
                    'amount' => number_format($allowance->amount, 2),
                    'amount_formatted' => $allowanceCurrency->symbol . number_format($allowance->amount, 2),
                    'amount_base' => number_format($allowanceAmountInBase, 2),
                    'amount_base_formatted' => $baseCurrency->symbol . number_format($allowanceAmountInBase, 2),
                ];
            }),
        ]);
    }

    public function store(Request $request, \App\Services\OfficeScheduleService $scheduleService)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|string',
            'release_date' => 'required|date',
            'deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $employee = Employee::findOrFail($validated['employee_id']);
        
        // Validate that release_date is not before the salary month
        $salaryMonthStart = \Carbon\Carbon::parse($validated['month'] . '-01');
        if ($validated['release_date'] < $salaryMonthStart->toDateString()) {
            return redirect()->back()->withErrors([
                'release_date' => 'Release date cannot be before the salary month (' . $salaryMonthStart->format('F Y') . ')'
            ])->withInput();
        }
        
        // Check if salary has already been released for this employee and month
        $existingRelease = SalaryRelease::where('employee_id', $validated['employee_id'])
            ->where('month', $validated['month'])
            ->first();
        
        if ($existingRelease) {
            return redirect()->back()->withErrors([
                'month' => 'Salary has already been released for this employee for ' . $salaryMonthStart->format('F Y')
            ])->withInput();
        }
        
        // If releasing salary for a month, only count payments in that month
        $salaryMonthEnd = $salaryMonthStart->copy()->endOfMonth();
        
        // Get all invoices with payments from salary month only
        $invoices = $employee->invoices()
            ->with(['currency', 'payments' => function($query) use ($salaryMonthStart, $salaryMonthEnd) {
                $query->where('payment_date', '>=', $salaryMonthStart->toDateString())
                      ->where('payment_date', '<=', $salaryMonthEnd->toDateString())
                      ->where('commission_paid', false);
            }])
            ->get();
        
        // Calculate commission based on unpaid payments from salary month (converted to base currency)
        $commissionAmount = 0;
        $paymentIds = [];
        
        foreach($invoices as $invoice) {
            // Only calculate if employee has commission rate
            if($employee->commission_rate && $employee->commission_rate > 0) {
                $unpaidPayments = $invoice->payments->where('commission_paid', false);
                if($unpaidPayments->count() > 0) {
                    $paidAmount = $unpaidPayments->sum('amount');
                    
                    // Convert paid amount to base currency
                    if ($invoice->currency && !$invoice->currency->is_base) {
                        $paidAmountInBase = $invoice->currency->toBase($paidAmount);
                        $taxInBase = $invoice->currency->toBase($invoice->tax);
                        $invoiceAmountInBase = $invoice->currency->toBase($invoice->amount);
                    } else {
                        $paidAmountInBase = $paidAmount;
                        $taxInBase = $invoice->tax;
                        $invoiceAmountInBase = $invoice->amount;
                    }
                    
                    // Calculate commission after tax deduction (in base currency)
                    $taxPerPayment = $invoiceAmountInBase > 0 ? ($taxInBase / $invoiceAmountInBase) * $paidAmountInBase : 0;
                    $netAmount = $paidAmountInBase - $taxPerPayment;
                    $commissionRate = $employee->commission_rate / 100;
                    $invoiceCommission = $netAmount * $commissionRate;
                    $commissionAmount += $invoiceCommission;
                    
                    // Collect payment IDs to mark as commission paid
                    $paymentIds = array_merge($paymentIds, $unpaidPayments->pluck('id')->toArray());
                }
            }
        }
        
        // Get unpaid bonuses (convert to base currency)
        $bonuses = $employee->bonuses()
            ->where('released', false)
            ->where('release_type', 'with_salary')
            ->with('currency')
            ->get();
        
        $bonusAmount = $bonuses->sum(function($bonus) {
            return $bonus->getAmountInBaseCurrency();
        });
        
        // Get active employee allowances (convert to base currency)
        $allowances = $employee->employeeAllowances()
            ->where('is_active', true)
            ->with(['allowanceType', 'currency'])
            ->get();
        
        $allowanceAmount = $allowances->sum(function($allowance) {
            return $allowance->getAmountInBaseCurrency();
        });
        
        $baseSalary = $employee->salary;

        // Calculate Advanced Deductions (Late & Leave)
        $globalSchedule = $scheduleService->getSchedule($employee->user);
        $divisor = $globalSchedule->salary_divisor ?? 30;
        $oneDaySalary = $baseSalary / $divisor;
        
        // Late Deduction
        $lateCountForDeduction = $globalSchedule->late_count_for_deduction ?? 3;
        $latesCount = \App\Models\Attendance::forEmployee($employee->employeeUser->id ?? 0)
            ->where('attendance_date', '>=', $salaryMonthStart->toDateString())
            ->where('attendance_date', '<=', $salaryMonthEnd->toDateString())
            ->where('is_late', true)
            ->count();
        $lateDeduction = floor($latesCount / $lateCountForDeduction) * $oneDaySalary;
        
        // Leave Deduction
        $expectedWorkingDays = $scheduleService->countExpectedWorkingDays($employee, $salaryMonthStart, $salaryMonthEnd);
        $actualPresentDays = \App\Models\Attendance::forEmployee($employee->employeeUser->id ?? 0)
            ->where('attendance_date', '>=', $salaryMonthStart->toDateString())
            ->where('attendance_date', '<=', $salaryMonthEnd->toDateString())
            ->count();
        $leavesTaken = max(0, $expectedWorkingDays - $actualPresentDays);
        $maxLeaves = $employee->max_monthly_leaves ?? 0;
        $extraLeaves = max(0, $leavesTaken - $maxLeaves);
        $leaveDeduction = $extraLeaves * $oneDaySalary;

        $deductions = $validated['deductions'] ?? 0;
        $totalAmount = $baseSalary + $commissionAmount + $bonusAmount + $allowanceAmount - $deductions - $lateDeduction - $leaveDeduction;
        
        $validated['user_id'] = auth()->id();
        $validated['currency_id'] = $employee->currency_id ?? $this->getBaseCurrency()->id;
        $validated['base_salary'] = $baseSalary;
        $validated['commission_amount'] = $commissionAmount;
        $validated['bonus_amount'] = $bonusAmount;
        $validated['allowance_amount'] = $allowanceAmount;
        $validated['late_deduction'] = $lateDeduction;
        $validated['leave_deduction'] = $leaveDeduction;
        $validated['total_amount'] = $totalAmount;
        
        // Capture exchange rate at time of creation for historical accuracy
        if (isset($validated['currency_id'])) {
            $currency = \App\Models\Currency::find($validated['currency_id']);
            if ($currency) {
                $validated['exchange_rate_at_time'] = $currency->conversion_rate;
            }
        }
        
        $salaryRelease = SalaryRelease::create($validated);
        
        // Mark payments as commission paid and link to this salary release
        if(!empty($paymentIds)) {
            \App\Models\Payment::whereIn('id', $paymentIds)
                ->update([
                    'commission_paid' => true,
                    'salary_release_id' => $salaryRelease->id
                ]);
        }
        
        // Mark bonuses as released
        $employee->bonuses()
            ->where('released', false)
            ->where('release_type', 'with_salary')
            ->update(['released' => true]);
        
        return redirect()->route('salary-releases.index')->with('success', 'Salary released successfully.');
    }

    public function show(SalaryRelease $salaryRelease)
    {
        $this->authorize('view', $salaryRelease);
        $salaryRelease->load(['employee', 'currency']);
        return view('salary-releases.show', compact('salaryRelease'));
    }

    public function edit(SalaryRelease $salaryRelease)
    {
        $this->authorize('update', $salaryRelease);
        $userId = auth()->id();
        $employees = Employee::where('user_id', $userId)->get();
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        return view('salary-releases.edit', compact('salaryRelease', 'employees', 'currencies', 'baseCurrency'));
    }

    public function update(Request $request, SalaryRelease $salaryRelease)
    {
        $this->authorize('update', $salaryRelease);
        
        $validated = $request->validate([
            'release_date' => 'required|date',
            'deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $deductions = $validated['deductions'] ?? 0;
        $validated['total_amount'] = $salaryRelease->base_salary + $salaryRelease->commission_amount + $salaryRelease->bonus_amount + $salaryRelease->allowance_amount - $deductions - $salaryRelease->late_deduction - $salaryRelease->leave_deduction;
        
        $salaryRelease->update($validated);
        
        return redirect()->route('salary-releases.index')->with('success', 'Salary release updated successfully.');
    }

    public function destroy(SalaryRelease $salaryRelease)
    {
        $this->authorize('delete', $salaryRelease);
        $salaryRelease->delete();
        
        return redirect()->route('salary-releases.index')->with('success', 'Salary release deleted successfully.');
    }

    public function pdf(SalaryRelease $salaryRelease)
    {
        $this->authorize('view', $salaryRelease);
        $salaryRelease->load(['employee', 'user', 'currency']);
        
        // Load active allowances for this employee at the time of salary release
        $employeeAllowances = $salaryRelease->employee->employeeAllowances()
            ->where('is_active', true)
            ->with(['allowanceType', 'currency'])
            ->get();
        
        $pdf = Pdf::loadView('salary-releases.pdf', compact('salaryRelease', 'employeeAllowances'));
        return $pdf->download('salary-slip-' . $salaryRelease->id . '.pdf');
    }
}
