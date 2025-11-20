<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\InvoiceResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiFiltering;
use App\Http\Traits\ApiSorting;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceApiController extends BaseApiController
{
    use ApiPagination, ApiFiltering, ApiSorting;

    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->tokenCan('admin')) {
            $query = Invoice::where('user_id', $user->id);
        } else {
            // Employee sees invoices they created or are assigned to
            $query = Invoice::where(function($q) use ($user) {
                $q->where('created_by_employee_id', $user->id)
                  ->orWhere('employee_id', $user->employee_id);
            })->where('user_id', $user->admin_id);
        }

        $query->with(['client', 'employee', 'currency', 'payments']);

        // Apply filters
        $this->applyFilters($query, [
            'status' => '=',
            'approval_status' => '=',
            'client_id' => '=',
            'employee_id' => '=',
            'is_one_time' => '=',
        ]);

        // Date range filters
        if ($request->has('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }
        if ($request->has('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        // Apply sorting
        $this->applySorting($query, [
            'id', 'due_date', 'amount', 'status', 'approval_status', 'created_at'
        ], 'created_at', 'desc');

        $invoices = $this->applyPagination($query);

        return $this->paginated($invoices, InvoiceResource::class);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $userId = $user->tokenCan('admin') ? $user->id : $user->admin_id;

        $validated = $request->validate([
            'client_id' => 'required_without:is_one_time|nullable|exists:clients,id',
            'employee_id' => 'nullable|exists:employees,id',
            'currency_id' => 'required|exists:currencies,id',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'special_note' => 'nullable|string',
            'is_one_time' => 'boolean',
            'one_time_client_name' => 'required_if:is_one_time,true|nullable|string|max:255',
            'milestones' => 'nullable|array',
            'milestones.*.title' => 'required|string|max:255',
            'milestones.*.amount' => 'required|numeric|min:0',
            'milestones.*.due_date' => 'required|date',
        ]);

        $validated['user_id'] = $userId;
        $validated['status'] = 'pending';
        $validated['approval_status'] = $user->tokenCan('employee') ? 'pending' : 'approved';
        $validated['paid_amount'] = 0;
        $validated['remaining_amount'] = $validated['amount'];

        // Capture exchange rate
        $currency = Currency::find($validated['currency_id']);
        if ($currency) {
            $validated['exchange_rate_at_time'] = $currency->conversion_rate;
        }

        if ($user->tokenCan('employee')) {
            $validated['created_by_employee_id'] = $user->id;
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::create($validated);

            // Create milestones if provided
            if (!empty($validated['milestones'])) {
                foreach ($validated['milestones'] as $index => $milestone) {
                    $invoice->milestones()->create([
                        'title' => $milestone['title'],
                        'amount' => $milestone['amount'],
                        'due_date' => $milestone['due_date'],
                        'status' => 'pending',
                        'order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            $invoice->load(['client', 'employee', 'currency', 'milestones']);

            return $this->created(new InvoiceResource($invoice), 'Invoice created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create invoice: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $query = Invoice::with(['client', 'employee', 'currency', 'payments', 'milestones']);

        if ($user->tokenCan('admin')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where(function($q) use ($user) {
                $q->where('created_by_employee_id', $user->id)
                  ->orWhere('employee_id', $user->employee_id);
            })->where('user_id', $user->admin_id);
        }

        $invoice = $query->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        return $this->resource(new InvoiceResource($invoice));
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $query = Invoice::query();

        if ($user->tokenCan('admin')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('created_by_employee_id', $user->id)
                  ->where('user_id', $user->admin_id);
        }

        $invoice = $query->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|nullable|exists:clients,id',
            'employee_id' => 'sometimes|nullable|exists:employees,id',
            'currency_id' => 'sometimes|required|exists:currencies,id',
            'due_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'special_note' => 'nullable|string',
        ]);

        $invoice->update($validated);
        $invoice->load(['client', 'employee', 'currency', 'payments', 'milestones']);

        return $this->resource(new InvoiceResource($invoice), 'Invoice updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete invoices');
        }

        $invoice = Invoice::where('user_id', $request->user()->id)->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        $invoice->delete();

        return $this->success(null, 'Invoice deleted successfully');
    }

    public function approve(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can approve invoices');
        }

        $invoice = Invoice::where('user_id', $request->user()->id)->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        $invoice->update([
            'approval_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return $this->resource(new InvoiceResource($invoice), 'Invoice approved successfully');
    }

    public function reject(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can reject invoices');
        }

        $invoice = Invoice::where('user_id', $request->user()->id)->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        $invoice->update([
            'approval_status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return $this->resource(new InvoiceResource($invoice), 'Invoice rejected');
    }

    public function pdf(Request $request, $id)
    {
        $user = $request->user();
        
        $query = Invoice::with(['client', 'employee', 'currency', 'payments', 'milestones']);

        if ($user->tokenCan('admin')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where(function($q) use ($user) {
                $q->where('created_by_employee_id', $user->id)
                  ->orWhere('employee_id', $user->employee_id);
            })->where('user_id', $user->admin_id);
        }

        $invoice = $query->find($id);

        if (!$invoice) {
            return $this->notFound('Invoice not found');
        }

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'invoice-' . $invoice->id . '.pdf');
    }
}
