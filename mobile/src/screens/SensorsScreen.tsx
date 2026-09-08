import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { apiService } from '../services/api';
import { SensorItem } from '../types';

export const SensorsScreen: React.FC = () => {
  const [sensors, setSensors] = useState<SensorItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadSensors = async () => {
    try {
      const data = await apiService.getMapElements();
      setSensors(data.sensors);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadSensors();
  }, []);

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>ORMAN SENSÖR AĞI</Text>
        <Text style={styles.headerSub}>Otomatik Çevresel Telemetri Düğümleri</Text>
      </View>

      {loading ? (
        <ActivityIndicator color="#10B981" style={{ marginTop: 30 }} />
      ) : (
        <FlatList
          data={sensors}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => {
                setRefreshing(true);
                loadSensors();
              }}
              tintColor="#10B981"
            />
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardTop}>
                <View style={styles.codeRow}>
                  <Text style={styles.codeText}>{item.code}</Text>
                  <View
                    style={[
                      styles.statusDot,
                      {
                        backgroundColor:
                          item.status === 'ACTIVE'
                            ? '#10B981'
                            : item.status === 'WARNING'
                            ? '#F59E0B'
                            : '#EF4444',
                      },
                    ]}
                  />
                </View>
                <Text style={styles.batteryText}>🔋 %{item.battery_level}</Text>
              </View>

              <Text style={styles.nameText}>{item.name}</Text>
              <Text style={styles.zoneText}>📍 {item.zone_name}</Text>

              {item.telemetry ? (
                <View style={styles.telemetryRow}>
                  <View style={styles.tBox}>
                    <Text style={styles.tLabel}>Sıcaklık</Text>
                    <Text style={styles.tVal}>{item.telemetry.temperature}°C</Text>
                  </View>
                  <View style={styles.tBox}>
                    <Text style={styles.tLabel}>Nem</Text>
                    <Text style={styles.tVal}>%{item.telemetry.humidity}</Text>
                  </View>
                  <View style={styles.tBox}>
                    <Text style={styles.tLabel}>Duman</Text>
                    <Text style={styles.tVal}>{item.telemetry.smoke_ppm} ppm</Text>
                  </View>
                  <View style={styles.tBox}>
                    <Text style={styles.tLabel}>Gaz (CO)</Text>
                    <Text style={styles.tVal}>{item.telemetry.co_gas_ppm} ppm</Text>
                  </View>
                </View>
              ) : (
                <Text style={styles.noData}>Telemetri bekleniyor</Text>
              )}
            </View>
          )}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#080C14',
  },
  header: {
    paddingTop: 54,
    paddingHorizontal: 20,
    paddingBottom: 16,
    backgroundColor: '#0F172A',
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#FFF',
    letterSpacing: 0.5,
  },
  headerSub: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  list: {
    padding: 16,
    gap: 12,
  },
  card: {
    backgroundColor: '#0F172A',
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: '#1E293B',
    gap: 8,
  },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  codeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  codeText: {
    fontSize: 11,
    fontFamily: 'monospace',
    fontWeight: '800',
    color: '#10B981',
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  batteryText: {
    fontSize: 11,
    color: '#94A3B8',
    fontFamily: 'monospace',
  },
  nameText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#FFF',
  },
  zoneText: {
    fontSize: 11,
    color: '#94A3B8',
  },
  telemetryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#080C14',
    padding: 8,
    borderRadius: 10,
    marginTop: 4,
  },
  tBox: {
    alignItems: 'center',
  },
  tLabel: {
    fontSize: 8,
    color: '#64748B',
    marginBottom: 2,
  },
  tVal: {
    fontSize: 11,
    fontFamily: 'monospace',
    fontWeight: '700',
    color: '#CBD5E1',
  },
  noData: {
    fontSize: 11,
    color: '#64748B',
    fontStyle: 'italic',
  },
});
