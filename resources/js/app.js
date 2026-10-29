import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ActivityIndicator, RefreshControl, ScrollView } from 'react-native';
import { WebView } from 'react-native-webview';

const API_URL = 'http://192.168.137.1:8000/api/v1/sensor-data/latest';
const CAMERA_STREAM_URL = 'http://192.168.137.153:5000/video_feed';

export default function App() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchSensorData = async () => {
    try {
      const response = await fetch(API_URL);
      const json = await response.json();
      if (json.status === 'success') {
        setData(json.data);
      }
    } catch (error) {
      console.error('Sensör verisi çekilemedi:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchSensorData();
    // 3 saniyede bir verileri otomatik güncelle (Polling)
    const interval = setInterval(fetchSensorData, 3000);
    return () => clearInterval(interval);
  }, []);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#38bdf8" />
        <Text style={styles.loadingText}>Silvanus Sensör Verileri Yükleniyor...</Text>
      </View>
    );
  }

  return (
    <ScrollView 
      contentContainerStyle={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchSensorData(); }} />}
    >
      <Text style={styles.title}>🌲 SİLVANUS SENSÖR PANELSİ</Text>

      {/* Sıcaklık Kartı */}
      <View style={[styles.card, { borderColor: '#f87171' }]}>
        <Text style={styles.cardLabel}>Sıcaklık</Text>
        <Text style={[styles.cardValue, { color: '#f87171' }]}>
          {data?.temperature != null ? `${data.temperature} °C` : '--'}
        </Text>
      </View>

      {/* Nem Kartı */}
      <View style={[styles.card, { borderColor: '#38bdf8' }]}>
        <Text style={styles.cardLabel}>Nem Oranı</Text>
        <Text style={[styles.cardValue, { color: '#38bdf8' }]}>
          {data?.humidity != null ? `%${data.humidity}` : '--'}
        </Text>
      </View>

      {/* Gaz Seviyesi Kartı */}
      <View style={[styles.card, { borderColor: data?.gas_level > 2000 ? '#ef4444' : '#4ade80' }]}>
        <Text style={styles.cardLabel}>Gaz Seviyesi (MQ-2)</Text>
        <Text style={[styles.cardValue, { color: data?.gas_level > 2000 ? '#ef4444' : '#4ade80' }]}>
          {data?.gas_level != null ? `${data.gas_level} ADC` : '--'}
        </Text>
        {data?.gas_level > 2000 && (
          <Text style={styles.warningText}>⚠️ YÜKSEK GAZ SEVİYESİ / YANGIN RİSKİ!</Text>
        )}
      </View>

      {/* Canlı Kamera Akış Kartı (Raspberry Pi 5) */}
      <View style={[styles.card, { borderColor: '#a855f7', padding: 10 }]}>
        <Text style={[styles.cardLabel, { color: '#c084fc', fontWeight: 'bold' }]}>
          🎥 CANLI TERMAL / ALEV TESPİT KAMERASI
        </Text>
        <View style={styles.cameraWrapper}>
          <WebView
            source={{ uri: CAMERA_STREAM_URL }}
            style={styles.cameraStream}
            allowsInlineMediaPlayback={true}
            originWhitelist={['*']}
            mixedContentMode="always"
            startInLoadingState={true}
            scalesPageToFit={true}
          />
        </View>
      </View>

      <Text style={styles.footerText}>
        Son Güncelleme: {data?.created_at ? new Date(data.created_at).toLocaleTimeString() : 'Henüz veri yok'}
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1, backgroundColor: '#0f172a', padding: 20, justifyContent: 'center', alignItems: 'center' },
  center: { flex: 1, backgroundColor: '#0f172a', justifyContent: 'center', alignItems: 'center' },
  loadingText: { color: '#94a3b8', marginTop: 10 },
  title: { fontSize: 20, fontWeight: 'bold', color: '#f8fafc', marginBottom: 25 },
  card: { backgroundColor: '#1e293b', width: '100%', padding: 20, borderRadius: 16, borderWidth: 1, marginBottom: 15, alignItems: 'center' },
  cardLabel: { fontSize: 14, color: '#94a3b8', marginBottom: 5 },
  cardValue: { fontSize: 28, fontWeight: 'bold' },
  warningText: { color: '#ef4444', fontWeight: 'bold', marginTop: 8, fontSize: 12 },
  cameraWrapper: { width: '100%', height: 220, borderRadius: 12, overflow: 'hidden', backgroundColor: '#000', marginTop: 8 },
  cameraStream: { flex: 1 },
  footerText: { color: '#64748b', fontSize: 12, marginTop: 15 }
});