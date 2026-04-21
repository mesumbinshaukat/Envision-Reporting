<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Users</h2>
    </x-slot>

    <div class="max-w-5xl space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">Manage moderators and supervisors.</p>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add User</a>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-navy-200">
                    <thead class="bg-navy-900 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Name</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Role</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Created</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-navy-900 font-semibold">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full border border-navy-200 bg-navy-50 px-2 py-1 text-xs font-semibold text-navy-900">
                                        {{ ucfirst($user->role ?? 'admin') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($user->created_at)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-navy-900 underline">View</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-navy-900 underline">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-600">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>

