<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Add New Client</h2>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data" class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-navy-900 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-navy-900 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="primary_contact" class="block text-sm font-semibold text-navy-900 mb-1">Primary Contact Number</label>
                <input type="text" name="primary_contact" id="primary_contact" value="{{ old('primary_contact') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="secondary_contact" class="block text-sm font-semibold text-navy-900 mb-1">Secondary Contact Number</label>
                <input type="text" name="secondary_contact" id="secondary_contact" value="{{ old('secondary_contact') }}" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="picture" class="block text-sm font-semibold text-navy-900 mb-1">Picture</label>
                <input type="file" name="picture" id="picture" accept="image/*" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div>
                <label for="website" class="block text-sm font-semibold text-navy-900 mb-1">Website</label>
                <input type="url" name="website" id="website" value="{{ old('website') }}" placeholder="https://example.com" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Create Client</button>
                <a href="{{ route('clients.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
