<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-navy-900" style="padding-right: 15px !important;">Office Location Settings</h2>
    </x-slot>

    <div class="space-y-6">
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

        <div id="dynamicMessage" class="hidden px-4 py-3 rounded"></div>

        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">Configure Office Location</h3>
            <p class="text-gray-600 mb-6">
                Set your office location to enable geolocation-based attendance tracking. Employees must be within the specified radius to check in/out.
            </p>

            <form method="POST" action="{{ route('admin.office-location.update') }}" id="locationForm">
                @csrf

                <div class="bg-blue-50 border border-blue-300 rounded p-4 mb-6">
                    <h4 class="font-semibold text-blue-900 mb-2">📍 How to Set Accurate Office Location:</h4>
                    <div class="text-sm text-blue-800 space-y-2">
                        <p><strong>⭐ RECOMMENDED METHOD (Most Accurate):</strong></p>
                        <ol class="list-decimal list-inside ml-4 space-y-1">
                            <li>Open <a href="https://www.google.com/maps" target="_blank" class="underline font-semibold">Google Maps</a> on your phone or computer</li>
                            <li>Find your exact office location on the map</li>
                            <li>Long-press (mobile) or right-click (desktop) on the location</li>
                            <li>Copy the coordinates (e.g., "24.959242, 67.057241")</li>
                            <li>Paste them in the fields below</li>
                            <li>Click "Save Settings"</li>
                        </ol>
                        <p class="mt-2"><strong>Alternative:</strong> Click "Use Mobile GPS" button below (requires good GPS signal - works best on mobile phones near windows)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="office_latitude" class="block text-sm font-semibold text-navy-900 mb-1">
                            Latitude * <span class="text-xs text-gray-500">(Accurate Office Location)</span>
                        </label>
                        <input 
                            type="number" 
                            name="office_latitude" 
                            id="office_latitude" 
                            step="0.00000001"
                            value="{{ old('office_latitude', $user->office_latitude) }}" 
                            required 
                            class="w-full px-4 py-2 border border-navy-900 rounded @error('office_latitude') border-red-500 @enderror"
                            placeholder="e.g., 24.95924180"
                        >
                        @error('office_latitude')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="office_longitude" class="block text-sm font-semibold text-navy-900 mb-1">
                            Longitude * <span class="text-xs text-gray-500">(Accurate Office Location)</span>
                        </label>
                        <input 
                            type="number" 
                            name="office_longitude" 
                            id="office_longitude" 
                            step="0.00000001"
                            value="{{ old('office_longitude', $user->office_longitude) }}" 
                            required 
                            class="w-full px-4 py-2 border border-navy-900 rounded @error('office_longitude') border-red-500 @enderror"
                            placeholder="e.g., 67.05724110"
                        >
                        @error('office_longitude')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="office_radius_meters" class="block text-sm font-semibold text-navy-900 mb-1">
                        Allowed Radius (meters) *
                    </label>
                    <input 
                        type="number" 
                        name="office_radius_meters" 
                        id="office_radius_meters" 
                        min="5"
                        max="1000"
                        value="{{ old('office_radius_meters', $user->office_radius_meters ?? 15) }}" 
                        required 
                        class="w-full px-4 py-2 border border-navy-900 rounded @error('office_radius_meters') border-red-500 @enderror"
                    >
                    <p class="text-sm text-gray-600 mt-1">
                        Employees must be within this distance to check in/out (recommended: 15-50 meters)
                    </p>
                    @error('office_radius_meters')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hidden fields for GPS calibration data -->
                <input type="hidden" name="gps_latitude" id="gps_latitude">
                <input type="hidden" name="gps_longitude" id="gps_longitude">
                <input type="hidden" name="gps_accuracy" id="gps_accuracy">

                <div class="flex gap-4 mb-6">
                    <button 
                        type="button" 
                        id="calibrateBtn"
                        style="background-color: #9333ea !important; color: white !important;"
                        class="px-6 py-2 rounded hover:opacity-90 font-semibold"
                    >
                        <span id="calibrateBtnText">🎯 Calibrate with Desktop GPS</span>
                        <span id="calibrateBtnLoader" class="hidden">
                            <svg class="animate-spin inline h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <circle class="opacity-75" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-dasharray="32" stroke-dashoffset="32"></circle>
                            </svg>
                            Calibrating...
                        </span>
                    </button>
                    <button 
                        type="button" 
                        id="getCurrentLocationBtn"
                        style="background-color: #2563eb !important; color: white !important;"
                        class="px-6 py-2 rounded hover:opacity-90 font-semibold"
                    >
                        <span id="locationBtnText">📱 Use Mobile GPS</span>
                        <span id="locationBtnLoader" class="hidden">
                            <svg class="animate-spin inline h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <circle class="opacity-75" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-dasharray="32" stroke-dashoffset="32"></circle>
                            </svg>
                            Getting location...
                        </span>
                    </button>
                </div>
                
                <p class="text-xs text-gray-600 mb-6">
                    💡 <strong>Tip:</strong> "Use Mobile GPS" gets your current location. "Calibrate" creates correction data for desktop users based on the office coordinates you entered above.
                </p>

                <div class="flex justify-end mt-6">
                    <button 
                        type="submit" 
                        style="background-color: #16a34a !important; color: white !important;"
                        class="px-8 py-3 rounded hover:opacity-90 font-semibold text-lg"
                    >
                        💾 Save Settings
                    </button>
                </div>

                @if($user->office_latitude && $user->office_longitude)
                    <div class="bg-blue-50 border border-blue-200 rounded p-4">
                        <h4 class="font-semibold text-navy-900 mb-2">Current Office Location</h4>
                        <p class="text-sm text-gray-700">
                            <strong>Coordinates:</strong> {{ number_format($user->office_latitude, 6) }}, {{ number_format($user->office_longitude, 6) }}<br>
                            <strong>Radius:</strong> {{ $user->office_radius_meters }} meters
                        </p>
                        <a 
                            href="https://www.google.com/maps?q={{ $user->office_latitude }},{{ $user->office_longitude }}" 
                            target="_blank"
                            class="text-blue-600 hover:underline text-sm mt-2 inline-block"
                        >
                            View on Google Maps →
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <div class="bg-white border border-navy-900 rounded-lg p-6">
            <h3 class="text-xl font-bold text-navy-900 mb-4">How It Works</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>Employees must enable location services in their browser</li>
                <li>When checking in/out, their location is verified against the office location</li>
                <li>If they are outside the allowed radius, check-in/out will be denied</li>
                <li>All attempts (successful and failed) are logged with IP address and device information</li>
                <li>Logs are only visible to administrators</li>
            </ul>
        </div>
    </div>

    @push('scripts')
    <script>
        // Initialize positioning systems with more samples for admin setup
        const hybridGeo = new HybridGeolocation({
            maxAttempts: 7, // Take 7 samples for better accuracy
            timeout: 20000,
            minAccuracy: 100, // Accept readings up to 100m
            samplingInterval: 2000 // 2 seconds between samples
        });
        
        const wifiPositioning = new WiFiPositioning();

        const getCurrentLocationBtn = document.getElementById('getCurrentLocationBtn');
        const locationBtnText = document.getElementById('locationBtnText');
        const locationBtnLoader = document.getElementById('locationBtnLoader');
        const calibrateBtn = document.getElementById('calibrateBtn');
        const calibrateBtnText = document.getElementById('calibrateBtnText');
        const calibrateBtnLoader = document.getElementById('calibrateBtnLoader');
        const latitudeInput = document.getElementById('office_latitude');
        const longitudeInput = document.getElementById('office_longitude');
        const dynamicMessage = document.getElementById('dynamicMessage');

        function showMessage(message, type = 'success') {
            dynamicMessage.className = `px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'}`;
            dynamicMessage.textContent = message;
            dynamicMessage.classList.remove('hidden');
            
            setTimeout(() => {
                dynamicMessage.classList.add('hidden');
            }, 5000);
        }

        // Calibrate button - gets desktop GPS and creates calibration based on entered office coordinates
        calibrateBtn.addEventListener('click', async function() {
            // Check if office coordinates are entered
            if (!latitudeInput.value || !longitudeInput.value) {
                showMessage('Please enter the accurate office coordinates first!', 'error');
                return;
            }

            if (!navigator.geolocation) {
                showMessage('Geolocation is not supported by your browser.', 'error');
                return;
            }

            calibrateBtn.disabled = true;
            calibrateBtnText.classList.add('hidden');
            calibrateBtnLoader.classList.remove('hidden');

            try {
                showMessage('Getting desktop GPS reading for calibration...', 'success');
                
                // Get raw desktop GPS reading
                const rawGPS = await hybridGeo.getGPSLocation();
                console.log('Desktop GPS reading:', rawGPS);
                
                // Store raw GPS for calibration
                document.getElementById('gps_latitude').value = rawGPS.latitude.toFixed(8);
                document.getElementById('gps_longitude').value = rawGPS.longitude.toFixed(8);
                document.getElementById('gps_accuracy').value = rawGPS.accuracy.toFixed(2);
                
                // Calculate offset
                const knownLat = parseFloat(latitudeInput.value);
                const knownLon = parseFloat(longitudeInput.value);
                const offsetLat = knownLat - rawGPS.latitude;
                const offsetLon = knownLon - rawGPS.longitude;
                
                console.log('=== CALIBRATION DEBUG ===');
                console.log('Known Office Coords:', knownLat, knownLon);
                console.log('Raw GPS Coords:', rawGPS.latitude, rawGPS.longitude);
                console.log('Calculated Offset:', offsetLat, offsetLon);
                console.log('Hidden Field Values:');
                console.log('  gps_latitude:', document.getElementById('gps_latitude').value);
                console.log('  gps_longitude:', document.getElementById('gps_longitude').value);
                console.log('  office_latitude:', latitudeInput.value);
                console.log('  office_longitude:', longitudeInput.value);
                console.log('========================');
                
                showMessage(
                    `✅ Calibration data captured!\n` +
                    `Desktop GPS: ${rawGPS.latitude.toFixed(6)}, ${rawGPS.longitude.toFixed(6)}\n` +
                    `Office Location: ${knownLat.toFixed(6)}, ${knownLon.toFixed(6)}\n` +
                    `Offset: ${offsetLat.toFixed(6)}, ${offsetLon.toFixed(6)}\n` +
                    `Now click "Save Settings" to apply calibration.`,
                    'success'
                );
                
            } catch (error) {
                let errorMessage = 'Unable to get GPS reading.';
                
                if (error.code) {
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location permission denied. Please enable location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out.';
                            break;
                    }
                } else {
                    errorMessage = error.message || errorMessage;
                }
                
                showMessage(errorMessage, 'error');
            } finally {
                calibrateBtn.disabled = false;
                calibrateBtnText.classList.remove('hidden');
                calibrateBtnLoader.classList.add('hidden');
            }
        });

        // Use current location button - for mobile/accurate GPS
        getCurrentLocationBtn.addEventListener('click', async function() {
            if (!navigator.geolocation) {
                showMessage('Geolocation is not supported by your browser.', 'error');
                return;
            }

            getCurrentLocationBtn.disabled = true;
            locationBtnText.classList.add('hidden');
            locationBtnLoader.classList.remove('hidden');

            try {
                showMessage('📡 Taking multiple GPS samples (this will take 15-20 seconds)... Please stay still.', 'success');
                
                // Get multiple samples for best accuracy
                const location = await hybridGeo.getAccurateLocation();
                console.log('Final location:', location);
                
                // Warn if accuracy is poor
                if (location.accuracy > 100) {
                    showMessage(
                        `⚠️ GPS accuracy is poor (±${Math.round(location.accuracy)}m). ` +
                        `For best results:\n` +
                        `1. Use a mobile phone instead of desktop\n` +
                        `2. Move near a window or go outside\n` +
                        `3. Or manually enter coordinates from Google Maps`,
                        'error'
                    );
                    getCurrentLocationBtn.disabled = false;
                    locationBtnText.classList.remove('hidden');
                    locationBtnLoader.classList.add('hidden');
                    return;
                }
                
                // Get single raw GPS reading for calibration baseline
                const rawGPS = await hybridGeo.getGPSLocation();
                
                // Store raw GPS for calibration
                document.getElementById('gps_latitude').value = rawGPS.latitude.toFixed(8);
                document.getElementById('gps_longitude').value = rawGPS.longitude.toFixed(8);
                document.getElementById('gps_accuracy').value = rawGPS.accuracy.toFixed(2);
                
                // Set the improved coordinates as the known office location
                latitudeInput.value = location.latitude.toFixed(8);
                longitudeInput.value = location.longitude.toFixed(8);
                
                // Create calibration point for future use
                try {
                    await wifiPositioning.addCalibrationPoint(
                        location.latitude,
                        location.longitude,
                        'Office - Admin Set'
                    );
                    console.log('Calibration point created');
                } catch (calibError) {
                    console.warn('Failed to create calibration point:', calibError);
                }
                
                showMessage(
                    `✅ Location captured! Method: ${location.method}, Accuracy: ${Math.round(location.accuracy)}m` +
                    (location.sampleCount ? ` (${location.sampleCount} samples averaged)` : ''),
                    'success'
                );
                
            } catch (error) {
                let errorMessage = 'Unable to retrieve your location.';
                
                if (error.code) {
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location permission denied. Please enable location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information is unavailable. Try moving near a window.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out. Please try again.';
                            break;
                    }
                } else {
                    errorMessage = error.message || errorMessage;
                }
                
                showMessage(errorMessage, 'error');
            } finally {
                getCurrentLocationBtn.disabled = false;
                locationBtnText.classList.remove('hidden');
                locationBtnLoader.classList.add('hidden');
            }
        });
    </script>
    @endpush
</x-app-layout>
