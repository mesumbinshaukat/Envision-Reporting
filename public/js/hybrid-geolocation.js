/**
 * Hybrid Geolocation System
 * Combines multiple positioning methods for maximum accuracy in indoor/desktop environments
 * 
 * Methods used (in priority order):
 * 1. High-accuracy GPS (outdoor/mobile)
 * 2. WiFi-based positioning (Google/browser database)
 * 3. IP-based geolocation (fallback)
 * 4. Multiple reading averaging
 * 5. Kalman filtering for noise reduction
 */

class HybridGeolocation {
    constructor(options = {}) {
        this.options = {
            maxAttempts: 3,
            timeout: 15000,
            enableHighAccuracy: true,
            maximumAge: 0,
            minAccuracy: 100, // meters
            samplingInterval: 2000, // ms between samples
            ...options
        };
        
        this.readings = [];
        this.ipLocation = null;
    }

    /**
     * Normalize coordinate to 8 decimal places for consistency
     * This ensures all coordinates match the backend precision
     */
    normalizeCoordinate(coord) {
        return parseFloat(coord.toFixed(8));
    }

    /**
     * Normalize a location object's coordinates
     */
    normalizeLocation(location) {
        return {
            ...location,
            latitude: this.normalizeCoordinate(location.latitude),
            longitude: this.normalizeCoordinate(location.longitude)
        };
    }

    /**
     * Get location using multiple methods and return the most accurate
     */
    async getAccurateLocation() {
        // Only use GPS-based methods (IP geolocation is unreliable and causes CORS errors)
        const results = await Promise.allSettled([
            this.getGPSLocation(),
            this.getMultipleSamples()
        ]);

        // Combine results and pick the best
        const locations = results
            .filter(r => r.status === 'fulfilled' && r.value)
            .map(r => r.value);

        if (locations.length === 0) {
            throw new Error('Unable to determine location from GPS. Please ensure location services are enabled.');
        }

        // Return the most accurate location
        return this.selectBestLocation(locations);
    }

