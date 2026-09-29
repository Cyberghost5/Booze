import { Capacitor } from '@capacitor/core';
import { Geolocation } from '@capacitor/geolocation';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { StatusBar, Style } from '@capacitor/status-bar';

/**
 * Native Bridge Service for Booze App
 * Provides seamless cross-platform integration between web browsers & Capacitor native mobile apps.
 */
export const NativeBridge = {
    /**
     * Check if app is running as a native mobile app (Android or iOS)
     */
    isNative() {
        return Capacitor.isNativePlatform();
    },

    /**
     * Get platform name ('android', 'ios', or 'web')
     */
    getPlatform() {
        return Capacitor.getPlatform();
    },

    /**
     * Configure Status Bar for native mobile screens
     */
    async configureStatusBar() {
        if (!this.isNative()) return;
        try {
            await StatusBar.setStyle({ style: Style.Dark });
            await StatusBar.setBackgroundColor({ color: '#09090b' });
        } catch (e) {
            console.warn('Native status bar configuration notice:', e);
        }
    },

    /**
     * Get Current GPS Geolocation
     * Uses Capacitor Geolocation on Native mobile devices, falls back to Navigator Geolocation on Web.
     */
    async getCurrentPosition() {
        if (this.isNative()) {
            try {
                const permissionStatus = await Geolocation.checkPermissions();
                if (permissionStatus.location !== 'granted') {
                    await Geolocation.requestPermissions();
                }
                const position = await Geolocation.getCurrentPosition({
                    enableHighAccuracy: true,
                    timeout: 10000,
                });
                return {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                };
            } catch (e) {
                console.warn('Capacitor native geolocation error, using web fallback:', e);
            }
        }

        // Web Fallback
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                return reject(new Error('Geolocation is not supported by your device or browser.'));
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    resolve({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                    });
                },
                (err) => reject(err),
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    },

    /**
     * Capture Photo via Native Camera or Photo Gallery
     */
    async takePhoto() {
        if (this.isNative()) {
            try {
                const image = await Camera.getPhoto({
                    quality: 90,
                    allowEditing: true,
                    resultType: CameraResultType.Uri,
                    source: CameraSource.Prompt,
                });
                return image.webPath;
            } catch (e) {
                console.warn('Capacitor camera capture cancelled or unavailable:', e);
                return null;
            }
        }
        return null;
    }
};
