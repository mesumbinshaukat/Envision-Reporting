<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $userId = $request->user()->tokenCan('admin') ? $request->user()->id : $request->user()->admin_id;
        
        $currencies = Currency::where('user_id', $userId)->get();

        return $this->success(CurrencyResource::collection($currencies));
    }

    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can create currencies');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code,NULL,id,user_id,' . $request->user()->id,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'conversion_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['is_base'] = false;

        $currency = Currency::create($validated);

        return $this->created(new CurrencyResource($currency), 'Currency created successfully');
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can update currencies');
        }

        $currency = Currency::where('user_id', $request->user()->id)->find($id);

        if (!$currency) {
            return $this->notFound('Currency not found');
        }

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:10|unique:currencies,code,' . $id . ',id,user_id,' . $request->user()->id,
            'name' => 'sometimes|required|string|max:255',
            'symbol' => 'sometimes|required|string|max:10',
            'conversion_rate' => 'sometimes|required|numeric|min:0',
        ]);

        $currency->update($validated);

        return $this->resource(new CurrencyResource($currency), 'Currency updated successfully');
    }

    public function setBase(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can set base currency');
        }

        $currency = Currency::where('user_id', $request->user()->id)->find($id);

        if (!$currency) {
            return $this->notFound('Currency not found');
        }

        // Unset all other base currencies
        Currency::where('user_id', $request->user()->id)->update(['is_base' => false]);

        // Set this as base
        $currency->update(['is_base' => true, 'conversion_rate' => 1.0]);

        return $this->resource(new CurrencyResource($currency), 'Base currency set successfully');
    }

    public function toggleActive(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can toggle currency status');
        }

        $currency = Currency::where('user_id', $request->user()->id)->find($id);

        if (!$currency) {
            return $this->notFound('Currency not found');
        }

        $currency->update(['is_active' => !$currency->is_active]);

        return $this->resource(new CurrencyResource($currency), 'Currency status toggled successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete currencies');
        }

        $currency = Currency::where('user_id', $request->user()->id)->find($id);

        if (!$currency) {
            return $this->notFound('Currency not found');
        }

        if ($currency->is_base) {
            return $this->error('Cannot delete base currency', 400);
        }

        $currency->delete();

        return $this->success(null, 'Currency deleted successfully');
    }
}
