<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Employee Activity Logs</h2>
            <div class="text-sm text-gray-600">
                <span class="font-semibold text-navy-900">{{ $logs->total() }}</span> records
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="activityLogFilters()">
        <!-- Filters Panel -->
        <div class="bg-white border border-navy-900 rounded-lg p-6" x-data="{ open: true }">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-bold text-navy-900">Filters</h3>
                    <span class="text-xs text-gray-500" x-show="hasActiveFilters()">Active</span>
                </div>
                <button type="button" @click="open = !open" class="text-sm text-navy-900 hover:text-navy-600 font-semibold">
                    <span x-show="open">Hide</span>
                    <span x-show="!open">Show</span>
                </button>
            </div>

            <form method="GET" action="{{ route('admin.activity-logs.index') }}" x-show="open" x-transition class="grid grid-cols-1 xl:grid-cols-4 lg:grid-cols-3 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Employee</label>
                    <select name="employee_user_id" class="w-full border border-navy-900 rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach ($employees as $employeeUser)
                            <option value="{{ $employeeUser->id }}" @selected((string) $employeeUser->id === (string) $filters['employee_user_id'])>
                                {{ $employeeUser->employee->name ?? $employeeUser->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Category</label>
                    <select name="category" class="w-full border border-navy-900 rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($category === $filters['category'])>{{ Str::headline($category) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Action</label>
                    <select name="action_filter" class="w-full border border-navy-900 rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected($action === $filters['action_filter'])>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">HTTP Method</label>
                    <select name="http_method" class="w-full border border-navy-900 rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method }}" @selected($method === strtoupper((string) $filters['http_method']))>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Device Type</label>
                    <select name="device_type" class="w-full border border-navy-900 rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach ($deviceTypes as $deviceType)
                            <option value="{{ $deviceType }}" @selected($deviceType === $filters['device_type'])>{{ $deviceType }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Response Status</label>
                    <select name="response_status" class="w-full border border-navy-900 rounded px-3 py-2">
                        <option value="">All</option>
                        @foreach ($responseStatuses as $status)
                            <option value="{{ $status }}" @selected((string) $status === (string) $filters['response_status'])>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">IP Address</label>
                    <input type="text" name="ip_address" value="{{ $filters['ip_address'] }}" class="w-full border border-navy-900 rounded px-3 py-2" placeholder="Search IP" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="w-full border border-navy-900 rounded px-3 py-2" placeholder="Summary, action, route..." />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full border border-navy-900 rounded px-3 py-2" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full border border-navy-900 rounded px-3 py-2" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy-900 mb-1">Per Page</label>
                    <select name="per_page" class="w-full border border-navy-900 rounded px-3 py-2">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $filters['per_page'] === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-4 flex flex-wrap items-center gap-2 mt-2">
                    <button type="submit" class="bg-navy-900 text-white px-6 py-2 rounded hover:bg-opacity-90 font-semibold">Apply Filters</button>
                    <a href="{{ route('admin.activity-logs.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 font-semibold">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white border border-navy-900 rounded-lg overflow-hidden" x-data="activityLogTable({
            filters: @js($filters),
            initialSort: '{{ $filters['sort'] }}',
            initialDirection: '{{ $filters['direction'] }}',
            pollingInterval: 0,
        })">
            <div class="p-4 bg-navy-900 text-white flex flex-wrap gap-2 items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold">Logs</h3>
                    <p class="text-xs text-white/70">Sorted by <span x-text="sortLabel"></span></p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden sm:inline text-white/80">Live updates:</span>
                    <span class="px-2 py-1 rounded-full text-xs" :class="{ 'bg-green-500/20 text-green-200 border border-green-400/60': live, 'bg-gray-500/20 text-gray-200 border border-gray-400/50': !live }">
                        <span class="flex items-center gap-1">
                            <span class="inline-block w-2 h-2 rounded-full" :class="live ? 'bg-green-400 animate-pulse' : 'bg-gray-400'"></span>
                            <span x-text="live ? 'Connected' : 'Idle'"></span>
                        </span>
                    </span>
                    <button type="button" @click="toggleLive()" class="bg-white text-navy-900 px-3 py-1 rounded hover:bg-gray-100 font-semibold">Toggle Live</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 border-b border-gray-200 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3 cursor-pointer" @click="sort('occurred_at')">Date/Time <x-sort-icon :direction="$filters['direction']" active="{{ $filters['sort'] === 'occurred_at' }}" /></th>
                            <th class="px-4 py-3 cursor-pointer" @click="sort('employee')">Employee <x-sort-icon :direction="$filters['direction']" active="{{ $filters['sort'] === 'employee' }}" /></th>
                            <th class="px-4 py-3 cursor-pointer" @click="sort('action')">Action <x-sort-icon :direction="$filters['direction']" active="{{ $filters['sort'] === 'action' }}" /></th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Path</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">IP</th>
                            <th class="px-4 py-3">Device</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition" :id="'log-' + {{ $log->id }}">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-semibold text-navy-900">{{ $log->occurred_at?->timezone(config('app.timezone'))->format('M d, Y h:i:s A') ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->occurred_at?->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-semibold text-navy-900">{{ $log->employee_display_name }}</div>
                                    <div class="text-xs text-gray-500">ID: {{ $log->employee_user_id ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-mono text-xs bg-gray-200/70 text-gray-800 px-2 py-1 rounded inline-block">{{ $log->action }}</div>
                                    @if ($log->summary)
                                        <div class="text-xs text-gray-600 mt-1">{{ Str::limit($log->summary, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $log->category ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $log->category ? Str::headline($log->category) : 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $log->request_method ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $log->request_method ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    <div class="font-mono break-all">{{ $log->request_path }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $log->route_name ? 'Route: '.$log->route_name : 'No route name' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($log->response_status)
                                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $log->response_status >= 400 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $log->response_status }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs font-mono">
                                    <div>{{ $log->ip_address_v4 ?? $log->ip_address ?? 'N/A' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $log->ip_address_v6 }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    <div>{{ $log->device_type ?? 'Unknown' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ Str::limit($log->browser ?? 'Browser?', 30) }}</div>
                                    <div class="text-[11px] text-gray-500">{{ Str::limit($log->os ?? 'OS?', 30) }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.activity-logs.show', $log) }}" class="text-sm text-navy-900 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <x-icons.no-data class="w-12 h-12 text-gray-300" />
                                        <p>No activity logs found for the selected filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200">
                {{ $logs->withQueryString()->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function activityLogFilters() {
                return {
                    hasActiveFilters() {
                        const params = new URLSearchParams(window.location.search);
                        const filterKeys = ['employee_user_id', 'category', 'action_filter', 'http_method', 'device_type', 'response_status', 'ip_address', 'search', 'date_from', 'date_to'];
                        return filterKeys.some(key => params.get(key));
                    }
                }
            }

            function activityLogTable({ filters, initialSort, initialDirection, pollingInterval = 0 }) {
                return {
                    sortField: initialSort,
                    sortDirection: initialDirection,
                    live: false,
                    sortLabel: '',
                    channel: null,
                    sort(field) {
                        if (this.sortField === field) {
                            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                        } else {
                            this.sortField = field;
                            this.sortDirection = 'desc';
                        }

                        const params = new URLSearchParams(window.location.search);
                        params.set('sort', this.sortField);
                        params.set('direction', this.sortDirection);
                        window.location.search = params.toString();
                    },
                    toggleLive() {
                        this.live = !this.live;
                        if (this.live) {
                            this.subscribe();
                        } else {
                            this.unsubscribe();
                        }
                    },
                    subscribe() {
                        if (typeof window.Echo === 'undefined') {
                            console.warn('Echo is not configured');
                            this.live = false;
                            return;
                        }

                        this.channel = window.Echo.private('admin.activity.logs')
                            .listen('EmployeeActivityCreated', (event) => {
                                this.handleIncomingLog(event);
                            })
                            .error(error => {
                                console.error('Broadcast error', error);
                                this.live = false;
                            });
                    },
                    unsubscribe() {
                        if (this.channel && typeof this.channel.stopListening === 'function') {
                            this.channel.stopListening('EmployeeActivityCreated');
                        }
                        if (typeof window.Echo !== 'undefined') {
                            window.Echo.leave('admin.activity.logs');
                        }
                        this.channel = null;
                    },
                    handleIncomingLog(event) {
                        const tableBody = document.querySelector('tbody');
                        if (!tableBody) {
                            return;
                        }

                        const existing = document.getElementById(`log-${event.id}`);
                        if (existing) {
                            existing.classList.add('animate-pulse');
                            setTimeout(() => existing.classList.remove('animate-pulse'), 1000);
                            return;
                        }

                        // Optionally, fetch latest page when live updates arrive to keep pagination consistent
                        fetch(window.location.href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            }
                        }).then(response => {
                            if (!response.ok) return;
                            return response.text();
                        }).then(html => {
                            if (!html) return;
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newBody = doc.querySelector('tbody');
                            const newPagination = doc.querySelector('.p-4.border-t');
                            if (newBody) {
                                tableBody.innerHTML = newBody.innerHTML;
                            }
                            if (newPagination) {
                                document.querySelector('.p-4.border-t').innerHTML = newPagination.innerHTML;
                            }
                        }).catch(error => console.error('Live refresh failed', error));
                    },
                    init() {
                        this.updateSortLabel();
                        if (pollingInterval > 0) {
                            setInterval(() => this.fetchLatest(), pollingInterval);
                        }
                    },
                    updateSortLabel() {
                        const labelMap = {
                            occurred_at: 'date/time',
                            employee: 'employee',
                            action: 'action',
                            summary: 'summary',
                            response_status: 'status',
                        };
                        const label = labelMap[this.sortField] || this.sortField;
                        this.sortLabel = `${label} (${this.sortDirection})`;
                    },
                    fetchLatest() {
                        fetch(window.location.href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).then(response => response.ok ? response.json() : null)
                        .then(data => {
                            if (!data) return;
                            console.debug('Polled activity logs', data.meta);
                        }).catch(error => console.error(error));
                    },
                    destroy() {
                        this.unsubscribe();
                    }
                }
            }
        </script>
    @endpush
</x-app-layout>
