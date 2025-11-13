<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Currency;
use App\Traits\HandlesCurrency;
use Illuminate\Http\Request;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class InvoiceController extends Controller
{
    use AuthorizesRequests, HandlesCurrency;
    public function index(Request $request)
    {
        $isEmployee = auth()->guard('employee')->check();
        $user = $isEmployee ? auth()->guard('employee')->user() : auth()->user();
        
        $query = Invoice::with(['client', 'employee', 'payments', 'createdByEmployee', 'currency']);
        
        // Filter based on user type
        if ($isEmployee) {
            // Employee sees only approved invoices they created or their own employee invoices
            $query->where(function($q) use ($user) {
                $q->where('created_by_employee_id', $user->id)
                  ->orWhere('employee_id', $user->employee_id);
            })->where('approval_status', '!=', 'rejected');
        } else {
            // Admin sees all invoices from their account
            $query->where('user_id', $user->id);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client', function($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%");
                })->orWhere('one_time_client_name', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $dateFrom = $request->date_from;
            $dateTo = date('Y-m-d 23:59:59', strtotime($request->date_to ?? $dateFrom));
            $query->where('created_at', '>=', $dateFrom)
                  ->where('created_at', '<=', $dateTo);
        }
        
        if ($request->has('date_to') && !$request->has('date_from')) {
            $dateTo = date('Y-m-d 23:59:59', strtotime($request->date_to));
            $query->where('created_at', '<=', $dateTo);
        }
        
        $invoices = $query->latest()->paginate(10);
        
        // Calculate total amount in base currency by converting each invoice's amount
        $allInvoices = $query->with('currency')->get();
        $totalAmount = $allInvoices->sum(function($invoice) {
            return $invoice->getAmountInBaseCurrency();
        });
        
        return view('invoices.index', compact('invoices', 'totalAmount', 'isEmployee'));
    }

    public function create()
    {
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            $userId = $employeeUser->admin_id;
            $employeeId = $employeeUser->employee_id;
            
            // Employee only sees clients assigned to them in previous invoices
            $clients = Client::where('user_id', $userId)
                ->whereHas('invoices', function($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                ->get();
        } else {
            $userId = auth()->id();
            $clients = Client::where('user_id', $userId)->get();
        }
        
        $employees = Employee::where('user_id', $userId)->get();
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        
        return view('invoices.create', compact('clients', 'employees', 'isEmployee', 'currencies', 'baseCurrency'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        // Check if it's a one-time invoice or new client creation
        $isOneTime = $request->has('is_one_time') && $request->is_one_time;
        $isNewClient = $request->client_id === 'new_client';
        
        $validated = $request->validated();
        
        $isEmployee = auth()->guard('employee')->check();
        
        // Handle new client creation
        if ($isNewClient && !$isOneTime) {
            if ($isEmployee) {
                $employeeUser = auth()->guard('employee')->user();
                $adminId = $employeeUser->admin_id;
                $createdByEmployeeId = $employeeUser->id;
            } else {
                $adminId = auth()->id();
                $createdByEmployeeId = null;
            }
            
            $client = \App\Models\Client::create([
                'user_id' => $adminId,
                'name' => $request->new_client_name,
                'created_by_employee_id' => $createdByEmployeeId,
            ]);
            $validated['client_id'] = $client->id;
        }
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            $validated['user_id'] = $employeeUser->admin_id;
            $validated['created_by_employee_id'] = $employeeUser->id;
            $validated['approval_status'] = 'pending'; // Employee invoices need approval
            
            // If employee didn't select a salesperson, automatically assign themselves
            if (empty($validated['employee_id'])) {
                $validated['employee_id'] = $employeeUser->employee_id;
            }
        } else {
            $validated['user_id'] = auth()->id();
            $validated['approval_status'] = 'approved'; // Admin invoices auto-approved
            $validated['approved_at'] = now();
            $validated['approved_by'] = auth()->id();
        }
        
        $validated['tax'] = $validated['tax'] ?? 0;
        
        // Handle paid_amount and remaining_amount based on status
        if ($validated['status'] === 'Partial Paid') {
            // Use the provided paid_amount
            $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
            $validated['remaining_amount'] = $validated['amount'] - $validated['paid_amount'];
        } elseif ($validated['status'] === 'Payment Done') {
            // Full payment
            $validated['paid_amount'] = $validated['amount'];
            $validated['remaining_amount'] = 0;
        } else {
            // Pending - no payment yet
            $validated['paid_amount'] = 0;
            $validated['remaining_amount'] = $validated['amount'];
        }
        
        $validated['is_one_time'] = $isOneTime;
        
        // Set client_id to null for one-time invoices
        if ($isOneTime) {
            $validated['client_id'] = null;
        }
        
        // Handle file uploads
        if ($request->hasFile('attachments')) {
            $attachmentPaths = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('invoices', 'public');
                $attachmentPaths[] = $path;
            }
            $validated['attachments'] = $attachmentPaths;
        }
        
        // Set payment processing fee default
        $validated['payment_processing_fee'] = $validated['payment_processing_fee'] ?? 0;
        
        // Capture exchange rate at time of creation for historical accuracy
        if (isset($validated['currency_id'])) {
            $currency = \App\Models\Currency::find($validated['currency_id']);
            if ($currency) {
                $validated['exchange_rate_at_time'] = $currency->conversion_rate;
            }
        }
        
        $invoice = Invoice::create($validated);
        
        // Handle milestones if provided
        if ($request->has('milestones') && is_array($request->milestones)) {
            foreach ($request->milestones as $index => $milestone) {
                if (!empty($milestone['amount'])) {
                    \App\Models\InvoiceMilestone::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $milestone['amount'],
                        'description' => $milestone['description'] ?? null,
                        'order' => $index,
                    ]);
                }
            }
        }
        
        // If status is "Partial Paid", create a payment record for the partial amount
        if ($validated['status'] === 'Partial Paid' && $validated['paid_amount'] > 0) {
            \App\Models\Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $validated['user_id'], // Use the already determined user_id
                'amount' => $validated['paid_amount'],
                'payment_date' => now(),
                'payment_month' => now()->format('Y-m'),
                'payment_method' => 'Initial Partial Payment',
                'notes' => 'Partial payment on invoice creation',
                'commission_paid' => false,
            ]);
        }
        
        // If status is "Payment Done", automatically create a payment record
        if ($validated['status'] === 'Payment Done') {
            Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $validated['user_id'], // Use the already determined user_id
                'amount' => $validated['amount'],
                'payment_date' => now(),
                'payment_month' => now()->format('Y-m'),
                'notes' => 'Full payment received on invoice creation',
                'commission_paid' => false,
            ]);
            
            // Update invoice paid and remaining amounts
            $invoice->update([
                'paid_amount' => $validated['amount'],
                'remaining_amount' => 0,
            ]);
        }
        
        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $invoice->user_id) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            $this->authorize('view', $invoice);
        }
        
        $invoice->load(['client', 'employee', 'currency', 'milestones', 'payments']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $invoice->user_id) {
                abort(403, 'This action is unauthorized.');
            }
            $userId = $employeeUser->admin_id;
            $employeeId = $employeeUser->employee_id;
            
            // Employee only sees clients assigned to them
            $clients = Client::where('user_id', $userId)
                ->whereHas('invoices', function($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                ->get();
        } else {
            $userId = auth()->id();
            $clients = Client::where('user_id', $userId)->get();
        }
        
        $employees = Employee::where('user_id', $userId)->get();
        $currencies = $this->getUserCurrencies();
        $baseCurrency = $this->getBaseCurrency();
        
        $invoice->load(['milestones', 'payments']);
        
        return view('invoices.edit', compact('invoice', 'clients', 'employees', 'isEmployee', 'currencies', 'baseCurrency'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        
        $validated = $request->validated();
        
        $validated['tax'] = $validated['tax'] ?? 0;
        
        // Handle file uploads
        if ($request->hasFile('attachments')) {
            $attachmentPaths = $invoice->attachments ?? [];
            
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('invoices', 'public');
                $attachmentPaths[] = $path;
            }
            $validated['attachments'] = $attachmentPaths;
        }
        
        // Set payment processing fee default
        $validated['payment_processing_fee'] = $validated['payment_processing_fee'] ?? $invoice->payment_processing_fee ?? 0;
        
        // Check if status changed to "Payment Done" and no payment exists yet
        $oldStatus = $invoice->status;
        $newStatus = $validated['status'];
        
        $invoice->update($validated);
        
        // Handle milestone updates
        if ($request->has('milestones')) {
            // Delete existing milestones
            $invoice->milestones()->delete();
            
            // Create new milestones
            foreach ($request->milestones as $index => $milestone) {
                if (!empty($milestone['amount'])) {
                    \App\Models\InvoiceMilestone::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $milestone['amount'],
                        'description' => $milestone['description'] ?? null,
                        'order' => $index,
                    ]);
                }
            }
        }
        
        // If status changed to "Payment Done" and total not yet paid
        if ($newStatus === 'Payment Done' && $oldStatus !== 'Payment Done') {
            $totalPaid = $invoice->payments()->sum('amount');
            $remainingAmount = $invoice->amount - $totalPaid;
            
            if ($remainingAmount > 0) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => auth()->id(),
                    'amount' => $remainingAmount,
                    'payment_date' => now(),
                    'payment_month' => now()->format('Y-m'),
                    'notes' => 'Remaining payment received on status update',
                    'commission_paid' => false,
                ]);
                
                // Update invoice paid and remaining amounts
                $invoice->update([
                    'paid_amount' => $invoice->amount,
                    'remaining_amount' => 0,
                ]);
            }
        }
        
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();
        
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function pdf(Invoice $invoice)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            if ($employeeUser->admin_id !== $invoice->user_id) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            $this->authorize('view', $invoice);
        }
        
        $invoice->load(['client', 'employee', 'user', 'payments', 'currency', 'milestones']);
        
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download('invoice-' . $invoice->id . '.pdf');
    }

    public function pay(Request $request, Invoice $invoice)
    {
        // Manual authorization check for both guards
        $isEmployee = auth()->guard('employee')->check();
        
        if ($isEmployee) {
            $employeeUser = auth()->guard('employee')->user();
            // Check if employee has access to this invoice
            if ($employeeUser->admin_id !== $invoice->user_id) {
                abort(403, 'This action is unauthorized.');
            }
            // Check if invoice is approved
            if ($invoice->approval_status !== 'approved') {
                abort(403, 'Cannot make payment on unapproved invoice.');
            }
        } else {
            // Admin authorization
            $this->authorize('update', $invoice);
        }
        
        // Calculate remaining amount from payments
        $totalPaid = $invoice->payments()->sum('amount');
        $remainingAmount = $invoice->amount - $totalPaid;
        
        $validated = $request->validate([
            'payment_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:' . $remainingAmount
            ],
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        
        $paymentAmount = $validated['payment_amount'];
        $paymentDate = $validated['payment_date'];
        
        // Get user_id based on guard
        if ($isEmployee) {
            $userId = $employeeUser->admin_id;
        } else {
            $userId = auth()->id();
        }
        
        // Create payment record
        Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $userId,
            'amount' => $paymentAmount,
            'payment_date' => $paymentDate,
            'payment_month' => date('Y-m', strtotime($paymentDate)),
            'notes' => $validated['notes'] ?? null,
        ]);
        
        // Recalculate totals
        $newTotalPaid = $invoice->payments()->sum('amount');
        $newRemainingAmount = $invoice->amount - $newTotalPaid;
        
        // Update invoice paid_amount and remaining_amount
        $invoice->paid_amount = $newTotalPaid;
        $invoice->remaining_amount = $newRemainingAmount;
        
        // Update status based on payment
        if ($newRemainingAmount <= 0.01) { // Account for floating point precision
            $invoice->status = 'Payment Done';
            $invoice->remaining_amount = 0;
        } else {
            $invoice->status = 'Partial Paid';
        }
        
        // Set payment date and month to latest payment
        $latestPayment = $invoice->payments()->latest('payment_date')->first();
        if ($latestPayment) {
            $invoice->payment_date = $latestPayment->payment_date;
            $invoice->payment_month = $latestPayment->payment_month;
        }
        
        $invoice->save();
        
        return redirect()->route('invoices.index')->with('success', 'Payment of Rs.' . number_format($paymentAmount, 2) . ' recorded successfully. Status: ' . $invoice->status);
    }

    public function approve(Invoice $invoice)
    {
        $invoice->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Invoice approved successfully.');
    }

    public function reject(Invoice $invoice)
    {
        $invoice->update(['approval_status' => 'rejected']);
        $invoice->delete(); // Soft delete

        return redirect()->back()->with('success', 'Invoice rejected and moved to trash.');
    }

    public function trash()
    {
        $isEmployee = auth()->guard('employee')->check();
        $user = $isEmployee ? auth()->guard('employee')->user() : auth()->user();

        $query = Invoice::onlyTrashed()->with(['client', 'employee', 'createdByEmployee']);

        if (!$isEmployee) {
            $query->where('user_id', $user->id);
        } else {
            // Employees don't see trash
            abort(403);
        }

        $invoices = $query->latest('deleted_at')->paginate(10);

        return view('invoices.trash', compact('invoices'));
    }

    public function restore($id)
    {
        $invoice = Invoice::withTrashed()->findOrFail($id);
        $invoice->restore();
        $invoice->update(['approval_status' => 'approved']);

        return redirect()->back()->with('success', 'Invoice restored successfully.');
    }

    public function forceDelete($id)
    {
        $invoice = Invoice::withTrashed()->findOrFail($id);
        $invoice->forceDelete();

        return redirect()->back()->with('success', 'Invoice permanently deleted.');
    }
}
