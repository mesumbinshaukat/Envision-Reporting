<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Allowance Types</h2>
            <div class="flex gap-2">
                <a href="{{ route('employee-allowances.index') }}" class="px-4 py-2 bg-white border border-navy-900 text-navy-900 rounded hover:bg-navy-50">Employee Allowances</a>
                <a href="{{ route('allowance-types.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add Allowance Type</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            @if($allowanceTypes->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Name</th>
                            <th class="text-left py-3 px-4">Label</th>
                            <th class="text-left py-3 px-4">Description</th>
                            <th class="text-left py-3 px-4">Status</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allowanceTypes as $type)
                            <tr class="border-b">
                                <td class="py-3 px-4 font-semibold">{{ $type->name }}</td>
                                <td class="py-3 px-4">{{ $type->label }}</td>
                                <td class="py-3 px-4">{{ $type->description ?? 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded text-sm {{ $type->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $type->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('allowance-types.edit', $type) }}" class="text-navy-900 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('allowance-types.destroy', $type) }}" class="inline" onsubmit="return confirm('Are you sure? This allowance type cannot be deleted if assigned to employees.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $allowanceTypes->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No allowance types found.</p>
                    <a href="{{ route('allowance-types.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Create your first allowance type</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
