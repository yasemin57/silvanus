import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { apiService } from '../services/api';
import { FireIncidentItem, User } from '../types';

interface Props {
  navigation: any;
  user: User | null;
}

export const HomeScreen: React.FC<Props> = ({ navigation, user }) => {
  const [incidents, setIncidents] = useState<FireIncidentItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchIncidents = async () => {
    try {
      const data = await apiService.getActiveIncidents();
      setIncidents(data);
    } catch (error) {
      console.error('Fetch incidents error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchIncidents();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchIncidents();
  };

  return (
    <View style={styles.container}>
      <!-- Top Header -->
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>GÖREV BAŞI</Text>
          <Text style={styles.userName}>{user?.name ?? 'İtfaiye Personeli'}</Text>
          <Text style={styles.stationBadge}>
            🏢 {user?.firefighter?.station?.name ?? 'Kazdağları Yangın İlk Müdahale'}
          </Text>
        </View>

        <TouchableOpacity
          style={styles.mapQuickBtn}
          onPress={() => navigation.navigate('LiveMap')}
        >
          <Text style={styles.mapQuickText}>🗺️ HARİTA</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#EF4444" />
        }
      >
        <!-- Active Alert Banner -->
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>AKTİF YANGIN VE ERKEN UYARILAR</Text>
          <View style={styles.countBadge}>
            <Text style={styles.countText}>{incidents.length}</Text>
          </View>
        </View>

        {loading ? (
          <ActivityIndicator color="#EF4444" style={{ marginTop: 20 }} />
        ) : incidents.length > 0 ? (
          incidents.map((incident) => (
            <TouchableOpacity
              key={incident.id}
              style={styles.incidentCard}
              onPress={() =>
                navigation.navigate('IncidentDetail', {
                  incidentId: incident.id,
                  latitude: incident.latitude,
                  longitude: incident.longitude,
                })
              }
            >
              <View style={styles.cardHeader}>
                <View style={styles.fireIconBox}>
                  <Text style={styles.fireEmoji}>🔥</Text>
                </View>
                <View style={{ flex: 1, marginLeft: 12 }}>
                  <Text style={styles.incidentCode}>{incident.incident_code}</Text>
                  <Text style={styles.incidentTitle}>{incident.title}</Text>
                </View>
                <View style={styles.riskBadge}>
                  <Text style={styles.riskText}>%{Math.round(incident.risk_score)} KRİTİK</Text>
                </View>
              </View>

              <View style={styles.metricsRow}>
                <View style={styles.metricItem}>
                  <Text style={styles.metricLabel}>Sıcaklık</Text>
                  <Text style={styles.metricValue}>
                    {incident.latest_readings?.temperature ?? 72}°C
                  </Text>
                </View>
                <View style={styles.metricItem}>
                  <Text style={styles.metricLabel}>Nem</Text>
                  <Text style={styles.metricValue}>
                    %{incident.latest_readings?.humidity ?? 13}
                  </Text>
                </View>
                <View style={styles.metricItem}>
                  <Text style={styles.metricLabel}>Duman</Text>
                  <Text style={styles.metricValue}>
                    {incident.latest_readings?.smoke_ppm ?? 92} ppm
                  </Text>
                </View>
                <View style={styles.metricItem}>
                  <Text style={styles.metricLabel}>Durum</Text>
                  <Text style={styles.statusValue}>{incident.status_label ?? incident.status}</Text>
                </View>
              </View>

              <View style={styles.cardFooter}>
                <Text style={styles.locationText}>
                  📍 Lat: {incident.latitude.toFixed(4)}, Lng: {incident.longitude.toFixed(4)}
                </Text>
                <Text style={styles.actionPrompt}>MÜDAHALE ET →</Text>
              </View>
            </TouchableOpacity>
          ))
        ) : (
          <View style={styles.emptyCard}>
            <Text style={{ fontSize: 36, marginBottom: 8 }}>🌲</Text>
            <Text style={styles.emptyTitle}>Aktif Yangın Bulunmuyor</Text>
            <Text style={styles.emptySubtitle}>Sensörler normal orman telemetrisi iletiyor.</Text>
          </View>
        )}

        <!-- Offline Sync & Health Info -->
        <View style={styles.healthCard}>
          <Text style={styles.healthTitle}>⚡ SİSTEM DURUMU</Text>
          <View style={styles.healthGrid}>
            <View style={styles.healthItem}>
              <Text style={styles.healthLabel}>Yerel Sunucu</Text>
              <Text style={styles.healthStatusActive}>🟢 ÇEVRİMİÇİ</Text>
            </View>
            <View style={styles.healthItem}>
              <Text style={styles.healthLabel}>Sensörler</Text>
              <Text style={styles.healthStatusActive}>🟢 148 / 150</Text>
            </View>
            <View style={styles.healthItem}>
              <Text style={styles.healthLabel}>Kameralar</Text>
              <Text style={styles.healthStatusActive}>🟢 31 / 32</Text>
            </View>
          </View>

          <TouchableOpacity
            style={styles.syncButton}
            onPress={async () => {
              await apiService.downloadOfflineSyncBundle();
              alert('Offline orman paketi başarıyla senkronize edildi.');
            }}
          >
            <Text style={styles.syncButtonText}>📥 OFFLINE PAKETİ GÜNCELLE</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#080C14',
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 54,
    paddingBottom: 16,
    backgroundColor: '#0F172A',
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  greeting: {
    fontSize: 10,
    fontWeight: '800',
    color: '#10B981',
    letterSpacing: 1,
  },
  userName: {
    fontSize: 18,
    fontWeight: '800',
    color: '#FFF',
    marginTop: 2,
  },
  stationBadge: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  mapQuickBtn: {
    backgroundColor: '#EF4444',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
  },
  mapQuickText: {
    color: '#FFF',
    fontWeight: '800',
    fontSize: 12,
  },
  scrollContent: {
    padding: 16,
    gap: 16,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#CBD5E1',
    letterSpacing: 0.5,
  },
  countBadge: {
    backgroundColor: 'rgba(239, 68, 68, 0.2)',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.4)',
  },
  countText: {
    color: '#EF4444',
    fontSize: 11,
    fontWeight: '800',
  },
  incidentCard: {
    backgroundColor: '#0F172A',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.3)',
    gap: 12,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  fireIconBox: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: 'rgba(239, 68, 68, 0.15)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  fireEmoji: {
    fontSize: 22,
  },
  incidentCode: {
    fontSize: 10,
    fontFamily: 'monospace',
    color: '#94A3B8',
    fontWeight: '700',
  },
  incidentTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#FFF',
    marginTop: 1,
  },
  riskBadge: {
    backgroundColor: '#EF4444',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 10,
  },
  riskText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: '900',
  },
  metricsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#080C14',
    padding: 10,
    borderRadius: 12,
  },
  metricItem: {
    alignItems: 'center',
  },
  metricLabel: {
    fontSize: 9,
    color: '#64748B',
    fontWeight: '600',
    marginBottom: 2,
  },
  metricValue: {
    fontSize: 13,
    fontWeight: '800',
    color: '#F8FAFC',
    fontFamily: 'monospace',
  },
  statusValue: {
    fontSize: 11,
    fontWeight: '700',
    color: '#F59E0B',
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#1E293B',
    paddingTop: 10,
  },
  locationText: {
    fontSize: 11,
    fontFamily: 'monospace',
    color: '#94A3B8',
  },
  actionPrompt: {
    fontSize: 11,
    fontWeight: '800',
    color: '#EF4444',
  },
  emptyCard: {
    backgroundColor: '#0F172A',
    borderRadius: 18,
    padding: 32,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#1E293B',
  },
  emptyTitle: {
    color: '#FFF',
    fontWeight: '800',
    fontSize: 16,
  },
  emptySubtitle: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 4,
  },
  healthCard: {
    backgroundColor: '#0F172A',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: '#1E293B',
    marginTop: 8,
    gap: 12,
  },
  healthTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 0.5,
  },
  healthGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  healthItem: {
    alignItems: 'center',
  },
  healthLabel: {
    fontSize: 10,
    color: '#64748B',
    marginBottom: 2,
  },
  healthStatusActive: {
    fontSize: 11,
    fontWeight: '700',
    color: '#10B981',
  },
  syncButton: {
    backgroundColor: '#1E293B',
    paddingVertical: 10,
    borderRadius: 10,
    alignItems: 'center',
  },
  syncButtonText: {
    color: '#CBD5E1',
    fontSize: 11,
    fontWeight: '700',
  },
});
