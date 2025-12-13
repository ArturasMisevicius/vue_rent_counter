<x-filament-panels::page>
    <div class="mobile-reading-container">
        <!-- Offline Status Banner -->
        <div x-data="{ offline: @entangle('offlineMode') }" 
             x-show="offline" 
             class="mb-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg">
            <div class="flex items-center">
                <x-heroicon-o-wifi-slash class="w-5 h-5 mr-2" />
                <span class="font-medium">Offline Mode Active</span>
            </div>
            <p class="text-sm mt-1">Data will be synced when you go back online.</p>
        </div>

        <!-- GPS Status -->
        <div x-data="gpsTracker()" x-init="initGPS()" class="mb-4">
            <div x-show="gpsStatus !== 'hidden'" 
                 class="p-3 rounded-lg border"
                 :class="{
                     'bg-green-100 border-green-400 text-green-700': gpsStatus === 'success',
                     'bg-yellow-100 border-yellow-400 text-yellow-700': gpsStatus === 'loading',
                     'bg-red-100 border-red-400 text-red-700': gpsStatus === 'error'
                 }">
                <div class="flex items-center">
                    <x-heroicon-o-map-pin class="w-5 h-5 mr-2" />
                    <span x-text="gpsMessage" class="font-medium"></span>
                </div>
                <div x-show="coordinates" class="text-sm mt-1">
                    <span x-text="'Lat: ' + (coordinates?.latitude || 'N/A')"></span>,
                    <span x-text="'Lng: ' + (coordinates?.longitude || 'N/A')"></span>
                    <span x-show="accuracy" x-text="'(±' + accuracy + 'm)'"></span>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form wire:submit="saveReading">
            {{ $this->form }}
            
            <div class="mt-6 space-y-3">
                {{ $this->saveAction }}
                {{ $this->saveAndNextAction }}
                {{ $this->clearAction }}
            </div>
        </form>

        <!-- Camera Integration -->
        <div x-data="cameraCapture()" class="mt-6">
            <div x-show="showCamera" class="fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center">
                <div class="bg-white rounded-lg p-4 max-w-sm w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Capture Meter Photo</h3>
                        <button @click="closeCamera()" type="button" class="text-gray-500 hover:text-gray-700">
                            <x-heroicon-o-x-mark class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <video x-ref="video" class="w-full rounded-lg mb-4" autoplay playsinline></video>
                    <canvas x-ref="canvas" class="hidden"></canvas>
                    
                    <div class="flex space-x-2">
                        <button @click="capturePhoto()" type="button" 
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                            <x-heroicon-o-camera class="w-5 h-5 inline mr-2" />
                            Capture
                        </button>
                        <button @click="closeCamera()" type="button" 
                                class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offline Data Queue -->
        <div x-data="offlineQueue()" x-init="initOfflineQueue()" class="mt-6">
            <div x-show="queueCount > 0" class="p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <x-heroicon-o-queue-list class="w-5 h-5 mr-2" />
                        <span x-text="queueCount + ' readings queued for sync'"></span>
                    </div>
                    <button @click="syncQueue()" type="button" 
                            class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                        Sync Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .mobile-reading-container {
            max-width: 100%;
            padding: 1rem;
        }

        /* Mobile-optimized form controls */
        .mobile-input-large input,
        .mobile-select-large select,
        .mobile-textarea-large textarea {
            font-size: 16px !important; /* Prevents zoom on iOS */
            padding: 12px !important;
            min-height: 48px !important;
        }

        .mobile-numeric input {
            text-align: right;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
        }

        .mobile-button-large {
            min-height: 48px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
        }

        .mobile-toggle-large {
            transform: scale(1.2);
        }

        .mobile-file-upload {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #f9fafb;
        }

        .mobile-gps-status {
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 14px;
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .mobile-reading-container {
                padding: 0.5rem;
            }
            
            .fi-section-content {
                padding: 1rem !important;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .mobile-file-upload {
                background-color: #374151;
                border-color: #6b7280;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        // GPS Tracking Component
        function gpsTracker() {
            return {
                gpsStatus: 'hidden',
                gpsMessage: '',
                coordinates: null,
                accuracy: null,
                watchId: null,

                initGPS() {
                    if (!navigator.geolocation) {
                        this.gpsStatus = 'error';
                        this.gpsMessage = 'GPS not supported';
                        return;
                    }

                    this.gpsStatus = 'loading';
                    this.gpsMessage = 'Getting location...';

                    const options = {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    };

                    this.watchId = navigator.geolocation.watchPosition(
                        (position) => {
                            this.coordinates = {
                                latitude: position.coords.latitude.toFixed(6),
                                longitude: position.coords.longitude.toFixed(6)
                            };
                            this.accuracy = Math.round(position.coords.accuracy);
                            this.gpsStatus = 'success';
                            this.gpsMessage = 'Location acquired';
                            
                            // Send coordinates to Livewire component
                            @this.set('gpsLocation', this.coordinates);
                        },
                        (error) => {
                            this.gpsStatus = 'error';
                            this.gpsMessage = this.getGPSErrorMessage(error);
                        },
                        options
                    );
                },

                getGPSErrorMessage(error) {
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            return 'Location access denied';
                        case error.POSITION_UNAVAILABLE:
                            return 'Location unavailable';
                        case error.TIMEOUT:
                            return 'Location timeout';
                        default:
                            return 'Location error';
                    }
                },

                destroy() {
                    if (this.watchId) {
                        navigator.geolocation.clearWatch(this.watchId);
                    }
                }
            }
        }

        // Camera Capture Component
        function cameraCapture() {
            return {
                showCamera: false,
                stream: null,

                async openCamera() {
                    this.showCamera = true;
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { 
                                facingMode: 'environment',
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            }
                        });
                        this.$refs.video.srcObject = this.stream;
                    } catch (error) {
                        console.error('Camera access error:', error);
                        alert('Could not access camera: ' + error.message);
                        this.closeCamera();
                    }
                },

                capturePhoto() {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    const context = canvas.getContext('2d');

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0);

                    canvas.toBlob((blob) => {
                        const file = new File([blob], 'meter-photo.jpg', { type: 'image/jpeg' });
                        // Trigger file upload in Filament
                        const event = new CustomEvent('photo-captured', { detail: { file } });
                        document.dispatchEvent(event);
                    }, 'image/jpeg', 0.8);

                    this.closeCamera();
                },

                closeCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.showCamera = false;
                }
            }
        }

        // Offline Queue Management
        function offlineQueue() {
            return {
                queueCount: 0,
                
                initOfflineQueue() {
                    this.updateQueueCount();
                    
                    // Listen for offline mode changes
                    document.addEventListener('offline-mode-toggled', (event) => {
                        if (!event.detail.offline) {
                            this.syncQueue();
                        }
                    });
                },

                updateQueueCount() {
                    const queue = JSON.parse(localStorage.getItem('offline_readings') || '[]');
                    this.queueCount = queue.length;
                },

                async syncQueue() {
                    const queue = JSON.parse(localStorage.getItem('offline_readings') || '[]');
                    
                    if (queue.length === 0) return;

                    try {
                        for (const reading of queue) {
                            await @this.call('saveOfflineReading', reading);
                        }
                        
                        localStorage.removeItem('offline_readings');
                        this.queueCount = 0;
                        
                        // Show success notification
                        window.dispatchEvent(new CustomEvent('sync-complete', {
                            detail: { count: queue.length }
                        }));
                        
                    } catch (error) {
                        console.error('Sync failed:', error);
                        alert('Sync failed: ' + error.message);
                    }
                },

                addToQueue(reading) {
                    const queue = JSON.parse(localStorage.getItem('offline_readings') || '[]');
                    queue.push({
                        ...reading,
                        timestamp: Date.now(),
                        id: 'offline_' + Date.now()
                    });
                    localStorage.setItem('offline_readings', JSON.stringify(queue));
                    this.updateQueueCount();
                }
            }
        }

        // Service Worker Registration for Offline Support
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        }

        // Handle network status changes
        window.addEventListener('online', () => {
            document.dispatchEvent(new CustomEvent('network-status-changed', { 
                detail: { online: true } 
            }));
        });

        window.addEventListener('offline', () => {
            document.dispatchEvent(new CustomEvent('network-status-changed', { 
                detail: { online: false } 
            }));
        });
    </script>
    @endpush
</x-filament-panels::page>