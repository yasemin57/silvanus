import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
} from 'react-native';
import axios from 'axios';
import { API_BASE_URL } from '../services/api';
import { FireIncidentItem } from '../types';

export const HistoryScreen: React.FC<{ navigation: any }> = ({ navigation }) => {
  const [history, setHistory] = useState<FireIncidentItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchHistory = async () => {
      try {
        const res = await axios.get(`${API_BASE_URL}/incidents/history`);
        if (res.data.success) {
          setHistory(res.data.data);
        }
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchHistory();
  }, []);

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>GEÇMİŞ MÜDAHALE KAYITLARI</Text>
        <Text style={styles.headerSub}>Tamamlanan ve Söndürülen Yangın Olayları</Text>
      </View>

      {loading ? (
        <ActivityIndicator color="#EF4444" style={{ marginTop: 30 }} />
      ) : (
        <FlatList
          data={history}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.list}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.card}
              onPress={() =>
                navigation.navigate('IncidentDetail', {
                  incidentId: item.id,
                  latitude: item.latitude,
                  longitude: item.longitude,
                })
              }
            >
              <View style={styles.row}>
                <Text style={styles.code}>{item.incident_code}</Text>
                <Text style={styles.resolvedBadge}>✅ TAMAMLANDI</Text>
              </View>

              <Text style={styles.title}>{item.title}</Text>
              <Text style={styles.sub}>{item.zone_name}</Text>

              <View style={styles.footer}>
                <Text style={styles.time}>
                  {new Date(item.detected_at).toLocaleDateString('tr-TR')}
                </Text>
                <Text style={styles.detailLink}>Kayıtları Gör →</Text>
              </View>
            </TouchableOpacity>
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
    gap: 6,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  code: {
    fontSize: 10,
    fontFamily: 'monospace',
    color: '#94A3B8',
    fontWeight: '700',
  },
  resolvedBadge: {
    fontSize: 10,
    fontWeight: '800',
    color: '#10B981',
  },
  title: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FFF',
  },
  sub: {
    fontSize: 11,
    color: '#94A3B8',
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 6,
    paddingTop: 6,
    borderTopWidth: 1,
    borderTopColor: '#1E293B',
  },
  time: {
    fontSize: 10,
    color: '#64748B',
    fontFamily: 'monospace',
  },
  detailLink: {
    fontSize: 11,
    fontWeight: '800',
    color: '#EF4444',
  },
});
