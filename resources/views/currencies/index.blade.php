<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Currency Management</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Base Currency Info -->
        @if($baseCurrency)
            <div class="bg-blue-50 border-2 border-blue-500 rounded-lg p-6">
                <h3 class="text-lg font-bold text-navy-900 mb-2">Base Currency</h3>
                <div class="flex items-center gap-4">
                    <div class="text-3xl">{{ $baseCurrency->symbol }}</div>
                    <div>
                        <div class="font-bold text-xl">{{ $baseCurrency->code }} - {{ $baseCurrency->name }}</div>
                        <div class="text-sm text-gray-600">{{ $baseCurrency->country }}</div>
                        <div class="text-xs text-gray-500 mt-1">All other currencies are converted relative to this base currency</div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-yellow-50 border-2 border-yellow-500 rounded-lg p-6">
                <h3 class="text-lg font-bold text-navy-900 mb-2">⚠️ No Base Currency Set</h3>
                <p class="text-sm">Please add your first currency below. It will automatically become your base currency.</p>
            </div>
        @endif

        <!-- Add New Currency -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Add New Currency</h3>
            <form method="POST" action="{{ route('currencies.store') }}" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-semibold text-navy-900 mb-1">Currency *</label>
                        <select name="code" id="code" required class="w-full px-4 py-2 border border-navy-900 rounded" onchange="updateCurrencyInfo()">
                            <option value="">Select Currency...</option>
                            @foreach($templates as $code => $template)
                                <option value="{{ $code }}" data-symbol="{{ $template['symbol'] }}" data-name="{{ $template['name'] }}" data-country="{{ $template['country'] }}">
                                    {{ $code }} - {{ $template['name'] }} ({{ $template['symbol'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="conversion_rate_field" style="display: {{ $baseCurrency ? 'block' : 'none' }};">
                        <label for="conversion_rate" class="block text-sm font-semibold text-navy-900 mb-1">
                            Conversion Rate *
                            <span class="text-xs text-gray-600" id="conversion_label">
                                @if($baseCurrency)
                                    (1 <span id="selected_code">XXX</span> = ? {{ $baseCurrency->code }})
                                @endif
                            </span>
                        </label>
                        <input type="number" name="conversion_rate" id="conversion_rate" step="0.000001" min="0.000001" value="1" class="w-full px-4 py-2 border border-navy-900 rounded" {{ $baseCurrency ? 'required' : '' }}>
                        <p class="text-xs text-gray-600 mt-1">Enter how much of your base currency equals 1 unit of the selected currency</p>
                    </div>
                </div>

                @if($baseCurrency)
                    <div class="flex items-center">
                        <input type="checkbox" name="is_base" id="is_base" value="1" class="mr-2">
                        <label for="is_base" class="text-sm text-navy-900">Set as new base currency (will recalculate all rates)</label>
                    </div>
                @endif

                <div>
                    <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add Currency</button>
                </div>
            </form>
        </div>

        <!-- Currency List -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-navy-900 mb-4">Your Currencies</h3>
            
            @if($currencies->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-navy-900">
                                <th class="text-left py-3 px-4">Currency</th>
                                <th class="text-left py-3 px-4">Symbol</th>
                                <th class="text-left py-3 px-4">Country</th>
                                <th class="text-left py-3 px-4">Conversion Rate</th>
                                <th class="text-left py-3 px-4">Status</th>
                                <th class="text-left py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currencies as $currency)
                                <tr class="border-b border-gray-200 {{ $currency->is_base ? 'bg-blue-50' : '' }}">
                                    <td class="py-3 px-4">
                                        <div class="font-bold">{{ $currency->code }}</div>
                                        <div class="text-sm text-gray-600">{{ $currency->name }}</div>
                                        @if($currency->is_base)
                                            <span class="text-xs bg-blue-500 text-white px-2 py-1 rounded">BASE</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-2xl">{{ $currency->symbol }}</td>
                                    <td class="py-3 px-4">{{ $currency->country }}</td>
                                    <td class="py-3 px-4">
                                        @if($currency->is_base)
                                            <span class="text-gray-500">1.000000 (Base)</span>
                                        @else
                                            <form method="POST" action="{{ route('currencies.update', $currency) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="conversion_rate" value="{{ $currency->conversion_rate }}" step="0.000001" min="0.000001" class="w-32 px-2 py-1 border border-gray-300 rounded text-sm">
                                                <button type="submit" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">Update</button>
                                            </form>
                                            <div class="text-xs text-gray-600 mt-1">1 {{ $currency->code }} = {{ $currency->conversion_rate }} {{ $baseCurrency->code }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs {{ $currency->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $currency->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex gap-2">
                                            @if(!$currency->is_base)
                                                <form method="POST" action="{{ route('currencies.set-base', $currency) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700" onclick="return confirm('Set this as base currency? All conversion rates will be recalculated.')">Set as Base</button>
                                                </form>
                                            @endif
                                            
                                            <form method="POST" action="{{ route('currencies.toggle-active', $currency) }}">
                                                @csrf
                                                <button type="submit" class="text-xs bg-yellow-600 text-white px-2 py-1 rounded hover:bg-yellow-700">
                                                    {{ $currency->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            
                                            @if(!$currency->is_base)
                                                <form method="POST" action="{{ route('currencies.destroy', $currency) }}" onsubmit="return confirm('Delete this currency? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600">No currencies added yet. Add your first currency above.</p>
            @endif
        </div>
    </div>

    <script>
        function updateCurrencyInfo() {
            const select = document.getElementById('code');
            const selectedOption = select.options[select.selectedIndex];
            const code = selectedOption.value;
            
            if (code) {
                document.getElementById('selected_code').textContent = code;
            }
        }
    </script>
</x-app-layout>
