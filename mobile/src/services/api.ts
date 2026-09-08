import axios from 'axios';
import { offlineStorage } from './offlineStorage';
import { FireIncidentItem, MapElementsResponse, User } from '../types';

export const API_BASE_URL = 'http://10.0.2.2:8000/api/v1'; // Android Emulator default or local dev server

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

apiClient.interceptors.request.use(async (config) => {
  const token = await offlineStorage.getAuthToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export const apiService = {
  login: async (email: string, password: string, fcm_device_token?: string): Promise<{ token: string; user: User }> => {
    const res = await apiClient.post('/auth/login', { email, password, fcm_device_token });
    if (res.data.token) {
      await offlineStorage.saveAuthToken(res.data.token);
      await offlineStorage.saveUserProfile(res.data.user);
    }
    return res.data;
  },

  getMe: async (): Promise<User> => {
    const res = await apiClient.get('/auth/me');
    return res.data.user;
  },

  getMapElements: async (): Promise<MapElementsResponse['data']> => {
    try {
      const res = await apiClient.get<MapElementsResponse>('/map/elements');
      if (res.data.success) {
        await offlineStorage.cacheMapElements(res.data.data);
        return res.data.data;
      }
    } catch (error) {
      // Fallback to offline cache if connectivity drops in forest
      const cached = await offlineStorage.getCachedMapElements();
      if (cached) return cached;
      throw error;
    }
    throw new Error('Harita verisi alınamadı.');
  },

  getActiveIncidents: async (): Promise<FireIncidentItem[]> => {
    try {
      const res = await apiClient.get('/incidents/active');
      if (res.data.success) {
        await offlineStorage.cacheIncidents(res.data.data);
        return res.data.data;
      }
    } catch (error) {
      const cached = await offlineStorage.getCachedIncidents();
      if (cached) return cached;
      throw error;
    }
    return [];
  },

  getIncidentDetail: async (id: number): Promise<{ incident: FireIncidentItem; kpis?: any }> => {
    try {
      const res = await apiClient.get(`/incidents/${id}`);
      return { incident: res.data.data, kpis: res.data.kpis };
    } catch (error) {
      const cached = await offlineStorage.getCachedIncident(id);
      if (cached) return { incident: cached };
      throw error;
    }
  },

  transitionIncident: async (
    id: number,
    status: string,
    note?: string,
    latitude?: number,
    longitude?: number
  ): Promise<FireIncidentItem> => {
    const res = await apiClient.post(`/incidents/${id}/transition`, {
      status,
      note,
      latitude,
      longitude,
    });
    return res.data.data;
  },

  downloadOfflineSyncBundle: async (): Promise<any> => {
    const res = await apiClient.get('/offline/sync-bundle');
    if (res.data.success) {
      await offlineStorage.saveFullSyncBundle(res.data.bundle);
    }
    return res.data;
  },
};
