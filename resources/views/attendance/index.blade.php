<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-navy-900">My Attendance</h2>
            <a href="{{ route('attendance.fix-requests.index') }}" class="bg-navy-900 text-white px-4 py-2 rounded hover:bg-navy-800">
                View Fix Requests
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Dynamic Messages -->
        <div id="dynamicMessage" class="hidden px-4 py-3 rounded"></div>

        <!-- Location Info - Only show for employees with geolocation required -->
        @if($employee->geolocation_required)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="font-semibold text-blue-900 mb-1">📍 Location-Based Attendance</h4>
                    <p class="text-sm text-blue-800">
                        To check in/out, you must be within <strong>{{ $employee->user->office_radius_meters ?? 15 }} meters</strong> of the office location. 
                        The system takes multiple GPS samples for accuracy (this takes ~15 seconds).
                    </p>
                    <p class="text-xs text-blue-700 mt-2">
                        💡 <strong>Tips for best accuracy:</strong><br>
                        • Use a mobile phone instead of desktop/laptop<br>
                        • Enable high-accuracy GPS in your device settings<br>
                        • Move near a window if indoors<br>
                        • Stay still while the system takes GPS samples
                    </p>
                    <div id="locationDebug" class="mt-3 p-2 bg-white rounded text-xs font-mono hidden">
                        <div class="font-semibold text-gray-700 mb-1">Debug Info:</div>
                        <div id="debugInfo" class="text-gray-600"></div>
                    </div>
                    <button onclick="testLocation()" class="mt-2 text-xs text-blue-600 hover:underline">
                        🔍 Test My Location & Calculate Distance
                    </button>
                    <button onclick="clearLocationCache()" class="mt-2 ml-2 text-xs text-orange-600 hover:underline">
                        🔄 Clear Location Cache
                    </button>
                </div>
            </div>
        </div>
        @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="font-semibold text-green-900 mb-1">🌐 Geolocation Disabled Tracking</h4>
                    <p class="text-sm text-green-800">
                        You can check in/out from anywhere. Location tracking is not required for your account.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Check In/Out Card -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Today's Attendance</h3>
            
            @if($todayAttendance)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Check-in Time</p>
                            <p class="text-lg font-semibold text-navy-900">
                                {{ $todayAttendance->check_in ? $todayAttendance->check_in->format('h:i A') : 'Not checked in' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Check-out Time</p>
                            <p class="text-lg font-semibold text-navy-900">
                                {{ $todayAttendance->check_out ? $todayAttendance->check_out->format('h:i A') : 'Not checked out' }}
                            </p>
                        </div>
                        @if($todayAttendance->formatted_work_duration)
                            <div>
                                <p class="text-sm text-gray-600">Work Duration</p>
                                <p class="text-lg font-semibold text-green-600">
                                    {{ $todayAttendance->formatted_work_duration }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-4">
                        @if(!$todayAttendance->hasCheckedOut())
                            <button id="checkOutBtn" type="button" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 font-semibold text-lg shadow-lg transition-all duration-200 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed" style="background-color: #dc2626; color: white;">
                                <span id="checkOutText">Check Out</span>
                                <span id="checkOutLoader" class="hidden">
                                    <svg class="animate-spin inline h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        @else
                            <p class="text-green-600 font-semibold">You have completed your attendance for today.</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-600 mb-4">You haven't checked in today.</p>
                    <button id="checkInBtn" type="button" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold text-lg shadow-lg transition-all duration-200 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed" style="background-color: #16a34a; color: white;">
                        <span id="checkInText">Check In</span>
                        <span id="checkInLoader" class="hidden">
                            <svg class="animate-spin inline h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Attendance History -->
        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">This Month's Attendance</h3>
            
            @if($attendances->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-navy-900">
                                <th class="text-left py-2 px-4 text-navy-900">Date</th>
                                <th class="text-left py-2 px-4 text-navy-900">Check In</th>
                                <th class="text-left py-2 px-4 text-navy-900">Check Out</th>
                                <th class="text-left py-2 px-4 text-navy-900">Duration</th>
                                <th class="text-left py-2 px-4 text-navy-900">Status</th>
                                <th class="text-left py-2 px-4 text-navy-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                                <tr class="border-b">
                                    <td class="py-2 px-4">{{ $attendance->attendance_date->format('M d, Y') }}</td>
                                    <td class="py-2 px-4">
                                        {{ $attendance->check_in ? $attendance->check_in->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-2 px-4">
                                        {{ $attendance->check_out ? $attendance->check_out->format('h:i A') : '-' }}
                                    </td>
                                    <td class="py-2 px-4">
                                        {{ $attendance->formatted_work_duration ?? '-' }}
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($attendance->hasCheckedOut())
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Complete</span>
                                        @elseif($attendance->hasCheckedIn())
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Checked In</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">Incomplete</span>
                                        @endif

                                        @if($attendance->pendingFixRequests->count() > 0)
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs ml-1">Fix Pending</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">
                                        <a href="{{ route('attendance.show', $attendance) }}" class="text-navy-900 hover:underline mr-2">View</a>
                                        <a href="{{ route('attendance.fix-requests.create', $attendance) }}" class="text-blue-600 hover:underline">Request Fix</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-600 text-center py-8">No attendance records for this month.</p>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Check if geolocation is required for this employee
        const geolocationRequired = @json($employee->geolocation_required);

        // Initialize hybrid geolocation system with more samples for accuracy
        const hybridGeo = new HybridGeolocation({
            maxAttempts: 7, // Take 7 samples to match admin setup
            timeout: 20000,
            minAccuracy: 100,
            samplingInterval: 2000
        });

        const wifiPositioning = new WiFiPositioning();
        let publicIp = null;

        async function fetchPublicIp() {
            try {
                const response = await fetch('https://api.ipify.org?format=json');
                if (!response.ok) {
                    throw new Error('Failed to fetch public IP');
                }
                const data = await response.json();
                if (data && data.ip) {
                    publicIp = data.ip;
                    console.log('🌐 Public IPv4 detected:', publicIp);
                }
            } catch (error) {
                console.warn('Unable to determine public IP:', error);
            }
        }

        fetchPublicIp();
        
        // Fetch calibration data from database
        let dbCalibration = null;
        async function loadCalibrationFromDB() {
            try {
                const response = await fetch('{{ route("office-location.calibration") }}');
                const data = await response.json();
                if (data.success) {
                    dbCalibration = data.calibration;
                    console.log('✅ Calibration data loaded from database:', dbCalibration);
                } else {
                    console.warn('⚠️ No calibration data in database');
                }
            } catch (error) {
                console.warn('Failed to load calibration:', error);
            }
        }
        
        // Load calibration on page load
        loadCalibrationFromDB();

        // Secure geolocation handling
        const checkInBtn = document.getElementById('checkInBtn');
        const checkOutBtn = document.getElementById('checkOutBtn');
        const dynamicMessage = document.getElementById('dynamicMessage');

        // Haversine formula to calculate distance
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c; // Distance in meters
        }

        // Clear location cache
        function clearLocationCache() {
            showMessage('🔄 Location cache cleared. Click "Test My Location" to get fresh coordinates.', 'success');
        }

        // Test location function with hybrid positioning
        async function testLocation() {
            // Remote employees don't need location testing
            if (!geolocationRequired) {
                showMessage('🌐 You are a remote employee. Location tracking is not required for your account.', 'success');
                return;
            }

            const debugDiv = document.getElementById('locationDebug');
            const debugInfo = document.getElementById('debugInfo');
            
            debugDiv.classList.remove('hidden');
            debugInfo.innerHTML = '⏳ Taking 7 GPS samples for accuracy (15-20 seconds)... Please stay still and check console for progress.';
            
            if (!navigator.geolocation) {
                debugInfo.innerHTML = '❌ Geolocation not supported';
                return;
            }
            
            try {
                // Get GPS location
                let location = await hybridGeo.getAccurateLocation();
                let methodUsed = location.method || '📍 Raw GPS';
                console.log('GPS result:', location);
                
                // Apply database calibration if available
                if (dbCalibration) {
                    const correctedLat = location.latitude + dbCalibration.latitude_offset;
                    const correctedLon = location.longitude + dbCalibration.longitude_offset;
                    
                    console.log('Applying DB calibration:');
                    console.log('  Raw:', location.latitude, location.longitude);
                    console.log('  Offset:', dbCalibration.latitude_offset, dbCalibration.longitude_offset);
                    console.log('  Corrected:', correctedLat, correctedLon);
                    
                    location = {
                        latitude: parseFloat(correctedLat.toFixed(8)),
                        longitude: parseFloat(correctedLon.toFixed(8)),
                        accuracy: Math.min(location.accuracy, 20),
                        method: '🎯 DB-Calibrated',
                        originalGPS: {
                            latitude: location.latitude,
                            longitude: location.longitude
                        }
                    };
                    methodUsed = '🎯 DB-Calibrated';
                }
                
                // Normalize coordinates to 8 decimal places
                const lat = parseFloat(location.latitude.toFixed(8));
                const lon = parseFloat(location.longitude.toFixed(8));
                const acc = Math.round(location.accuracy);
                
                // Office coordinates (from admin settings)
                const officeLat = {{ $employee->user->office_latitude ?? 0 }};
                const officeLon = {{ $employee->user->office_longitude ?? 0 }};
                const officeRadius = {{ $employee->user->office_radius_meters ?? 15 }};
                
                let distanceInfo = '';
                if (officeLat && officeLon) {
                    const distance = calculateDistance(lat, lon, officeLat, officeLon);
                    const withinRange = distance <= officeRadius;
                    const statusIcon = withinRange ? '✅' : '❌';
                    const statusColor = withinRange ? 'text-green-600' : 'text-red-600';
                    
                    distanceInfo = `
                        <div class="mt-2 pt-2 border-t border-gray-300">
                            <div class="${statusColor} font-semibold">${statusIcon} Distance from Office: ${distance.toFixed(2)} meters</div>
                            <div class="text-gray-600">Required: Within ${officeRadius} meters</div>
                            <div class="text-gray-600">Office: ${officeLat.toFixed(8)}, ${officeLon.toFixed(8)}</div>
                            ${location.corrected ? '<div class="text-blue-600 text-xs mt-1">✨ Location corrected using calibration data</div>' : ''}
                            ${location.sampleCount ? `<div class="text-blue-600 text-xs">${location.sampleCount} GPS samples averaged</div>` : ''}
                            ${withinRange ? 
                                '<div class="text-green-600 font-semibold mt-1">✓ You can check in!</div>' : 
                                '<div class="text-red-600 font-semibold mt-1">✗ Too far to check in</div>'
                            }
                        </div>
                    `;
                }
                
                // Show calibration info if available
                const calibInfo = dbCalibration ? 
                    `<div class="text-xs text-green-600 mt-1">✅ Database calibration active: ${dbCalibration.label}</div>
                     <div class="text-xs text-gray-600">Offset: ${dbCalibration.latitude_offset.toFixed(8)}, ${dbCalibration.longitude_offset.toFixed(8)}</div>` : 
                    `<div class="text-xs text-orange-600 mt-1">⚠️ No calibration data. Admin should set office location.</div>`;
                
                debugInfo.innerHTML = `
                    ✅ Location obtained<br>
                    📍 Your Latitude: ${lat.toFixed(8)}<br>
                    📍 Your Longitude: ${lon.toFixed(8)}<br>
                    🎯 GPS Accuracy: ${acc} meters<br>
                    🔧 Method: ${methodUsed}<br>
                    ${calibInfo}
                    <a href="https://www.google.com/maps?q=${lat},${lon}" target="_blank" class="text-blue-600 hover:underline">View Your Location on Google Maps →</a>
                    ${distanceInfo}
                `;
            } catch (error) {
                console.error('All positioning methods failed:', error);
                debugInfo.innerHTML = `❌ Error: ${error.message || 'Unable to get location'}<br><small>Try enabling location services, moving near a window, or using a mobile device.</small>`;
            }
        }

        function showMessage(message, type = 'success') {
            dynamicMessage.className = `px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'}`;
            dynamicMessage.textContent = message;
            dynamicMessage.classList.remove('hidden');
            
            setTimeout(() => {
                dynamicMessage.classList.add('hidden');
            }, 5000);
        }

        async function getLocation(callback) {
            // For remote employees, skip GPS sampling entirely
            if (!geolocationRequired) {
                console.log('🌐 Remote employee - skipping GPS sampling');
                callback({
                    latitude: null,
                    longitude: null,
                    accuracy: null
                });
                return;
            }

            if (!navigator.geolocation) {
                showMessage('Geolocation is not supported by your browser.', 'error');
                return;
            }

            // Show getting location message
            showMessage('📡 Getting your precise location (taking 7 GPS samples, ~15 seconds)... Please stay still.', 'success');

            try {
                // Get GPS location first
                let location = await hybridGeo.getAccurateLocation();
                console.log('📍 Raw GPS location obtained:', location);
                
                // Apply database calibration if available
                if (dbCalibration) {
                    const correctedLat = location.latitude + dbCalibration.latitude_offset;
                    const correctedLon = location.longitude + dbCalibration.longitude_offset;
                    
                    location = {
                        latitude: parseFloat(correctedLat.toFixed(8)),
                        longitude: parseFloat(correctedLon.toFixed(8)),
                        accuracy: Math.min(location.accuracy, 20), // Improved accuracy
                        method: 'DB-Calibrated',
                        originalGPS: {
                            latitude: location.latitude,
                            longitude: location.longitude
                        },
                        calibration: dbCalibration.label
                    };
                    console.log('✨ Applied database calibration:', location);
                }
                
                // Normalize coordinates to 8 decimal places before sending
                const coords = {
                    latitude: parseFloat(location.latitude.toFixed(8)),
                    longitude: parseFloat(location.longitude.toFixed(8)),
                    accuracy: location.accuracy
                };
                
                console.log('Final coordinates (normalized):', coords);
                console.log('Method:', location.method);
                console.log('Accuracy:', coords.accuracy, 'meters');
                
                // Show accuracy info
                if (coords.accuracy > 50) {
                    showMessage(`⚠️ Location accuracy: ${Math.round(coords.accuracy)}m (${location.method})`, 'success');
                } else {
                    showMessage(`✅ Good accuracy: ${Math.round(coords.accuracy)}m (${location.method})`, 'success');
                }
                
                callback(coords);
            } catch (error) {
                console.error('All location methods failed:', error);
                showMessage('Unable to get accurate location. Please ensure location services are enabled.', 'error');
                
                // Re-enable button
                const activeBtn = document.querySelector('button[disabled]');
                if (activeBtn) {
                    activeBtn.disabled = false;
                    const text = activeBtn.querySelector('span:not(.hidden)');
                    const loader = activeBtn.querySelector('span.hidden');
                    if (text) text.classList.remove('hidden');
                    if (loader) loader.classList.add('hidden');
                }
            }
        }


        function sendAttendanceRequest(url, coords, btnId, textId, loaderId) {
            const btn = document.getElementById(btnId);
            const text = document.getElementById(textId);
            const loader = document.getElementById(loaderId);

            btn.disabled = true;
            text.classList.add('hidden');
            loader.classList.remove('hidden');

            // Use encrypted payload
            const payload = btoa(JSON.stringify(coords));

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                    public_ip: publicIp,
                    _token: document.querySelector('meta[name="csrf-token"]').content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show detailed error with distance if available
                    let errorMsg = data.message;
                    if (data.distance && data.required_distance) {
                        errorMsg += `\n\n📏 Your distance: ${data.distance}m\n✓ Required: Within ${data.required_distance}m`;
                    }
                    showMessage(errorMsg, 'error');
                    btn.disabled = false;
                    text.classList.remove('hidden');
                    loader.classList.add('hidden');
                }
            })
            .catch(error => {
                showMessage('An error occurred. Please try again.', 'error');
                btn.disabled = false;
                text.classList.remove('hidden');
                loader.classList.add('hidden');
            });
        }

        if (checkInBtn) {
            checkInBtn.addEventListener('click', function() {
                getLocation((coords) => {
                    sendAttendanceRequest(
                        '{{ route('attendance.check-in') }}',
                        coords,
                        'checkInBtn',
                        'checkInText',
                        'checkInLoader'
                    );
                });
            });
        }

        if (checkOutBtn) {
            checkOutBtn.addEventListener('click', function() {
                getLocation((coords) => {
                    sendAttendanceRequest(
                        '{{ route('attendance.check-out') }}',
                        coords,
                        'checkOutBtn',
                        'checkOutText',
                        'checkOutLoader'
                    );
                });
            });
        }
    </script>
    @endpush
</x-app-layout>
