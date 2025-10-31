<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class InvoiceController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'employee', 'payments']);
        
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
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $invoices = $query->latest()->paginate(10);
        $totalAmount = $query->sum('amount');
        
        return view('invoices.index', compact('invoices', 'totalAmount'));
    }

    public function create()
    {
        $clients = auth()->user()->clients;
        $employees = auth()->user()->employees;
        return view('invoices.create', compact('clients', 'employees'));
    }

    public function store(Request $request)
    {
        // Check if it's a one-time invoice or new client creation
        $isOneTime = $request->has('is_one_time') && $request->is_one_time;
        $isNewClient = $request->client_id === 'new_client';
        
        $rules = [
            'employee_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:Pending,Partial Paid,Payment Done',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'special_note' => 'nullable|string',
        ];
        
        if ($isOneTime) {
            $rules['one_time_client_name'] = 'required|string|max:255';
        } elseif ($isNewClient) {
            $rules['new_client_name'] = 'required|string|max:255';
        } else {
            $rules['client_id'] = 'required|exists:clients,id';
        }
        
        $validated = $request->validate($rules);
        
        // Handle new client creation
        if ($isNewClient && !$isOneTime) {
            $client = \App\Models\Client::create([
                'user_id' => auth()->id(),
                'name' => $request->new_client_name,
            ]);
            $validated['client_id'] = $client->id;
        }
        
        $validated['user_id'] = auth()->id();
        $validated['tax'] = $validated['tax'] ?? 0;
        $validated['paid_amount'] = 0;
        $validated['remaining_amount'] = $validated['amount'];
        $validated['is_one_time'] = $isOneTime;
        
        // Set client_id to null for one-time invoices
        if ($isOneTime) {
            $validated['client_id'] = null;
        }
        
        $invoice = Invoice::create($validated);
        
        // If status is "Payment Done", automatically create a payment record
        if ($validated['status'] === 'Payment Done') {
            Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
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
        $this->authorize('view', $invoice);
        $invoice->load(['client', 'employee']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $clients = auth()->user()->clients;
        $employees = auth()->user()->employees;
        return view('invoices.edit', compact('invoice', 'clients', 'employees'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'employee_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:Pending,Partial Paid,Payment Done',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'special_note' => 'nullable|string',
        ]);
        
        $validated['tax'] = $validated['tax'] ?? 0;
        
        // Check if status changed to "Payment Done" and no payment exists yet
        $oldStatus = $invoice->status;
        $newStatus = $validated['status'];
        
        $invoice->update($validated);
        
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
        $this->authorize('view', $invoice);
        $invoice->load(['client', 'employee', 'user']);
        
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download('invoice-' . $invoice->id . '.pdf');
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        
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
        
        // Create payment record
        Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
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
}
