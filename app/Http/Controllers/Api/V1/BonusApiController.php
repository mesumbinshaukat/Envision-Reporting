<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\BonusResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiSorting;
use App\Models\Bonus;
use App\Models\Currency;
use Illuminate\Http\Request;

class BonusApiController extends BaseApiController
{
    use ApiPagination, ApiSorting;

    public function index(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view bonuses');
        }

        $query = Bonus::where('user_id', $request->user()->id)->with(['employee', 'currency']);

        $this->applySorting($query, ['id', 'date', 'amount', 'created_at'], 'created_at', 'desc');

        $bonuses = $this->applyPagination($query);

        return $this->paginated($bonuses, BonusResource::class);
    }

    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can create bonuses');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'release_type' => 'required|in:with_salary,separate',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['released'] = $request->release_type === 'separate';

        // Capture exchange rate
        $currency = Currency::find($validated['currency_id']);
        if ($currency) {
            $validated['exchange_rate_at_time'] = $currency->conversion_rate;
        }

        $bonus = Bonus::create($validated);
        $bonus->load(['employee', 'currency']);

        return $this->created(new BonusResource($bonus), 'Bonus created successfully');
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view bonuses');
        }

        $bonus = Bonus::where('user_id', $request->user()->id)->with(['employee', 'currency'])->find($id);

        if (!$bonus) {
            return $this->notFound('Bonus not found');
        }

        return $this->resource(new BonusResource($bonus));
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can update bonuses');
        }

        $bonus = Bonus::where('user_id', $request->user()->id)->find($id);

        if (!$bonus) {
            return $this->notFound('Bonus not found');
        }

        $validated = $request->validate([
            'employee_id' => 'sometimes|required|exists:employees,id',
            'currency_id' => 'sometimes|required|exists:currencies,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'release_type' => 'sometimes|required|in:with_salary,separate',
        ]);

        if (isset($validated['release_type'])) {
            $validated['released'] = $validated['release_type'] === 'separate';
        }

        $bonus->update($validated);
        $bonus->load(['employee', 'currency']);

        return $this->resource(new BonusResource($bonus), 'Bonus updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete bonuses');
        }

        $bonus = Bonus::where('user_id', $request->user()->id)->find($id);

        if (!$bonus) {
            return $this->notFound('Bonus not found');
        }

        $bonus->delete();

        return $this->success(null, 'Bonus deleted successfully');
    }
}
