<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Edit User</h2>
    </x-slot>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="bg-white border border-navy-900 rounded-lg p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-navy-900 mb-1">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $managedUser->name) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-navy-900 mb-1">Email *</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $managedUser->email) }}" required class="w-full px-4 py-2 border border-navy-900 rounded">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-sm font-semibold text-navy-900 mb-1">Role *</label>
                    <select name="role" id="role" required class="w-full px-4 py-2 border border-navy-900 rounded">
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" {{ old('role', $managedUser->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-navy-900 mb-1">New Password</label>
                    <input type="password" name="password" id="password" minlength="8" class="w-full px-4 py-2 border border-navy-900 rounded">
                    <p class="text-xs text-gray-600 mt-1">Leave blank to keep existing password.</p>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-navy-900 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" minlength="8" class="w-full px-4 py-2 border border-navy-900 rounded">
            </div>

            <div class="border-t border-gray-300 pt-4 space-y-3">
                <div>
                    <h3 class="text-sm font-semibold text-navy-900">Feature Permissions</h3>
                    <p class="text-xs text-gray-600">Update exactly what this user can access. “Write” implies “Read”.</p>
                </div>

                @if($errors->has('permissions'))
                    <p class="text-xs text-red-600">{{ $errors->first('permissions') }}</p>
                @endif

                <div class="space-y-4">
                    @foreach($groupedFeatures as $group => $features)
                        <div class="border border-navy-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-navy-900">{{ $group }}</h4>
                                <div class="flex items-center gap-3 text-xs text-gray-600">
                                    <button type="button" class="underline text-navy-900" onclick="toggleGroup('{{ \Illuminate\Support\Str::slug($group) }}', true)">Select all</button>
                                    <button type="button" class="underline text-navy-900" onclick="toggleGroup('{{ \Illuminate\Support\Str::slug($group) }}', false)">Clear</button>
                                </div>
                            </div>

                            <div class="space-y-2" data-group="{{ \Illuminate\Support\Str::slug($group) }}">
                                @foreach($features as $meta)
                                    @php
                                        $key = $meta['key'];
                                        $readChecked = old("permissions.$key.read") !== null ? (bool) old("permissions.$key.read") : (bool) $meta['can_read'];
                                        $writeChecked = old("permissions.$key.write") !== null ? (bool) old("permissions.$key.write") : (bool) $meta['can_write'];
                                    @endphp
                                    <div class="flex items-center justify-between gap-4 rounded border border-gray-200 px-3 py-2">
                                        <div>
                                            <div class="text-sm font-semibold text-navy-900">{{ $meta['label'] }}</div>
                                            <div class="text-xs text-gray-600">{{ $key }}</div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <label class="flex items-center gap-2 text-sm text-navy-900">
                                                <input type="checkbox"
                                                       name="permissions[{{ $key }}][read]"
                                                       value="1"
                                                       class="w-4 h-4 permission-read"
                                                       {{ $readChecked ? 'checked' : '' }}
                                                       onchange="onReadChange(this)">
                                                <span>Read</span>
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-navy-900">
                                                <input type="checkbox"
                                                       name="permissions[{{ $key }}][write]"
                                                       value="1"
                                                       class="w-4 h-4 permission-write"
                                                       {{ $writeChecked ? 'checked' : '' }}
                                                       onchange="onWriteChange(this)">
                                                <span>Write</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Save Changes</button>
                <a href="{{ route('admin.users.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function onWriteChange(writeCheckbox) {
            const row = writeCheckbox.closest('.flex');
            const readCheckbox = row ? row.querySelector('.permission-read') : null;
            if (!readCheckbox) return;
            if (writeCheckbox.checked) {
                readCheckbox.checked = true;
            }
        }

        function onReadChange(readCheckbox) {
            const row = readCheckbox.closest('.flex');
            const writeCheckbox = row ? row.querySelector('.permission-write') : null;
            if (!writeCheckbox) return;
            if (!readCheckbox.checked) {
                writeCheckbox.checked = false;
            }
        }

        function toggleGroup(groupKey, checked) {
            const container = document.querySelector(`[data-group="${groupKey}"]`);
            if (!container) return;
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = checked;
            });
        }
    </script>
</x-app-layout>

