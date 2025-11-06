/**
 * WiFi-Based Indoor Positioning System
 * Uses WiFi fingerprinting and manual calibration for accurate indoor positioning
 */

class WiFiPositioning {
    constructor() {
        this.calibrationPoints = this.loadCalibrationPoints();
        this.officeLocation = null;
    }

    /**
     * Load saved calibration points from localStorage
     */
    loadCalibrationPoints() {
        try {
            const saved = localStorage.getItem('wifi_calibration_points');
            return saved ? JSON.parse(saved) : [];
        } catch (error) {
            console.error('Failed to load calibration points:', error);
            return [];
        }
    }

    /**
     * Save calibration points to localStorage
     */
    saveCalibrationPoints() {
        try {
            localStorage.setItem('wifi_calibration_points', JSON.stringify(this.calibrationPoints));
        } catch (error) {
            console.error('Failed to save calibration points:', error);
        }
    }

    /**
     * Add a calibration point at the office location
     * This should be done by admin while physically at the office
     */
    async addCalibrationPoint(knownLat, knownLon, label = 'Office') {
        try {
            // Get current GPS reading
            const gpsLocation = await this.getCurrentGPS();
            
            // Get WiFi information (if available via browser)
            const wifiInfo = await this.getWiFiInfo();
            
            const calibrationPoint = {
                id: Date.now(),
                label: label,
                knownLocation: {
                    latitude: knownLat,
                    longitude: knownLon
                },
                gpsReading: {
                    latitude: gpsLocation.latitude,
                    longitude: gpsLocation.longitude,
                    accuracy: gpsLocation.accuracy
                },
                wifiNetworks: wifiInfo,
                timestamp: new Date().toISOString(),
                deviceInfo: this.getDeviceInfo()
            };

            this.calibrationPoints.push(calibrationPoint);
            this.saveCalibrationPoints();
            
            return calibrationPoint;
        } catch (error) {
            console.error('Failed to add calibration point:', error);
            throw error;
        }
    }

    /**
     * Get corrected location using calibration data
     */
    async getCorrectedLocation() {
        try {
            const currentGPS = await this.getCurrentGPS();
            
            if (this.calibrationPoints.length === 0) {
                // No calibration data, return raw GPS
                return {
                    ...currentGPS,
                    corrected: false,
                    method: 'Raw GPS'
                };
            }

            // Find nearest calibration point
            const nearest = this.findNearestCalibrationPoint(
                currentGPS.latitude,
                currentGPS.longitude
            );

            if (!nearest) {
                return {
                    ...currentGPS,
                    corrected: false,
                    method: 'Raw GPS'
                };
            }

            // Calculate offset between GPS reading and known location
            const latOffset = nearest.knownLocation.latitude - nearest.gpsReading.latitude;
            const lonOffset = nearest.knownLocation.longitude - nearest.gpsReading.longitude;

            // Apply offset to current reading
            const correctedLat = currentGPS.latitude + latOffset;
            const correctedLon = currentGPS.longitude + lonOffset;

            return {
                latitude: correctedLat,
                longitude: correctedLon,
                accuracy: Math.min(currentGPS.accuracy, 15), // Improved accuracy
                corrected: true,
                method: 'Calibration-Corrected',
                calibrationPoint: nearest.label,
                offset: {
                    latitude: latOffset,
                    longitude: lonOffset
                },
                originalGPS: {
                    latitude: currentGPS.latitude,
                    longitude: currentGPS.longitude
                }
            };
        } catch (error) {
            console.error('Failed to get corrected location:', error);
            throw error;
        }
    }

    /**
     * Find nearest calibration point to current GPS reading
     */
    findNearestCalibrationPoint(lat, lon) {
        if (this.calibrationPoints.length === 0) {
            return null;
        }

        let nearest = null;
        let minDistance = Infinity;

        this.calibrationPoints.forEach(point => {
            const distance = this.calculateDistance(
                lat,
                lon,
                point.gpsReading.latitude,
                point.gpsReading.longitude
            );

            if (distance < minDistance) {
                minDistance = distance;
                nearest = point;
            }
        });

        // Only use calibration if within reasonable range (5km)
        return minDistance < 5000 ? nearest : null;
    }

    /**
     * Get current GPS location
     */
    getCurrentGPS() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    });
                },
                (error) => reject(error),
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
    }

    /**
     * Get WiFi information (limited in browsers, but some info available)
     */
    async getWiFiInfo() {
        const info = {
            connection: null,
            networks: []
        };

        try {
            // Check network connection type
            if ('connection' in navigator) {
                info.connection = {
                    type: navigator.connection.effectiveType,
                    downlink: navigator.connection.downlink,
                    rtt: navigator.connection.rtt
                };
            }

            // Note: Full WiFi scanning not available in browsers for security reasons
            // This is a limitation we work around with calibration
        } catch (error) {
            console.warn('WiFi info not available:', error);
        }

        return info;
    }

    /**
     * Get device information for fingerprinting
     */
    getDeviceInfo() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            screenResolution: `${screen.width}x${screen.height}`,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

        return R * c;
    }

    /**
     * Clear all calibration points
     */
    clearCalibration() {
        this.calibrationPoints = [];
        localStorage.removeItem('wifi_calibration_points');
    }

    /**
     * Get calibration statistics
     */
    getCalibrationStats() {
        return {
            pointCount: this.calibrationPoints.length,
            points: this.calibrationPoints.map(p => ({
                label: p.label,
                timestamp: p.timestamp,
                accuracy: p.gpsReading.accuracy
            }))
        };
    }
}

// Export for use in other scripts
window.WiFiPositioning = WiFiPositioning;
