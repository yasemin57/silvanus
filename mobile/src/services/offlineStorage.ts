import AsyncStorage from '@react-native-async-storage/async-storage';
import { FireIncidentItem, MapElementsResponse, User } from '../types';

const KEYS = {
  AUTH_TOKEN: '@silvanus_auth_token',
  USER_PROFILE: '@silvanus_user_profile',
  CACHED_MAP_ELEMENTS: '@silvanus_map_elements',
  CACHED_INCIDENTS: '@silvanus_incidents',
  OFFLINE_SYNC_BUNDLE: '@silvanus_sync_bundle',
  PENDING_STATUS_TRANSITIONS: '@silvanus_pending_transitions',
};

export const offlineStorage = {
  saveAuthToken: async (token: string): Promise<void> => {
    await AsyncStorage.setItem(KEYS.AUTH_TOKEN, token);
  },

  getAuthToken: async (): Promise<string | null> => {
    return await AsyncStorage.getItem(KEYS.AUTH_TOKEN);
  },

  clearAuthToken: async (): Promise<void> => {
    await AsyncStorage.removeItem(KEYS.AUTH_TOKEN);
    await AsyncStorage.removeItem(KEYS.USER_PROFILE);
  },

  saveUserProfile: async (user: User): Promise<void> => {
    await AsyncStorage.setItem(KEYS.USER_PROFILE, JSON.stringify(user));
  },

  getUserProfile: async (): Promise<User | null> => {
    const raw = await AsyncStorage.getItem(KEYS.USER_PROFILE);
    return raw ? JSON.parse(raw) : null;
  },

  cacheMapElements: async (elements: MapElementsResponse['data']): Promise<void> => {
    await AsyncStorage.setItem(KEYS.CACHED_MAP_ELEMENTS, JSON.stringify(elements));
  },

  getCachedMapElements: async (): Promise<MapElementsResponse['data'] | null> => {
    const raw = await AsyncStorage.getItem(KEYS.CACHED_MAP_ELEMENTS);
    return raw ? JSON.parse(raw) : null;
  },

  cacheIncidents: async (incidents: FireIncidentItem[]): Promise<void> => {
    await AsyncStorage.setItem(KEYS.CACHED_INCIDENTS, JSON.stringify(incidents));
  },

  getCachedIncidents: async (): Promise<FireIncidentItem[] | null> => {
    const raw = await AsyncStorage.getItem(KEYS.CACHED_INCIDENTS);
    return raw ? JSON.parse(raw) : null;
  },

  getCachedIncident: async (id: number): Promise<FireIncidentItem | null> => {
    const all = await offlineStorage.getCachedIncidents();
    return all?.find((i) => i.id === id) ?? null;
  },

  saveFullSyncBundle: async (bundle: any): Promise<void> => {
    await AsyncStorage.setItem(KEYS.OFFLINE_SYNC_BUNDLE, JSON.stringify(bundle));
    if (bundle.incidents) {
      await offlineStorage.cacheIncidents(bundle.incidents);
    }
  },

  getOfflineSyncBundle: async (): Promise<any | null> => {
    const raw = await AsyncStorage.getItem(KEYS.OFFLINE_SYNC_BUNDLE);
    return raw ? JSON.parse(raw) : null;
  },
};
