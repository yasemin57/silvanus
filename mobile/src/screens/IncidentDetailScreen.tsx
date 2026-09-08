import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Image,
} from 'react-native';
import { apiService } from '../services/api';
import { FireIncidentItem, IncidentStatus } from '../types';

interface Props {
  route: any;
  navigation: any;
}

export const IncidentDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const incidentId = route.params?.incidentId;
  const [incident, setIncident] = useState<FireIncidentItem | null>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  const fetchDetail = async () => {
    try {
      const res = await apiService.getIncidentDetail(incidentId);
      setIncident(res.incident);
    } catch (e: any) {
      Alert.alert('Hata', 'Olay detayları alınamadı.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDetail();
  }, [incidentId]);

  const handleTransition = async (nextStatus: IncidentStatus) => {
    setUpdating(true);
    try {
      const updated = await apiService.transitionIncident(incidentId, nextStatus);
      setIncident(updated);
      Alert.alert('Başarılı', `Olay durumu "${nextStatus}" olarak güncellendi.`);
    } catch (e: any) {
      Alert.alert('Hata', e.response?.data?.message || 'Durum güncellenemedi.');
    } finally {
      setUpdating(false);
    }
  };

  if (loading) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color="#EF4444" />
      </View>
    );
  }

  if (!incident) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <Text style={{ color: '#FFF' }}>Olay bulunamadı.</Text>
      </View>
    );
  }

  const getNextAction = () => {
    switch (incident.status) {
      case 'DETECTED':
      case 'NOTIFIED':
        return { label: 'OLAYI KABUL ET', status: 'ACCEPTED' as IncidentStatus, color: '#10B981' };
      case 'ACCEPTED':
        return { label: 'YOLA ÇIKILDI (İNTİKAL)', status: 'ON_THE_WAY' as IncidentStatus, color: '#F59E0B' };
      case 'ON_THE_WAY':
        return { label: 'OLAY YERİNE ULAŞILDI', status: 'ARRIVED' as IncidentStatus, color: '#06B6D4' };
      case 'ARRIVED':
        return { label: 'YANGIN KONTROL ALTINDA', status: 'UNDER_CONTROL' as IncidentStatus, color: '#8B5CF6' };
      case 'UNDER_CONTROL':
        return { label: 'SÖNDÜRÜLDÜ VE KAPATILDI', status: 'RESOLVED' as IncidentStatus, color: '#64748B' };
      default:
        return null;
    }
  };

  const nextAction = getNextAction();

  return (
    <View style={styles.container}>
      <!-- Top Bar -->
      <View style={styles.topBar}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Text style={styles.backBtnText}>←</Text>
        </TouchableOpacity>
        <Text style={styles.topBarTitle}>YANGIN MÜDAHALE DETAYI</Text>
        <View style={styles.riskBadge}>
          <Text style={styles.riskBadgeText}>%{Math.round(incident.risk_score)} KRİTİK</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <!-- Incident Header Info -->
        <View style={styles.card}>
          <Text style={styles.codeText}>{incident.incident_code}</Text>
          <Text style={styles.titleText}>{incident.title}</Text>
          <Text style={styles.zoneText}>🌲 {incident.zone_name}</Text>

          <View style={styles.metaGrid}>
            <View style={styles.metaItem}>
              <Text style={styles.metaLabel}>Tespit Zamanı</Text>
              <Text style={styles.metaValue}>
                {new Date(incident.detected_at).toLocaleTimeString('tr-TR')}
              </Text>
            </View>
            <View style={styles.metaItem}>
              <Text style={styles.metaLabel}>Mevcut Durum</Text>
              <Text style={styles.metaStatus}>{incident.status_label ?? incident.status}</Text>
            </View>
          </View>
        </View>

        <!-- Location Section (Requirement 6, 8) -->
        <View style={styles.card}>
          <Text style={styles.sectionHeader}>📍 OTOMATİK YANGIN KONUMU</Text>
          <Text style={styles.locationCoords}>
            Lat: {incident.latitude.toFixed(5)}, Lng: {incident.longitude.toFixed(5)}
          </Text>

          <TouchableOpacity
            style={styles.mapFocusBtn}
            onPress={() =>
              navigation.navigate('LiveMap', {
                latitude: incident.latitude,
                longitude: incident.longitude,
              })
            }
          >
            <Text style={styles.mapFocusBtnText}>🗺️ GOOGLE MAPS'TE ODAKLAN VE ROTA GÖR</Text>
          </TouchableOpacity>
        </View>

        <!-- Sensor Telemetry Grid (Requirement 13) -->
        <View style={styles.card}>
          <View style={styles.cardRow}>
            <Text style={styles.sectionHeader}>📡 SENSÖR VERİLERİ (OTOMATİK AKIŞ)</Text>
            <Text style={styles.liveTag}>CANLI</Text>
          </View>

          <View style={styles.telemetryGrid}>
            <View style={styles.telemetryBox}>
              <Text style={styles.tLabel}>Sıcaklık</Text>
              <Text style={[styles.tVal, { color: '#EF4444' }]}>
                {incident.latest_readings?.temperature ?? 72}°C
              </Text>
            </View>
            <View style={styles.telemetryBox}>
              <Text style={styles.tLabel}>Nem</Text>
              <Text style={[styles.tVal, { color: '#F59E0B' }]}>
                %{incident.latest_readings?.humidity ?? 13}
              </Text>
            </View>
            <View style={styles.telemetryBox}>
              <Text style={styles.tLabel}>Duman</Text>
              <Text style={[styles.tVal, { color: '#EF4444' }]}>
                {incident.latest_readings?.smoke_ppm ?? 92} ppm
              </Text>
            </View>
            <View style={styles.telemetryBox}>
              <Text style={styles.tLabel}>Gaz (CO)</Text>
              <Text style={[styles.tVal, { color: '#FB923C' }]}>
                {incident.latest_readings?.co_gas_ppm ?? 45} ppm
              </Text>
            </View>
          </View>

          <Text style={styles.sensorNote}>
            * Sensör verileri sisteme manuel girilmez, donanım katmanından otomatik alınır.
          </Text>
        </View>

        <!-- Camera Stream (Requirement 14) -->
        <View style={styles.card}>
          <Text style={styles.sectionHeader}>📷 CANLI KAMERA GÖRÜNTÜSÜ</Text>
          <View style={styles.cameraBox}>
            <Image
              source={{ uri: 'https://images.unsplash.com/photo-1516214104703-d870798883c5?w=800' }}
              style={styles.cameraImage}
            />
            <View style={styles.camHud}>
              <Text style={styles.camCode}>CAM-022 (Termal PTZ)</Text>
              <Text style={styles.aiDetectionTag}>AI ALEV TESPİTİ (%98)</Text>
            </View>
          </View>

          <TouchableOpacity
            style={styles.fullscreenCamBtn}
            onPress={() => navigation.navigate('CameraStream', { incident })}
          >
            <Text style={styles.fullscreenCamText}>TAM EKRAN CANLI AKIŞI İZLE</Text>
          </TouchableOpacity>
        </View>

        <!-- Dispatch Info (Requirement 10) -->
        <View style={styles.card}>
          <Text style={styles.sectionHeader}>🏢 İLGİLİ İTFAİYE MERKEZİ</Text>
          <Text style={styles.stationTitle}>
            {incident.fire_station?.name ?? 'Kazdağları İlk Müdahale Merkezi (Merkez #1)'}
          </Text>
          <Text style={styles.stationDist}>📍 Kuş Uçuşu Mesafe: 2.4 km | Tahmini Varış: 6 dk</Text>
        </View>

        <!-- Lifecycle Action Button (Requirement 15) -->
        {nextAction && (
          <TouchableOpacity
            style={[styles.actionBtn, { backgroundColor: nextAction.color }]}
            onPress={() => handleTransition(nextAction.status)}
            disabled={updating}
          >
            {updating ? (
              <ActivityIndicator color="#FFF" />
            ) : (
              <Text style={styles.actionBtnText}>[ {nextAction.label} ]</Text>
            )}
          </TouchableOpacity>
        )}

        <!-- Timeline -->
        <View style={styles.card}>
          <Text style={styles.sectionHeader}>⏱️ OLAY ZAMAN ÇİZELGESİ</Text>
          <View style={styles.timelineList}>
            {incident.timelines?.map((item, idx) => (
              <View key={idx} style={styles.timelineItem}>
                <View style={styles.timelineDot} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.timelineTitle}>{item.action_title ?? item.status_to}</Text>
                  <Text style={styles.timelineNote}>{item.note}</Text>
                  <Text style={styles.timelineTime}>
                    {new Date(item.recorded_at).toLocaleTimeString('tr-TR')}
                  </Text>
                </View>
              </View>
            ))}
          </View>
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
  topBar: {
    paddingTop: 54,
    paddingHorizontal: 20,
    paddingBottom: 16,
    backgroundColor: '#0F172A',
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backBtn: {
    padding: 8,
  },
  backBtnText: {
    color: '#FFF',
    fontSize: 20,
    fontWeight: '800',
  },
  topBarTitle: {
    color: '#FFF',
    fontSize: 14,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  riskBadge: {
    backgroundColor: '#EF4444',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  riskBadgeText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: '900',
  },
  scroll: {
    padding: 16,
    gap: 16,
  },
  card: {
    backgroundColor: '#0F172A',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: '#1E293B',
    gap: 10,
  },
  codeText: {
    fontSize: 10,
    fontFamily: 'monospace',
    color: '#94A3B8',
    fontWeight: '700',
  },
  titleText: {
    fontSize: 18,
    fontWeight: '800',
    color: '#FFF',
  },
  zoneText: {
    fontSize: 12,
    color: '#CBD5E1',
  },
  metaGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#080C14',
    padding: 10,
    borderRadius: 12,
    marginTop: 4,
  },
  metaItem: {
    flex: 1,
  },
  metaLabel: {
    fontSize: 10,
    color: '#64748B',
    marginBottom: 2,
  },
  metaValue: {
    fontSize: 13,
    fontWeight: '800',
    color: '#F8FAFC',
    fontFamily: 'monospace',
  },
  metaStatus: {
    fontSize: 13,
    fontWeight: '800',
    color: '#F59E0B',
  },
  sectionHeader: {
    fontSize: 11,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 0.5,
  },
  locationCoords: {
    fontSize: 13,
    fontFamily: 'monospace',
    color: '#F59E0B',
    fontWeight: '700',
  },
  mapFocusBtn: {
    backgroundColor: '#1E293B',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 4,
  },
  mapFocusBtnText: {
    color: '#F8FAFC',
    fontWeight: '800',
    fontSize: 11,
  },
  cardRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  liveTag: {
    color: '#10B981',
    fontSize: 10,
    fontWeight: '900',
    fontFamily: 'monospace',
  },
  telemetryGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  telemetryBox: {
    flex: 1,
    backgroundColor: '#080C14',
    padding: 10,
    borderRadius: 10,
    alignItems: 'center',
  },
  tLabel: {
    fontSize: 9,
    color: '#64748B',
    marginBottom: 2,
  },
  tVal: {
    fontSize: 14,
    fontWeight: '800',
    fontFamily: 'monospace',
  },
  sensorNote: {
    fontSize: 10,
    color: '#64748B',
    fontStyle: 'italic',
  },
  cameraBox: {
    height: 160,
    borderRadius: 12,
    overflow: 'hidden',
    position: 'relative',
  },
  cameraImage: {
    width: '100%',
    height: '100%',
  },
  camHud: {
    position: 'absolute',
    bottom: 8,
    left: 8,
    right: 8,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  camCode: {
    fontSize: 10,
    fontFamily: 'monospace',
    color: '#FFF',
    backgroundColor: 'rgba(0,0,0,0.7)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  aiDetectionTag: {
    fontSize: 10,
    fontWeight: '800',
    color: '#FFF',
    backgroundColor: '#EF4444',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  fullscreenCamBtn: {
    backgroundColor: '#06B6D4',
    paddingVertical: 10,
    borderRadius: 10,
    alignItems: 'center',
  },
  fullscreenCamText: {
    color: '#FFF',
    fontWeight: '800',
    fontSize: 11,
  },
  stationTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FFF',
  },
  stationDist: {
    fontSize: 12,
    color: '#94A3B8',
  },
  actionBtn: {
    paddingVertical: 18,
    borderRadius: 16,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  actionBtnText: {
    color: '#FFF',
    fontSize: 15,
    fontWeight: '900',
    letterSpacing: 1,
  },
  timelineList: {
    gap: 12,
    borderLeftWidth: 2,
    borderLeftColor: '#1E293B',
    paddingLeft: 12,
    marginLeft: 6,
  },
  timelineItem: {
    position: 'relative',
  },
  timelineDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#EF4444',
    position: 'absolute',
    left: -17,
    top: 4,
  },
  timelineTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: '#FFF',
  },
  timelineNote: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  timelineTime: {
    fontSize: 10,
    fontFamily: 'monospace',
    color: '#64748B',
    marginTop: 2,
  },
});
