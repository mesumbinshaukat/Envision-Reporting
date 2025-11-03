<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        $userId = auth()->id();
        $currencies = Currency::where('user_id', $userId)->orderBy('is_base', 'desc')->orderBy('code')->get();
        $baseCurrency = $currencies->where('is_base', true)->first();
        $templates = $this->currencyService->getCurrencyTemplates();
        
        return view('currencies.index', compact('currencies', 'baseCurrency', 'templates'));
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        
        $validated = $request->validate([
            'code' => 'required|string|max:10',
            'conversion_rate' => 'required|numeric|min:0.000001',
            'is_base' => 'boolean',
        ]);

        // Check if this is the first currency
        $existingCount = Currency::where('user_id', $userId)->count();
        $isFirstCurrency = $existingCount === 0;

        // Get template data
        $templates = $this->currencyService->getCurrencyTemplates();
        $template = $templates[$validated['code']] ?? null;

        if (!$template) {
            return back()->withErrors(['code' => 'Invalid currency code']);
        }

        // Check if currency already exists
        $existing = Currency::where('user_id', $userId)->where('code', $validated['code'])->first();
        if ($existing) {
            return back()->withErrors(['code' => 'Currency already exists']);
        }

        // If this is the first currency or is_base is true, make it base
        $isBase = $isFirstCurrency || ($request->has('is_base') && $request->is_base);

        DB::transaction(function () use ($userId, $validated, $template, $isBase) {
            // If setting as base, remove base from others
            if ($isBase) {
                Currency::where('user_id', $userId)->update(['is_base' => false]);
            }

            Currency::create([
                'user_id' => $userId,
                'code' => $validated['code'],
                'name' => $template['name'],
                'symbol' => $template['symbol'],
                'country' => $template['country'],
                'conversion_rate' => $isBase ? 1 : $validated['conversion_rate'],
                'is_base' => $isBase,
                'is_active' => true,
            ]);
        });

        return redirect()->route('currencies.index')->with('success', 'Currency added successfully');
    }

    public function update(Request $request, Currency $currency)
    {
        // Ensure user owns this currency
        if ($currency->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'conversion_rate' => 'required|numeric|min:0.000001',
        ]);

        // Cannot update conversion rate of base currency
        if ($currency->is_base) {
            return back()->withErrors(['conversion_rate' => 'Cannot update conversion rate of base currency']);
        }

        $currency->update([
            'conversion_rate' => $validated['conversion_rate'],
        ]);

        return redirect()->route('currencies.index')->with('success', 'Conversion rate updated successfully');
    }

    public function setBase(Currency $currency)
    {
        // Ensure user owns this currency
        if ($currency->user_id !== auth()->id()) {
            abort(403);
        }

        $this->currencyService->setBaseCurrency(auth()->id(), $currency->id);

        return redirect()->route('currencies.index')->with('success', 'Base currency updated successfully');
    }

    public function toggleActive(Currency $currency)
    {
        // Ensure user owns this currency
        if ($currency->user_id !== auth()->id()) {
            abort(403);
        }

        // Cannot deactivate base currency
        if ($currency->is_base && $currency->is_active) {
            return back()->withErrors(['error' => 'Cannot deactivate base currency']);
        }

        $currency->is_active = !$currency->is_active;
        $currency->save();

        return redirect()->route('currencies.index')->with('success', 'Currency status updated');
    }

    public function destroy(Currency $currency)
    {
        // Ensure user owns this currency
        if ($currency->user_id !== auth()->id()) {
            abort(403);
        }

        // Cannot delete base currency
        if ($currency->is_base) {
            return back()->withErrors(['error' => 'Cannot delete base currency. Set another currency as base first.']);
        }

        // Check if currency is in use
        $inUse = DB::table('invoices')->where('currency_id', $currency->id)->exists()
            || DB::table('employees')->where('currency_id', $currency->id)->exists()
            || DB::table('expenses')->where('currency_id', $currency->id)->exists()
            || DB::table('bonuses')->where('currency_id', $currency->id)->exists()
            || DB::table('salary_releases')->where('currency_id', $currency->id)->exists();

        if ($inUse) {
            return back()->withErrors(['error' => 'Cannot delete currency that is in use. Deactivate it instead.']);
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('success', 'Currency deleted successfully');
    }
}
