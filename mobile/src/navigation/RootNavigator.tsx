import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Text } from 'react-native';

import { LoginScreen } from '../screens/LoginScreen';
import { HomeScreen } from '../screens/HomeScreen';
import { LiveMapScreen } from '../screens/LiveMapScreen';
import { IncidentDetailScreen } from '../screens/IncidentDetailScreen';
import { CameraStreamScreen } from '../screens/CameraStreamScreen';
import { SensorsScreen } from '../screens/SensorsScreen';
import { HistoryScreen } from '../screens/HistoryScreen';
import { User } from '../types';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

const MainTabs: React.FC<{ user: User | null }> = ({ user }) => {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          backgroundColor: '#0F172A',
          borderTopColor: '#1E293B',
          height: 60,
          paddingBottom: 8,
          paddingTop: 8,
        },
        tabBarActiveTintColor: '#EF4444',
        tabBarInactiveTintColor: '#64748B',
      }}
    >
      <Tab.Screen
        name="HomeTab"
        options={{
          tabBarLabel: 'Ana Sayfa',
          tabBarIcon: () => <Text style={{ fontSize: 18 }}>🏠</Text>,
        }}
      >
        {(props) => <HomeScreen {...props} user={user} />}
      </Tab.Screen>

      <Tab.Screen
        name="MapTab"
        component={LiveMapScreen}
        options={{
          tabBarLabel: 'Canlı Harita',
          tabBarIcon: () => <Text style={{ fontSize: 18 }}>🗺️</Text>,
        }}
      />

      <Tab.Screen
        name="SensorsTab"
        component={SensorsScreen}
        options={{
          tabBarLabel: 'Sensörler',
          tabBarIcon: () => <Text style={{ fontSize: 18 }}>📡</Text>,
        }}
      />

      <Tab.Screen
        name="HistoryTab"
        component={HistoryScreen}
        options={{
          tabBarLabel: 'Geçmiş',
          tabBarIcon: () => <Text style={{ fontSize: 18 }}>⏱️</Text>,
        }}
      />
    </Tab.Navigator>
  );
};

interface RootNavigatorProps {
  user: User | null;
  onLoginSuccess: (user: User) => void;
}

export const RootNavigator: React.FC<RootNavigatorProps> = ({ user, onLoginSuccess }) => {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {!user ? (
        <Stack.Screen name="Login">
          {(props) => <LoginScreen {...props} onLoginSuccess={onLoginSuccess} />}
        </Stack.Screen>
      ) : (
        <>
          <Stack.Screen name="Main">
            {() => <MainTabs user={user} />}
          </Stack.Screen>
          <Stack.Screen name="LiveMap" component={LiveMapScreen} />
          <Stack.Screen name="IncidentDetail" component={IncidentDetailScreen} />
          <Stack.Screen name="CameraStream" component={CameraStreamScreen} />
        </>
      )}
    </Stack.Navigator>
  );
};
