<?php

namespace App\Http\Controllers;

use App\Models\SalaryRelease;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Bonus;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class SalaryReleaseController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $salaryReleases = auth()->user()->salaryReleases()->with('employee')->latest()->paginate(10);
        return view('salary-releases.index', compact('salaryReleases'));
    }

    public function create()
    {
        $employees = auth()->user()->employees;
        return view('salary-releases.create', compact('employees'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        
        // Get the salary release month (current month if not specified)
        $releaseMonth = $request->month ?? date('Y-m');
        $releaseMonthEnd = date('Y-m-t', strtotime($releaseMonth . '-01'));
        
        // Get all invoices with payments up to the release month
        $invoices = $employee->invoices()
            ->with(['payments' => function($query) use ($releaseMonthEnd) {
                $query->where('payment_date', '<=', $releaseMonthEnd)
                      ->where('commission_paid', false);
            }])
            ->get();
        
        // Calculate commission based on unpaid payments received up to release month
        $commissionAmount = 0;
        $commissionDetails = [];
        
        foreach($invoices as $invoice) {
            $unpaidPayments = $invoice->payments->where('commission_paid', false);
            if($unpaidPayments->count() > 0) {
                $paidAmount = $unpaidPayments->sum('amount');
                $commissionRate = $invoice->commission_rate / 100;
                $invoiceCommission = $paidAmount * $commissionRate;
                $commissionAmount += $invoiceCommission;
                
                $commissionDetails[] = [
                    'id' => $invoice->id,
                    'client' => $invoice->client->name,
                    'paid_amount' => number_format($paidAmount, 2),
                    'commission_rate' => $invoice->commission_rate,
                    'commission' => number_format($invoiceCommission, 2),
                ];
            }
        }
        
        // Get unpaid bonuses
        $bonuses = $employee->bonuses()
            ->where('released', false)
            ->where('release_type', 'with_salary')
            ->get();
        
        $bonusAmount = $bonuses->sum('amount');
        
        $baseSalary = $employee->salary;
        $deductions = $request->deductions ?? 0;
        $totalCalculated = $baseSalary + $commissionAmount + $bonusAmount - $deductions;
        
        return response()->json([
            'base_salary' => number_format($baseSalary, 2),
            'commission_amount' => number_format($commissionAmount, 2),
            'bonus_amount' => number_format($bonusAmount, 2),
            'deductions' => number_format($deductions, 2),
            'total_calculated' => number_format($totalCalculated, 2),
            'paid_invoices' => $commissionDetails,
            'bonuses' => $bonuses->map(function($bonus) {
                return [
                    'id' => $bonus->id,
                    'description' => $bonus->description ?? 'Bonus',
                    'amount' => number_format($bonus->amount, 2),
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
        
        // Check if salary has already been released for this employee and month
        $existingRelease = SalaryRelease::where('employee_id', $validated['employee_id'])
            ->where('month', $validated['month'])
            ->first();
        
        if ($existingRelease) {
            return redirect()->back()->withErrors([
                'month' => 'Salary has already been released for this employee for ' . date('F Y', strtotime($validated['month'] . '-01'))
            ])->withInput();
        }
        
        // Get the salary release month end date
        $releaseMonthEnd = date('Y-m-t', strtotime($validated['month'] . '-01'));
        
        // Get all invoices with payments up to the release month
        $invoices = $employee->invoices()
            ->with(['payments' => function($query) use ($releaseMonthEnd) {
                $query->where('payment_date', '<=', $releaseMonthEnd)
                      ->where('commission_paid', false);
            }])
            ->get();
        
        // Calculate commission based on unpaid payments received up to release month
        $commissionAmount = 0;
        $paymentIds = [];
        
        foreach($invoices as $invoice) {
            $unpaidPayments = $invoice->payments->where('commission_paid', false);
            if($unpaidPayments->count() > 0) {
                $paidAmount = $unpaidPayments->sum('amount');
                $commissionRate = $invoice->commission_rate / 100;
                $invoiceCommission = $paidAmount * $commissionRate;
                $commissionAmount += $invoiceCommission;
                
                // Collect payment IDs to mark as commission paid
                $paymentIds = array_merge($paymentIds, $unpaidPayments->pluck('id')->toArray());
            }
        }
        
        // Get unpaid bonuses
        $bonusAmount = $employee->bonuses()
            ->where('released', false)
            ->where('release_type', 'with_salary')
            ->sum('amount');
        
        $baseSalary = $employee->salary;
        $deductions = $validated['deductions'] ?? 0;
        $totalAmount = $baseSalary + $commissionAmount + $bonusAmount - $deductions;
        
        $validated['user_id'] = auth()->id();
        $validated['base_salary'] = $baseSalary;
        $validated['commission_amount'] = $commissionAmount;
        $validated['bonus_amount'] = $bonusAmount;
        $validated['total_amount'] = $totalAmount;
        
        $salaryRelease = SalaryRelease::create($validated);
        
        // Mark payments as commission paid
        if(!empty($paymentIds)) {
            \App\Models\Payment::whereIn('id', $paymentIds)
                ->update(['commission_paid' => true]);
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
        $salaryRelease->load('employee');
        return view('salary-releases.show', compact('salaryRelease'));
    }

    public function edit(SalaryRelease $salaryRelease)
    {
        $this->authorize('update', $salaryRelease);
        $employees = auth()->user()->employees;
        return view('salary-releases.edit', compact('salaryRelease', 'employees'));
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
        $salaryRelease->load(['employee', 'user']);
        
        $pdf = Pdf::loadView('salary-releases.pdf', compact('salaryRelease'));
        return $pdf->download('salary-slip-' . $salaryRelease->id . '.pdf');
    }
}
