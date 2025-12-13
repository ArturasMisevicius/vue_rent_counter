<x-filament-panels::page>
    <div class="mobile-reading-interface">
        <!-- Connection Status Banner -->
        <div x-data="{ 
            isOnline: navigator.onLine,
            init() {
                window.addEventListener('online', () => this.isOnline = true);
                window.addEventListener('offline', () => this.isOnline = false);
                this.updateConnectionStatus();
            },
            updateConnectionStatus() {
                $wire.isOffline = !this.isOnline;
            }
        }" 
        x-init="init()"
        class="mb-4">
            <div x-show="!isOnline" 
                 class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-yellow-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">Offline Mode</span>
                </div>
                <p class="mt-1 text-sm">Readings will be saved locally and synced when connection is restored.</p>
            </div>
        </div>

        <!-- GPS Location Component -->
        <div x-data="gpsLocation()" 
             x-init="init()"
             class="mb-4">
            <div x-show="gpsStatus === 'requesting'" 
                 class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-blue-800">
                <div class="flex items-center">
                    <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Getting GPS location...</span>
                </div>
            </div>

            <div x-show="gpsStatus === 'success'" 
                 class="bg-green-50 border border-green-200 rounded-lg p-3 text-green-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Location verified (±<span x-text="accuracy"></span>m)</span>
                </div>
            </div>

            <div x-show="gpsStatus === 'error'" 
                 class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Location unavailable</span>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form wire:submit="submitReading">
            {{ $this->form }}

            <!-- Submit Button -->
            <div class="mt-6 flex flex-col space-y-3">
                <x-filament::button 
                    type="submit" 
                    size="lg" 
                    class="w-full justify-center"
                    :disabled="!$selectedMeter">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Reading
                </x-filament::button>

                @if(!empty($cachedReadings))
                    <x-filament::button 
                        wire:click="syncOfflineReadings"
                        color="info"
                        size="lg" 
                        class="w-full justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Sync {{ count($cachedReadings) }} Offline Readings
                    </x-filament::button>
                @endif
            </div>
        </form>

        <!-- Camera Integration for Photo OCR -->
        <div x-data="cameraCapture()" 
             x-show="$wire.data.input_method === 'photo_ocr'"
             class="mt-6">
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-3">Camera Capture</h3>
                
                <!-- Camera Preview -->
                <div x-show="!capturedImage" class="space-y-3">
                    <video x-ref="video" 
                           class="w-full rounded-lg bg-black" 
                           style="max-height: 300px;"
                           autoplay 
                           playsinline></video>
                    
                    <div class="flex space-x-3">
                        <button type="button" 
                                @click="startCamera()" 
                                x-show="!cameraActive"
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg">
                            Start Camera
                        </button>
                        
                        <button type="button" 
                                @click="capturePhoto()" 
                                x-show="cameraActive"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg">
                            Capture Photo
                        </button>
                        
                        <button type="button" 
                                @click="stopCamera()" 
                                x-show="cameraActive"
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg">
                            Stop
                        </button>
                    </div>
                </div>

                <!-- Captured Image Preview -->
                <div x-show="capturedImage" class="space-y-3">
                    <img x-bind:src="capturedImage" 
                         class="w-full rounded-lg" 
                         style="max-height: 300px; object-fit: contain;">
                    
                    <div class="flex space-x-3">
                        <button type="button" 
                                @click="retakePhoto()" 
                                class="flex-1 bg-yellow-600 text-white px-4 py-2 rounded-lg">
                            Retake Photo
                        </button>
                        
                        <button type="button" 
                                @click="processOCR()" 
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg">
                            Process OCR
                        </button>
                    </div>
                </div>

                <!-- OCR Processing Status -->
                <div x-show="ocrProcessing" 
                     class="mt-3 text-center text-blue-600">
                    <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing OCR...
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Components -->
    <script>
        // GPS Location Component
        function gpsLocation() {
            return {
                gpsStatus: 'idle',
                latitude: null,
                longitude: null,
                accuracy: null,

                init() {
                    this.getCurrentLocation();
                },

                getCurrentLocation() {
                    if (!navigator.geolocation) {
                        this.gpsStatus = 'error';
                        return;
                    }

                    this.gpsStatus = 'requesting';

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.latitude = position.coords.latitude;
                            this.longitude = position.coords.longitude;
                            this.accuracy = Math.round(position.coords.accuracy);
                            this.gpsStatus = 'success';

                            // Update Livewire component
                            $wire.gpsLocation = {
                                latitude: this.latitude,
                                longitude: this.longitude,
                                accuracy: this.accuracy
                            };
                        },
                        (error) => {
                            console.error('GPS Error:', error);
                            this.gpsStatus = 'error';
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 300000 // 5 minutes
                        }
                    );
                }
            }
        }

        // Camera Capture Component
        function cameraCapture() {
            return {
                cameraActive: false,
                capturedImage: null,
                ocrProcessing: false,
                stream: null,

                async startCamera() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { 
                                facingMode: 'environment', // Use back camera
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            }
                        });
                        
                        this.$refs.video.srcObject = this.stream;
                        this.cameraActive = true;
                    } catch (error) {
                        console.error('Camera Error:', error);
                        alert('Unable to access camera. Please check permissions.');
                    }
                },

                capturePhoto() {
                    const video = this.$refs.video;
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0);

                    this.capturedImage = canvas.toDataURL('image/jpeg', 0.8);
                    this.stopCamera();
                },

                retakePhoto() {
                    this.capturedImage = null;
                    this.startCamera();
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.cameraActive = false;
                },

                async processOCR() {
                    if (!this.capturedImage) return;

                    this.ocrProcessing = true;

                    try {
                        // Convert data URL to blob
                        const response = await fetch(this.capturedImage);
                        const blob = await response.blob();

                        // Create FormData for upload
                        const formData = new FormData();
                        formData.append('photo', blob, 'meter-photo.jpg');

                        // Here you would send to OCR service
                        // For now, just simulate processing
                        await new Promise(resolve => setTimeout(resolve, 2000));

                        // Mock OCR result
                        const mockReading = Math.floor(Math.random() * 10000) + 1000;
                        
                        // Update form with OCR result
                        $wire.data.value = mockReading;
                        
                        alert(`OCR detected reading: ${mockReading}`);

                    } catch (error) {
                        console.error('OCR Error:', error);
                        alert('OCR processing failed. Please enter reading manually.');
                    } finally {
                        this.ocrProcessing = false;
                    }
                }
            }
        }

        // Service Worker for Offline Support
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        }

        // Auto-save form data to localStorage
        document.addEventListener('livewire:initialized', () => {
            const formData = localStorage.getItem('mobile_reading_draft');
            if (formData) {
                try {
                    const data = JSON.parse(formData);
                    $wire.data = { ...$wire.data, ...data };
                } catch (e) {
                    console.error('Failed to restore form data:', e);
                }
            }

            // Save form data on changes
            Livewire.on('form-updated', (data) => {
                localStorage.setItem('mobile_reading_draft', JSON.stringify(data));
            });

            // Clear draft on successful submission
            Livewire.on('reading-saved', () => {
                localStorage.removeItem('mobile_reading_draft');
            });
        });
    </script>

    <!-- PWA Styles for Mobile Optimization -->
    <style>
        .mobile-reading-interface {
            max-width: 100%;
            padding: 0.5rem;
        }

        @media (max-width: 640px) {
            .mobile-reading-interface {
                padding: 0.25rem;
            }
            
            /* Larger touch targets for mobile */
            button, input, select {
                min-height: 44px;
            }
            
            /* Optimize form spacing */
            .fi-section {
                margin-bottom: 1rem;
            }
            
            /* Better mobile typography */
            .fi-section-header-heading {
                font-size: 1.1rem;
            }
        }

        /* Camera preview styling */
        video {
            transform: scaleX(-1); /* Mirror for selfie-like experience */
        }

        /* Offline indicator */
        .offline-banner {
            position: sticky;
            top: 0;
            z-index: 50;
        }
    </style>
</x-filament-panels::page>