<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Add Allowance Type</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('allowance-types.store') }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-navy-900 mb-1">Name (Machine Name) *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required 
                    class="w-full px-4 py-2 border border-navy-900 rounded"
                    placeholder="e.g., petrol, housing, medical">
                <p class="text-xs text-gray-500 mt-1">Unique identifier, lowercase, no spaces (used in code)</p>
            </div>

            <div>
                <label for="label" class="block text-sm font-semibold text-navy-900 mb-1">Label (Display Name) *</label>
                <input type="text" name="label" id="label" value="{{ old('label') }}" required 
                    class="w-full px-4 py-2 border border-navy-900 rounded"
                    placeholder="e.g., Petrol Allowance, Housing Allowance">
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-navy-900 mb-1">Description</label>
                <textarea name="description" id="description" rows="3" 
                    class="w-full px-4 py-2 border border-navy-900 rounded">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked 
                    class="w-4 h-4 border-navy-900 rounded">
                <label for="is_active" class="ml-2 text-sm font-semibold text-navy-900">Active</label>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Allowance Type</button>
                <a href="{{ route('allowance-types.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
