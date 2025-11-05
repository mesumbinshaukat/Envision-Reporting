<?php

namespace App\Http\Controllers;

use App\Models\SalaryRelease;
use App\Models\Employee;
use App\Models\Bonus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Currency;
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

    public function preview(Request $request)
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
        $salaryMonthDate = date('Y-m-01', strtotime($releaseMonth . '-01'));
        $salaryMonthEnd = date('Y-m-t', strtotime($salaryMonthDate));
        
        // Get all invoices where this employee is the salesperson
        // Include payments from previous month only that haven't had commission paid
        $invoices = $employee->invoices()
            ->with(['client', 'currency', 'payments' => function($query) use ($salaryMonthDate, $salaryMonthEnd) {
                $query->where('payment_date', '>=', $salaryMonthDate)
                      ->where('payment_date', '<=', $salaryMonthEnd)
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
                    $taxPerPayment = ($taxInBase / $invoiceAmountInBase) * $paidAmountInBase;
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
        
        $baseSalary = $employee->salary;
        $deductions = $request->deductions ?? 0;
        $totalCalculated = $baseSalary + $commissionAmount + $bonusAmount - $deductions;
        
        // Get employee currency or base currency
        $currency = $employee->currency ?? $this->getBaseCurrency();
        $currencySymbol = $currency ? $currency->symbol : 'Rs.';
        
        return response()->json([
            'base_salary' => number_format($baseSalary, 2),
            'commission_amount' => number_format($commissionAmount, 2),
            'bonus_amount' => number_format($bonusAmount, 2),
            'deductions' => number_format($deductions, 2),
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
        ]);
    }

    public function store(Request $request)
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
        $salaryMonthStart = date('Y-m-01', strtotime($validated['month'] . '-01'));
        if ($validated['release_date'] < $salaryMonthStart) {
            return redirect()->back()->withErrors([
                'release_date' => 'Release date cannot be before the salary month (' . date('F Y', strtotime($validated['month'] . '-01')) . ')'
            ])->withInput();
        }
        
        // Check if salary has already been released for this employee and month
        $existingRelease = SalaryRelease::where('employee_id', $validated['employee_id'])
            ->where('month', $validated['month'])
            ->first();
        
        if ($existingRelease) {
            return redirect()->back()->withErrors([
                'month' => 'Salary has already been released for this employee for ' . date('F Y', strtotime($validated['month'] . '-01'))
            ])->withInput();
        }
        
        // If releasing salary in November, only count November payments
        $salaryMonthDate = date('Y-m-01', strtotime($validated['month'] . '-01'));
        $salaryMonthEnd = date('Y-m-t', strtotime($salaryMonthDate));
        
        // Get all invoices with payments from salary month only
        $invoices = $employee->invoices()
            ->with(['currency', 'payments' => function($query) use ($salaryMonthDate, $salaryMonthEnd) {
                $query->where('payment_date', '>=', $salaryMonthDate)
                      ->where('payment_date', '<=', $salaryMonthEnd)
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
                    $taxPerPayment = ($taxInBase / $invoiceAmountInBase) * $paidAmountInBase;
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
        
        $baseSalary = $employee->salary;
        $deductions = $validated['deductions'] ?? 0;
        $totalAmount = $baseSalary + $commissionAmount + $bonusAmount - $deductions;
        
        $validated['user_id'] = auth()->id();
        $validated['currency_id'] = $employee->currency_id ?? $this->getBaseCurrency()->id;
        $validated['base_salary'] = $baseSalary;
        $validated['commission_amount'] = $commissionAmount;
        $validated['bonus_amount'] = $bonusAmount;
        $validated['total_amount'] = $totalAmount;
        
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
        $validated['total_amount'] = $salaryRelease->base_salary + $salaryRelease->commission_amount + $salaryRelease->bonus_amount - $deductions;
        
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
        
        $pdf = Pdf::loadView('salary-releases.pdf', compact('salaryRelease'));
        return $pdf->download('salary-slip-' . $salaryRelease->id . '.pdf');
    }
}