    /**
     * Get GPS location with high accuracy settings
     */
    getGPSLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve(this.normalizeLocation({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        method: 'GPS',
                        timestamp: position.timestamp,
                        altitude: position.coords.altitude,
                        heading: position.coords.heading,
                        speed: position.coords.speed
                    }));
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: this.options.enableHighAccuracy,
                    timeout: this.options.timeout,
                    maximumAge: this.options.maximumAge
                }
            );
        });
    }

    /**
     * Take multiple GPS samples and average them for better accuracy
     * Filters outliers and uses median for more stable results
     */
    async getMultipleSamples() {
        const samples = [];
        
        console.log(`📡 Taking ${this.options.maxAttempts} GPS samples...`);
        
        for (let i = 0; i < this.options.maxAttempts; i++) {
            try {
                const location = await this.getGPSLocation();
                samples.push(location);
                console.log(`  Sample ${i + 1}/${this.options.maxAttempts}: ${location.latitude.toFixed(8)}, ${location.longitude.toFixed(8)} (±${Math.round(location.accuracy)}m)`);
                
                // Wait between samples
                if (i < this.options.maxAttempts - 1) {
                    await this.sleep(this.options.samplingInterval);
                }
            } catch (error) {
                console.warn(`Sample ${i + 1} failed:`, error.message);
            }
        }

        if (samples.length === 0) {
            throw new Error('No samples obtained');
        }

        if (samples.length === 1) {
            return samples[0];
        }

        // Filter outliers using median absolute deviation
        const filteredSamples = this.filterOutliers(samples);
        console.log(`📊 Using ${filteredSamples.length}/${samples.length} samples after outlier removal`);

        // Use median for more stable results (less affected by outliers)
        return this.calculateMedianLocation(filteredSamples);
    }

    /**
     * Filter outliers from GPS samples using median absolute deviation
     */
    filterOutliers(samples) {
        if (samples.length <= 3) {
            return samples; // Not enough samples to filter
        }

        // Calculate median latitude and longitude
        const lats = samples.map(s => s.latitude).sort((a, b) => a - b);
        const lons = samples.map(s => s.longitude).sort((a, b) => a - b);
        const medianLat = lats[Math.floor(lats.length / 2)];
        const medianLon = lons[Math.floor(lons.length / 2)];

        // Calculate distances from median
        const distances = samples.map(s => {
            return this.calculateDistanceSimple(s.latitude, s.longitude, medianLat, medianLon);
        });

        // Calculate median absolute deviation
        const sortedDistances = [...distances].sort((a, b) => a - b);
        const mad = sortedDistances[Math.floor(sortedDistances.length / 2)];

        // Filter out samples that are too far from median (> 3 * MAD)
        const threshold = Math.max(mad * 3, 50); // At least 50m threshold
        return samples.filter((s, i) => distances[i] <= threshold);
    }

    /**
     * Calculate median location from samples
     */
    calculateMedianLocation(samples) {
        if (samples.length === 1) {
            return samples[0];
        }

        // Sort by latitude and longitude
        const lats = samples.map(s => s.latitude).sort((a, b) => a - b);
        const lons = samples.map(s => s.longitude).sort((a, b) => a - b);
        
        // Get median
        const midIndex = Math.floor(samples.length / 2);
        const medianLat = samples.length % 2 === 0 
            ? (lats[midIndex - 1] + lats[midIndex]) / 2 
            : lats[midIndex];
        const medianLon = samples.length % 2 === 0 
            ? (lons[midIndex - 1] + lons[midIndex]) / 2 
            : lons[midIndex];

        // Find best accuracy
        const bestAccuracy = Math.min(...samples.map(s => s.accuracy));

        return this.normalizeLocation({
            latitude: medianLat,
            longitude: medianLon,
            accuracy: bestAccuracy,
            method: 'Median',
            timestamp: Date.now(),
            sampleCount: samples.length
        });
    }

    /**
     * Simple distance calculation for filtering (faster than full Haversine)
     */
    calculateDistanceSimple(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = dLat * dLat + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * dLon * dLon;
        return R * Math.sqrt(a);
    }

    /**
     * Get IP-based location as fallback
     */
    async getIPLocation() {
        try {
            // Use multiple IP geolocation services
            const services = [
                'https://ipapi.co/json/',
                'https://ip-api.com/json/',
                'https://ipinfo.io/json'
            ];

            for (const service of services) {
                try {
                    const response = await fetch(service);
                    const data = await response.json();
                    
                    const lat = data.latitude || data.lat;
                    const lon = data.longitude || data.lon;
                    
                    if (lat && lon) {
                        return {
                            latitude: parseFloat(lat),
                            longitude: parseFloat(lon),
                            accuracy: 1000, // IP location is very inaccurate
                            method: 'IP',
                            timestamp: Date.now(),
                            city: data.city,
                            country: data.country || data.country_name
                        };
                    }
                } catch (error) {
                    console.warn(`IP service ${service} failed:`, error.message);
                    continue;
                }
            }
            
            throw new Error('All IP services failed');
        } catch (error) {
            console.error('IP location failed:', error);
            return null;
        }
    }

    /**
     * Calculate weighted average of multiple location samples
     * Samples with better accuracy get higher weight
     */
    calculateWeightedAverage(samples) {
        if (samples.length === 1) {
            return samples[0];
        }

        let totalWeight = 0;
        let weightedLat = 0;
        let weightedLon = 0;
        let bestAccuracy = Infinity;

        samples.forEach(sample => {
            // Weight is inverse of accuracy (better accuracy = higher weight)
            const weight = 1 / (sample.accuracy || 1);
            totalWeight += weight;
            weightedLat += sample.latitude * weight;
            weightedLon += sample.longitude * weight;
            bestAccuracy = Math.min(bestAccuracy, sample.accuracy);
        });

        return this.normalizeLocation({
            latitude: weightedLat / totalWeight,
            longitude: weightedLon / totalWeight,
            accuracy: bestAccuracy,
            method: 'Averaged',
            timestamp: Date.now(),
            sampleCount: samples.length
        });
    }

    /**
     * Select the best location from multiple sources
     */
    selectBestLocation(locations) {
        // Sort by accuracy (lower is better)
        locations.sort((a, b) => a.accuracy - b.accuracy);
        
        // Prefer GPS/Averaged over IP if accuracy is reasonable
        const gpsLocations = locations.filter(l => l.method !== 'IP');
        
        if (gpsLocations.length > 0 && gpsLocations[0].accuracy <= this.options.minAccuracy) {
            return gpsLocations[0];
        }

        // Return best available
        return locations[0];
    }

    /**
     * Watch position continuously and apply Kalman filtering
     */
    watchPosition(callback, errorCallback) {
        const kalmanFilter = new SimpleKalmanFilter();
        
        return navigator.geolocation.watchPosition(
            (position) => {
                const filtered = kalmanFilter.filter(
                    position.coords.latitude,
                    position.coords.longitude,
                    position.coords.accuracy
                );
                
                callback({
                    ...position,
                    coords: {
                        ...position.coords,
                        latitude: filtered.latitude,
                        longitude: filtered.longitude,
                        accuracy: filtered.accuracy
                    },
                    filtered: true
                });
            },
            errorCallback,
            {
                enableHighAccuracy: true,
                timeout: this.options.timeout,
                maximumAge: 0
            }
        );
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

/**
 * Simple Kalman Filter for smoothing GPS readings
 */
class SimpleKalmanFilter {
    constructor() {
        this.variance = -1;
        this.minAccuracy = 1;
    }

    filter(lat, lon, accuracy) {
        if (accuracy < this.minAccuracy) {
            accuracy = this.minAccuracy;
        }

        if (this.variance < 0) {
            // First reading
            this.variance = accuracy * accuracy;
            this.lat = lat;
            this.lon = lon;
            this.accuracy = accuracy;
        } else {
            // Kalman gain
            const gain = this.variance / (this.variance + accuracy * accuracy);
            
            // Update estimates
            this.lat = this.lat + gain * (lat - this.lat);
            this.lon = this.lon + gain * (lon - this.lon);
            this.variance = (1 - gain) * this.variance;
            this.accuracy = Math.sqrt(this.variance);
        }

        return {
            latitude: this.lat,
            longitude: this.lon,
            accuracy: this.accuracy
        };
    }

    reset() {
        this.variance = -1;
    }
}

// Export for use in other scripts
window.HybridGeolocation = HybridGeolocation;
