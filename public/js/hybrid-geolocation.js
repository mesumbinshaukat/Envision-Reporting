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
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        method: 'GPS',
                        timestamp: position.timestamp,
                        altitude: position.coords.altitude,
                        heading: position.coords.heading,
                        speed: position.coords.speed
                    });
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
     */
    async getMultipleSamples() {
        const samples = [];
        
        for (let i = 0; i < this.options.maxAttempts; i++) {
            try {
                const location = await this.getGPSLocation();
                samples.push(location);
                
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

        // Filter out poor accuracy readings
        const goodSamples = samples.filter(s => s.accuracy <= this.options.minAccuracy);
        const samplesToUse = goodSamples.length > 0 ? goodSamples : samples;

        // Calculate weighted average (weight by accuracy)
        return this.calculateWeightedAverage(samplesToUse);
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

        return {
            latitude: weightedLat / totalWeight,
            longitude: weightedLon / totalWeight,
            accuracy: bestAccuracy,
            method: 'Averaged',
            timestamp: Date.now(),
            sampleCount: samples.length
        };
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
