// Gestion des notifications push
class PushNotifications {
    constructor(vapidPublicKey) {
        this.vapidPublicKey = vapidPublicKey;
        this.registration = null;
        this.subscription = null;
    }

    /**
     * Initialise les notifications push
     */
    async init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push notifications not supported');
            return false;
        }

        try {
            // Attendre que le service worker soit prêt avec timeout
            this.registration = await Promise.race([
                navigator.serviceWorker.ready,
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Service worker timeout')), 10000)
                )
            ]);
            console.log('Service worker ready for push notifications');

            // Vérifier si déjà abonné
            this.subscription = await this.registration.pushManager.getSubscription();

            return true;
        } catch (error) {
            console.error('Error initializing push notifications:', error);

            // Essayer de récupérer un service worker existant
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                console.log('Found existing service worker registration');
                this.registration = registration;
                this.subscription = await this.registration.pushManager.getSubscription();
                return true;
            }

            return false;
        }
    }

    /**
     * Vérifie si les notifications sont activées
     */
    async isSubscribed() {
        if (!this.registration) {
            const initialized = await this.init();
            if (!initialized || !this.registration) {
                console.warn('Service worker not available');
                return false;
            }
        }

        try {
            this.subscription = await this.registration.pushManager.getSubscription();
            return this.subscription !== null;
        } catch (error) {
            console.error('Error checking subscription:', error);
            return false;
        }
    }

    /**
     * Demande la permission et s'abonne aux notifications
     */
    async subscribe(preferences = {}) {
        try {
            // Demander la permission
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                console.log('Notification permission denied');
                return { success: false, reason: 'permission_denied' };
            }

            // S'abonner aux push notifications
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            // Enregistrer la subscription sur le serveur
            const response = await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: this.arrayBufferToBase64(subscription.getKey('p256dh')),
                        auth: this.arrayBufferToBase64(subscription.getKey('auth'))
                    },
                    preferences: preferences
                })
            });

            const data = await response.json();

            if (response.ok) {
                this.subscription = subscription;
                console.log('Successfully subscribed to push notifications');
                return { success: true, data: data };
            } else {
                console.error('Failed to save subscription on server:', data);
                return { success: false, reason: 'server_error', error: data };
            }
        } catch (error) {
            console.error('Error subscribing to push notifications:', error);
            return { success: false, reason: 'error', error: error.message };
        }
    }

    /**
     * Se désabonner des notifications
     */
    async unsubscribe() {
        try {
            if (!this.subscription) {
                this.subscription = await this.registration.pushManager.getSubscription();
            }

            if (!this.subscription) {
                console.log('No subscription to unsubscribe from');
                return { success: true };
            }

            // Se désabonner localement
            await this.subscription.unsubscribe();

            // Informer le serveur
            const response = await fetch('/api/push/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: this.subscription.endpoint
                })
            });

            const data = await response.json();

            if (response.ok) {
                this.subscription = null;
                console.log('Successfully unsubscribed from push notifications');
                return { success: true, data: data };
            } else {
                console.error('Failed to unsubscribe on server:', data);
                return { success: false, reason: 'server_error', error: data };
            }
        } catch (error) {
            console.error('Error unsubscribing from push notifications:', error);
            return { success: false, reason: 'error', error: error.message };
        }
    }

    /**
     * Met à jour les préférences de notifications
     */
    async updatePreferences(preferences) {
        try {
            if (!this.subscription) {
                this.subscription = await this.registration.pushManager.getSubscription();
            }

            if (!this.subscription) {
                return { success: false, reason: 'not_subscribed' };
            }

            const response = await fetch('/api/push/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: this.subscription.endpoint,
                    preferences: preferences
                })
            });

            const data = await response.json();

            if (response.ok) {
                console.log('Preferences updated successfully');
                return { success: true, data: data };
            } else {
                console.error('Failed to update preferences:', data);
                return { success: false, reason: 'server_error', error: data };
            }
        } catch (error) {
            console.error('Error updating preferences:', error);
            return { success: false, reason: 'error', error: error.message };
        }
    }

    /**
     * Convertit une clé VAPID base64 URL-safe en Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Convertit un ArrayBuffer en base64
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
}

// Export pour utilisation globale
window.PushNotifications = PushNotifications;
