import messaging, { FirebaseMessagingTypes } from '@react-native-firebase/messaging';
import { Alert, Platform } from 'react-native';

export type NotificationNavigationCallback = (incidentId: number, lat: number, lng: number) => void;

let onNavigateCallback: NotificationNavigationCallback | null = null;

export const pushNotificationService = {
  setNavigationHandler: (callback: NotificationNavigationCallback) => {
    onNavigateCallback = callback;
  },

  init: async (): Promise<string | null> => {
    try {
      const authStatus = await messaging().requestPermission();
      const enabled =
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

      if (!enabled) {
        console.warn('FCM bildirim izni verilmedi.');
        return null;
      }

      const fcmToken = await messaging().getToken();

      // Foreground Message Handler
      messaging().onMessage(async (remoteMessage: FirebaseMessagingTypes.RemoteMessage) => {
        const { notification, data } = remoteMessage;
        if (data?.type === 'FIRE_INCIDENT_ALERT') {
          Alert.alert(
            notification?.title || '🚨 KRİTİK YANGIN ALARMI',
            notification?.body || 'Konum sistem tarafından otomatik belirlendi.',
            [
              { text: 'Kapat', style: 'cancel' },
              {
                text: 'OLAYI GÖR & HARİTAYA GİT',
                onPress: () => {
                  if (data?.incident_id && onNavigateCallback) {
                    onNavigateCallback(
                      parseInt(data.incident_id, 10),
                      parseFloat(data.latitude || '39.7190'),
                      parseFloat(data.longitude || '26.8710')
                    );
                  }
                },
              },
            ]
          );
        }
      });

      // Notification Opened App from Background
      messaging().onNotificationOpenedApp((remoteMessage) => {
        const data = remoteMessage.data;
        if (data?.incident_id && onNavigateCallback) {
          onNavigateCallback(
            parseInt(data.incident_id, 10),
            parseFloat(data.latitude || '39.7190'),
            parseFloat(data.longitude || '26.8710')
          );
        }
      });

      return fcmToken;
    } catch (e) {
      console.error('FCM init error:', e);
      return null;
    }
  },
};
