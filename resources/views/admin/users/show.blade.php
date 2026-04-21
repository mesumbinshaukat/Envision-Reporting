<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">User Permissions</h2>
    </x-slot>

    <div class="max-w-4xl space-y-4">
        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-2">
            <div class="text-sm text-gray-600">User</div>
            <div class="text-lg font-semibold text-navy-900">{{ $managedUser->name }}</div>
            <div class="text-sm text-gray-700">{{ $managedUser->email }}</div>
            <div class="text-sm">
                <span class="inline-flex items-center rounded-full border border-navy-200 bg-navy-50 px-2 py-1 text-xs font-semibold text-navy-900">
                    {{ ucfirst($managedUser->role ?? 'moderator') }}
                </span>
            </div>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg p-6 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-navy-900">Assigned Features</h3>
                <p class="text-xs text-gray-600">Read/Write as currently assigned. Write implies Read.</p>
            </div>

            <div class="space-y-4">
                @foreach($groupedFeatures as $group => $features)
                    <div class="border border-navy-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-navy-900 mb-3">{{ $group }}</h4>
                        <div class="space-y-2">
                            @foreach($features as $meta)
                                <div class="flex items-center justify-between gap-4 rounded border border-gray-200 px-3 py-2">
                                    <div>
                                        <div class="text-sm font-semibold text-navy-900">{{ $meta['label'] }}</div>
                                        <div class="text-xs text-gray-600">{{ $meta['key'] }}</div>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span class="inline-flex items-center rounded-full border px-2 py-1 font-semibold {{ $meta['can_read'] ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                            Read: {{ $meta['can_read'] ? 'Yes' : 'No' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full border px-2 py-1 font-semibold {{ $meta['can_write'] ? 'border-sky-300 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                            Write: {{ $meta['can_write'] ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-4">
                <a href="{{ route('admin.users.edit', $managedUser) }}" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Edit User</a>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Back</a>
            </div>
        </div>
    </div>
</x-app-layout>

