<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Employees</h2>
            <a href="{{ route('employees.create') }}" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Add New Employee</a>
        </div>
    </x-slot>

    @php
        $employmentTypeOptions = [
            'Onsite' => 'Onsite',
            'Project-Based (Freelancer) - Remote' => 'Project-Based (Freelancer) - Remote',
            'Contract Arbitrage' => 'Contract Arbitrage',
            'Monthly/Annual Contract' => 'Monthly/Annual Contract',
            'Hybrid' => 'Hybrid',
        ];
    @endphp

    <div class="space-y-6">
        <form method="GET" action="{{ route('employees.index') }}" class="flex gap-4" id="employeeSearchForm">
            <input type="text" name="search" id="employeeSearch" value="{{ request('search') }}" placeholder="Search by name, email, or role..." class="flex-1 px-4 py-2 border border-navy-900 rounded">
            <select name="employment_type" id="employmentTypeFilter" class="px-4 py-2 border border-navy-900 rounded">
                <option value="">All Types</option>
                @foreach($employmentTypeOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('employment_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Filter</button>
            @if(request('search') || request('employment_type'))
                <a href="{{ route('employees.index') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Clear</a>
            @endif
        </form>

        <div id="bulkToolbar" class="hidden bg-white border border-navy-900 rounded-lg p-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-lg font-semibold text-navy-900"><span id="selectedCount">0</span> selected</p>
                <p class="text-sm text-gray-600" id="selectedHint">Select employees to enable bulk actions.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white text-sm" data-bulk-action="view">View</button>
                <button type="button" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white text-sm" data-bulk-action="edit">Edit</button>
                <button type="button" class="px-4 py-2 border border-green-700 text-green-700 rounded hover:bg-green-700 hover:text-white text-sm" data-bulk-action="enable_location">Enable Location</button>
                <button type="button" class="px-4 py-2 border border-orange-600 text-orange-600 rounded hover:bg-orange-600 hover:text-white text-sm" data-bulk-action="disable_location">Disable Location</button>
                <button type="button" class="px-4 py-2 border border-red-600 text-red-600 rounded hover:bg-red-600 hover:text-white text-sm" data-bulk-action="delete">Delete</button>
            </div>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden" id="employeesTableContainer">
            @if($employees->count() > 0)
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-3 px-4 w-12">
                                <input type="checkbox" id="selectAllRows" class="rounded border-navy-900 text-navy-900 focus:ring-navy-900">
                            </th>
                            <th class="text-left py-3 px-4">Name</th>
                            <th class="text-left py-3 px-4 hidden md:table-cell">Email</th>
                            <th class="text-left py-3 px-4 hidden lg:table-cell">Role</th>
                            <th class="text-left py-3 px-4 hidden lg:table-cell">Employment Type</th>
                            <th class="text-left py-3 px-4 hidden sm:table-cell">Salary</th>
                            <th class="text-left py-3 px-4 hidden xl:table-cell">Commission %</th>
                            <th class="text-left py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            @php
                                $employeeInitials = collect(explode(' ', trim($employee->name)))
                                    ->filter()
                                    ->map(fn($segment) => mb_strtoupper(mb_substr($segment, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                            @endphp
                            <tr class="border-b" data-employee-id="{{ $employee->id }}" data-employee-name="{{ $employee->name }}" data-employee-email="{{ $employee->email }}" data-employee-role="{{ $employee->role }}">
                                <td class="py-3 px-4">
                                    <input type="checkbox" class="employee-row-checkbox rounded border-navy-900 text-navy-900 focus:ring-navy-900" data-employee-id="{{ $employee->id }}">
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 sm:w-8 sm:h-8 rounded-full border border-gray-200 overflow-hidden bg-gray-100 flex items-center justify-center text-sm font-semibold text-gray-600">
                                            @if($employee->profile_photo_url)
                                                <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->name }} profile photo" class="h-full w-full object-cover">
                                            @else
                                                <span>{{ $employeeInitials }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-semibold text-navy-900 flex items-center gap-2">
                                                <span>{{ $employee->name }}</span>
                                                @if($employee->geolocation_required)
                                                    <span class="text-xs text-green-600" title="Geolocation required">📍</span>
                                                @else
                                                    <span class="text-xs text-orange-600" title="Remote employee">🌐</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500 sm:hidden">
                                                {{ $employee->role }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 hidden md:table-cell">{{ $employee->email }}</td>
                                <td class="py-3 px-4 hidden lg:table-cell">{{ $employee->role }}</td>
                                <td class="py-3 px-4 hidden lg:table-cell">{{ $employee->employment_type }}</td>
                                <td class="py-3 px-4 hidden sm:table-cell">{{ $employee->currency ? $employee->currency->symbol : 'Rs.' }}{{ number_format($employee->salary, 2) }}</td>
                                <td class="py-3 px-4 hidden xl:table-cell">{{ $employee->commission_rate }}%</td>
                                <td class="py-3 px-4">
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <a href="{{ route('employees.show', $employee) }}" class="text-navy-900 hover:underline text-sm">View</a>
                                        <a href="{{ route('employees.edit', $employee) }}" class="text-navy-900 hover:underline text-sm">Edit</a>
                                        <button 
                                            onclick="toggleGeolocation({{ $employee->id }}, this)" 
                                            class="text-sm {{ $employee->geolocation_required ? 'text-orange-600' : 'text-green-600' }} hover:underline text-left"
                                            data-geolocation="{{ $employee->geolocation_required ? '1' : '0' }}"
                                        >
                                            {{ $employee->geolocation_required ? '🌐 Disable Location' : '📍 Require Location' }}
                                        </button>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4 border-t border-gray-200 bg-gray-50" id="employeesPagination">{{ $employees->links() }}</div>
            @else
                <div class="p-8 text-center text-gray-600">
                    <p>No employees found.</p>
                    <a href="{{ route('employees.create') }}" class="text-navy-900 hover:underline mt-2 inline-block">Add your first employee</a>
                </div>
            @endif
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50" data-close-confirm></div>
        <div class="relative z-10 max-w-lg mx-auto mt-32 bg-white border border-navy-900 rounded-lg shadow-xl p-6 space-y-4">
            <div>
                <h3 id="confirmTitle" class="text-xl font-bold text-navy-900"></h3>
                <p id="confirmMessage" class="text-sm text-gray-600 mt-1"></p>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white" data-close-confirm>Cancel</button>
                <button type="button" id="confirmActionBtn" class="px-4 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Bulk Drawer -->
    <div id="bulkDrawer" class="fixed inset-0 z-40 pointer-events-none">
        <div class="drawer-overlay absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-300" data-close-drawer></div>
        <div class="drawer-panel absolute inset-x-0 bottom-0 bg-white border-t-4 border-navy-900 rounded-t-3xl shadow-2xl transform translate-y-full transition-transform duration-300 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <p id="drawerTitle" class="text-xl font-bold text-navy-900">Bulk Details</p>
                    <p id="drawerSubtitle" class="text-sm text-gray-600"></p>
                </div>
                <button type="button" class="text-gray-500 hover:text-navy-900" data-close-drawer>&times;</button>
            </div>
            <div class="px-6 py-4 flex-1 overflow-y-auto" id="drawerBody">
                <div id="drawerLoading" class="py-10 text-center hidden">
                    <svg class="w-8 h-8 animate-spin text-navy-900 mx-auto" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-gray-600 mt-2">Fetching employee data...</p>
                </div>

                <div id="drawerViewContent" class="space-y-3 hidden">
                    <div id="bulkViewList" class="space-y-3"></div>
                </div>

                <div id="drawerEditContent" class="hidden">
                    <form id="bulkEditForm" class="space-y-4">
                        <div id="bulkEditList" class="space-y-4"></div>
                        <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2 border-t border-gray-200">
                            <button type="button" class="px-4 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white" data-close-drawer>Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let searchTimeout;
            const searchInput = document.getElementById('employeeSearch');
            const typeFilter = document.getElementById('employmentTypeFilter');
            const tableContainer = document.getElementById('employeesTableContainer');

            function performSearch() {
                const searchValue = searchInput.value;
                const typeValue = typeFilter.value;
                const url = new URL('{{ route('employees.index') }}');

                if (searchValue) url.searchParams.set('search', searchValue);
                if (typeValue) url.searchParams.set('employment_type', typeValue);

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('employeesTableContainer');
                    if (newContent) {
                        tableContainer.innerHTML = newContent.innerHTML;
                    }

                    window.history.pushState({}, '', url);
                    if (window.bulkManager) {
                        window.bulkManager.refreshRows();
                    }
                })
                .catch(error => console.error('Search error:', error));
            }

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            typeFilter.addEventListener('change', performSearch);

            document.getElementById('employeeSearchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });

            window.bulkManager = createBulkManager({
                employmentTypes: @json($employmentTypeOptions),
                geolocationModes: @json($geolocationModeOptions),
                bulkActionUrl: '{{ route('employees.bulk-action') }}',
                bulkFetchUrl: '{{ route('employees.bulk-fetch') }}',
                bulkUpdateUrl: '{{ route('employees.bulk-update') }}',
            });

            window.bulkManager.refreshRows();
        });

        window.toggleGeolocation = function(employeeId, button) {
            if (!confirm('Are you sure you want to toggle geolocation tracking for this employee?')) {
                return;
            }

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = '⏳ Updating...';

            fetch(`/employees/${employeeId}/toggle-geolocation`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const isRequired = data.geolocation_required;
                    button.dataset.geolocation = isRequired ? '1' : '0';
                    button.className = `text-sm ${isRequired ? 'text-orange-600' : 'text-green-600'} hover:underline text-left`;
                    button.textContent = isRequired ? '🌐 Disable Location' : '📍 Require Location';

                    const nameCell = button.closest('tr').querySelector('td:nth-child(2) span:last-child');
                    if (nameCell) {
                        nameCell.textContent = isRequired ? '📍' : '🌐';
                        nameCell.className = `text-xs ${isRequired ? 'text-green-600' : 'text-orange-600'}`;
                    }

                    alert(data.message);
                } else {
                    alert(data.message || 'Failed to update geolocation setting');
                    button.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating geolocation setting');
                button.textContent = originalText;
            })
            .finally(() => {
                button.disabled = false;
            });
        }

        function createBulkManager(config) {
            const state = {
                selected: new Set(),
                currentAction: null,
                drawerMode: null,
            };

            const toolbar = document.getElementById('bulkToolbar');
            const selectedCount = document.getElementById('selectedCount');
            const selectedHint = document.getElementById('selectedHint');
            const tableContainer = document.getElementById('employeesTableContainer');
            const confirmModal = document.getElementById('confirmModal');
            const confirmTitle = document.getElementById('confirmTitle');
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmActionBtn = document.getElementById('confirmActionBtn');
            const drawer = document.getElementById('bulkDrawer');
            const drawerOverlay = drawer.querySelector('.drawer-overlay');
            const drawerPanel = drawer.querySelector('.drawer-panel');
            const drawerTitle = document.getElementById('drawerTitle');
            const drawerSubtitle = document.getElementById('drawerSubtitle');
            const drawerBody = document.getElementById('drawerBody');
            const drawerViewContent = document.getElementById('drawerViewContent');
            const drawerEditContent = document.getElementById('drawerEditContent');
            const drawerLoading = document.getElementById('drawerLoading');
            const bulkViewList = document.getElementById('bulkViewList');
            const bulkEditList = document.getElementById('bulkEditList');
            const bulkEditForm = document.getElementById('bulkEditForm');
            let selectAllCheckbox = document.getElementById('selectAllRows');
            let rowCheckboxes = [];

            const actionCopy = {
                enable_location: {
                    title: 'Enable Location Tracking',
                    message: count => `Enable location tracking for ${count} selected ${count === 1 ? 'employee' : 'employees'}? Employees without login accounts will be skipped.`,
                    button: 'Enable',
                },
                disable_location: {
                    title: 'Disable Location Tracking',
                    message: count => `Disable location tracking for ${count} selected ${count === 1 ? 'employee' : 'employees'}?`,
                    button: 'Disable',
                },
                delete: {
                    title: 'Delete Employees',
                    message: count => `This will delete ${count} selected ${count === 1 ? 'employee' : 'employees'}. This action cannot be undone. Proceed?`,
                    button: 'Delete',
                },
            };

            function refreshRows() {
                rowCheckboxes = Array.from(document.querySelectorAll('.employee-row-checkbox'));
                selectAllCheckbox = document.getElementById('selectAllRows');

                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = state.selected.has(parseInt(checkbox.dataset.employeeId));
                    checkbox.addEventListener('change', () => toggleRowSelection(checkbox));
                });

                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = rowCheckboxes.length > 0 && rowCheckboxes.every(cb => cb.checked);
                    selectAllCheckbox.addEventListener('change', handleSelectAllChange);
                }

                attachActionButtons();
                updateToolbar();
            }

            function toggleRowSelection(checkbox) {
                const id = parseInt(checkbox.dataset.employeeId);
                if (checkbox.checked) {
                    state.selected.add(id);
                } else {
                    state.selected.delete(id);
                }
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = rowCheckboxes.length > 0 && rowCheckboxes.every(cb => cb.checked);
                }
                updateToolbar();
            }

            function handleSelectAllChange() {
                const shouldSelect = selectAllCheckbox.checked;
                rowCheckboxes.forEach(cb => {
                    cb.checked = shouldSelect;
                    const id = parseInt(cb.dataset.employeeId);
                    if (shouldSelect) {
                        state.selected.add(id);
                    } else {
                        state.selected.delete(id);
                    }
                });
                updateToolbar();
            }

            function updateToolbar() {
                const count = state.selected.size;
                if (count > 0) {
                    toolbar.classList.remove('hidden');
                    selectedCount.textContent = count;
                    selectedHint.textContent = 'Choose an action to perform on the selected employees.';
                } else {
                    toolbar.classList.add('hidden');
                    selectedHint.textContent = 'Select employees to enable bulk actions.';
                }
            }

            function attachActionButtons() {
                document.querySelectorAll('[data-bulk-action]').forEach(button => {
                    button.removeEventListener('click', button._bulkListener || (() => {}));
                    button._bulkListener = () => handleAction(button.dataset.bulkAction);
                    button.addEventListener('click', button._bulkListener);
                });
            }

            function handleAction(action) {
                if (state.selected.size === 0) {
                    alert('Please select at least one employee first.');
                    return;
                }

                if (action === 'view' || action === 'edit') {
                    openDrawer(action);
                    return;
                }

                state.currentAction = action;
                openConfirm(action);
            }

            function openConfirm(action) {
                const copy = actionCopy[action];
                if (!copy) return;

                confirmTitle.textContent = copy.title;
                confirmMessage.textContent = copy.message(state.selected.size);
                confirmActionBtn.textContent = copy.button;
                confirmModal.classList.remove('hidden');
            }

            function closeConfirm() {
                confirmModal.classList.add('hidden');
            }

            function performBulkAction() {
                if (!state.currentAction) return;

                fetch(config.bulkActionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: state.currentAction,
                        employee_ids: Array.from(state.selected),
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Bulk action failed.');
                        return;
                    }

                    let message = data.message;
                    if (data.summary?.skipped?.length) {
                        const skippedCount = data.summary.skipped.length;
                        message += `\n${skippedCount} ${skippedCount === 1 ? 'employee was' : 'employees were'} skipped.`;
                    }
                    alert(message);
                    window.location.reload();
                })
                .catch(() => alert('An error occurred while performing bulk action.'))
                .finally(() => {
                    closeConfirm();
                    state.currentAction = null;
                });
            }

            function openDrawer(mode) {
                state.drawerMode = mode;
                drawer.classList.remove('pointer-events-none');
                drawerOverlay.classList.remove('opacity-0');
                drawerPanel.classList.remove('translate-y-full');
                document.body.classList.add('overflow-hidden');
                drawerLoading.classList.remove('hidden');
                drawerViewContent.classList.add('hidden');
                drawerEditContent.classList.add('hidden');

                drawerTitle.textContent = mode === 'edit' ? 'Bulk Edit Employees' : 'Bulk View Employees';
                drawerSubtitle.textContent = `${state.selected.size} selected`;

                fetch(config.bulkFetchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: Array.from(state.selected) })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Unable to load employees.');
                        closeDrawer();
                        return;
                    }

                    if (mode === 'view') {
                        renderViewDrawer(data.employees);
                    } else {
                        renderEditDrawer(data.employees);
                    }
                })
                .catch(() => {
                    alert('Failed to fetch employee details.');
                    closeDrawer();
                })
                .finally(() => {
                    drawerLoading.classList.add('hidden');
                });
            }

            function renderViewDrawer(employees) {
                bulkViewList.innerHTML = '';
                employees.forEach(employee => {
                    const card = document.createElement('div');
                    card.className = 'border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow';
                    card.innerHTML = `
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <p class="text-lg font-semibold text-navy-900">${employee.name}</p>
                                <p class="text-sm text-gray-600">${employee.email}</p>
                            </div>
                            <span class="text-xs uppercase tracking-wide px-3 py-1 rounded-full ${employee.geolocation_required ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-600'}">
                                ${employee.geolocation_mode_label}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm mt-3">
                            <div>
                                <p class="text-gray-500">Role</p>
                                <p class="font-semibold">${employee.role}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Employment Type</p>
                                <p class="font-semibold">${employee.employment_type}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Salary</p>
                                <p class="font-semibold">${employee.currency_symbol}${Number(employee.salary).toLocaleString()}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Commission</p>
                                <p class="font-semibold">${Number(employee.commission_rate).toFixed(2)}%</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Joining Date</p>
                                <p class="font-semibold">${employee.joining_date ?? 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Primary Contact</p>
                                <p class="font-semibold">${employee.phone ?? 'N/A'}</p>
                            </div>
                        </div>
                    `;
                    bulkViewList.appendChild(card);
                });

                drawerViewContent.classList.remove('hidden');
            }

            function renderEditDrawer(employees) {
                bulkEditList.innerHTML = '';
                employees.forEach(employee => {
                    const card = document.createElement('div');
                    card.className = 'border border-gray-200 rounded-2xl p-4 shadow-sm edit-card';
                    card.dataset.employeeId = employee.id;

                    const employmentOptions = Object.entries(config.employmentTypes)
                        .map(([value, label]) => `<option value="${value}" ${value === employee.employment_type ? 'selected' : ''}>${label}</option>`) 
                        .join('');

                    const geolocationSelect = employee.has_user_account ? `
                        <div>
                            <label class="text-xs font-semibold text-navy-900 mb-1 block">Geolocation Mode</label>
                            <select name="geolocation_mode" class="w-full border border-navy-900 rounded px-3 py-2">
                                ${Object.entries(config.geolocationModes).map(([value, label]) => `
                                    <option value="${value}" ${employee.geolocation_mode === value ? 'selected' : ''}>${label}</option>
                                `).join('')}
                            </select>
                        </div>
                    ` : `
                        <div>
                            <label class="text-xs font-semibold text-navy-900 mb-1 block">Geolocation Mode</label>
                            <div class="px-3 py-2 border border-dashed border-gray-300 rounded text-xs text-gray-500">
                                Create a login for this employee to enable geolocation controls.
                            </div>
                        </div>
                    `;

                    card.innerHTML = `
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <p class="text-lg font-semibold text-navy-900">${employee.name}</p>
                                <p class="text-sm text-gray-600">${employee.email}</p>
                            </div>
                            <span class="text-xs text-gray-500">ID: ${employee.id}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="text-xs font-semibold text-navy-900 mb-1 block">Role *</label>
                                <input type="text" name="role" value="${employee.role}" class="w-full border border-navy-900 rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-navy-900 mb-1 block">Employment Type *</label>
                                <select name="employment_type" class="w-full border border-navy-900 rounded px-3 py-2" required>
                                    ${employmentOptions}
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-navy-900 mb-1 block">Salary *</label>
                                <input type="number" name="salary" value="${employee.salary}" min="0" step="0.01" class="w-full border border-navy-900 rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-navy-900 mb-1 block">Commission (%) *</label>
                                <input type="number" name="commission_rate" value="${employee.commission_rate}" min="0" max="100" step="0.01" class="w-full border border-navy-900 rounded px-3 py-2" required>
                            </div>
                            ${geolocationSelect}
                        </div>
                    `;

                    bulkEditList.appendChild(card);
                });

                drawerEditContent.classList.remove('hidden');
            }

            function closeDrawer() {
                drawer.classList.add('pointer-events-none');
                drawerOverlay.classList.add('opacity-0');
                drawerPanel.classList.add('translate-y-full');
                document.body.classList.remove('overflow-hidden');
                state.drawerMode = null;
            }

            bulkEditForm.addEventListener('submit', event => {
                event.preventDefault();
                const updates = [];

                bulkEditList.querySelectorAll('.edit-card').forEach(card => {
                    const id = parseInt(card.dataset.employeeId);
                    const role = card.querySelector('input[name="role"]').value.trim();
                    const employmentType = card.querySelector('select[name="employment_type"]').value;
                    const salary = parseFloat(card.querySelector('input[name="salary"]').value);
                    const commission = parseFloat(card.querySelector('input[name="commission_rate"]').value);
                    const geoSelect = card.querySelector('select[name="geolocation_mode"]');

                    updates.push({
                        id,
                        role,
                        employment_type: employmentType,
                        salary,
                        commission_rate: commission,
                        geolocation_mode: geoSelect ? geoSelect.value : null,
                    });
                });

                fetch(config.bulkUpdateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ updates })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Bulk update failed.');
                        return;
                    }
                    alert(data.message);
                    window.location.reload();
                })
                .catch(() => alert('Failed to save bulk edits.'));
            });

            confirmActionBtn.addEventListener('click', performBulkAction);
            confirmModal.querySelectorAll('[data-close-confirm]').forEach(button => button.addEventListener('click', closeConfirm));
            drawerOverlay.addEventListener('click', closeDrawer);
            drawer.querySelectorAll('[data-close-drawer]').forEach(button => button.addEventListener('click', closeDrawer));

            return {
                refreshRows,
                clearSelection() {
                    state.selected.clear();
                    refreshRows();
                },
            };
        }
    </script>
</x-app-layout>
