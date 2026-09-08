import React, { useEffect, useState } from 'react';
import { NavigationContainer, createNavigationContainerRef } from '@react-navigation/native';
import { StatusBar, SafeAreaView } from 'react-native';
import { RootNavigator } from './src/navigation/RootNavigator';
import { offlineStorage } from './src/services/offlineStorage';
import { pushNotificationService } from './src/services/pushNotification';
import { User } from './src/types';

export const navigationRef = createNavigationContainerRef<any>();

export default function App() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const bootstrap = async () => {
      try {
        const savedUser = await offlineStorage.getUserProfile();
        if (savedUser) {
          setUser(savedUser);
        }

        // Setup notification navigation deep linking
        pushNotificationService.setNavigationHandler((incidentId, latitude, longitude) => {
          if (navigationRef.isReady()) {
            navigationRef.navigate('LiveMap', { incidentId, latitude, longitude });
          }
        });
      } catch (e) {
        console.error('App bootstrap error:', e);
      } finally {
        setLoading(false);
      }
    };

    bootstrap();
  }, []);

  if (loading) {
    return null;
  }

  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: '#080C14' }}>
      <StatusBar barStyle="light-content" backgroundColor="#080C14" />
      <NavigationContainer ref={navigationRef}>
        <RootNavigator
          user={user}
          onLoginSuccess={(loggedInUser) => setUser(loggedInUser)}
        />
      </NavigationContainer>
    </SafeAreaView>
  );
}
