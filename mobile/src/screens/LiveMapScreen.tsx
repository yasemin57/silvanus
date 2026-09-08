import React, { useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Dimensions,
} from 'react-native';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import { apiService } from '../services/api';
import { CameraItem, FireIncidentItem, FireStationItem, SensorItem } from '../types';

const { width, height } = Dimensions.get('window');

interface Props {
  route: any;
  navigation: any;
}

export const LiveMapScreen: React.FC<Props> = ({ route, navigation }) => {
  const mapRef = useRef<MapView | null>(null);
  const [loading, setLoading] = useState(true);
  const [fires, setFires] = useState<FireIncidentItem[]>([]);
  const [sensors, setSensors] = useState<SensorItem[]>([]);
  const [cameras, setCameras] = useState<CameraItem[]>([]);
  const [stations, setStations] = useState<FireStationItem[]>([]);
  const [selectedFire, setSelectedFire] = useState<FireIncidentItem | null>(null);

  const initialLat = route.params?.latitude ?? 39.7190;
  const initialLng = route.params?.longitude ?? 26.8710;

  const loadElements = async () => {
    try {
      const data = await apiService.getMapElements();
      setFires(data.fires);
      setSensors(data.sensors);
      setCameras(data.cameras);
      setStations(data.stations);

      if (data.fires.length > 0) {
        setSelectedFire(data.fires[0]);
      }
    } catch (e) {
      console.error('Map elements error:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadElements();
  }, []);

  // When navigated with specific coordinates, automatically fly to fire location
  useEffect(() => {
    if (route.params?.latitude && route.params?.longitude && mapRef.current) {
      mapRef.current.animateToRegion(
        {
          latitude: route.params.latitude,
          longitude: route.params.longitude,
          latitudeDelta: 0.03,
          longitudeDelta: 0.03,
        },
        1200
      );
    }
  }, [route.params?.latitude, route.params?.longitude]);

  return (
    <View style={styles.container}>
      <MapView
        ref={mapRef}
        provider={PROVIDER_GOOGLE}
        style={styles.map}
        initialRegion={{
          latitude: initialLat,
          longitude: initialLng,
          latitudeDelta: 0.08,
          longitudeDelta: 0.08,
        }}
        customMapStyle={darkMapStyle}
      >
        <!-- Fire Stations -->
        {stations.map((st) => (
          <Marker
            key={`stn-${st.id}`}
            coordinate={{ latitude: st.latitude, longitude: st.longitude }}
            title={st.name}
            description={`Müdahale İstasyonu | ${st.active_units_count} Birim`}
          >
            <View style={styles.stationMarker}>
              <Text style={{ fontSize: 16 }}>🏢</Text>
            </View>
          </Marker>
        ))}

        <!-- Sensors -->
        {sensors.map((sn) => (
          <Marker
            key={`sns-${sn.id}`}
            coordinate={{ latitude: sn.latitude, longitude: sn.longitude }}
            title={`${sn.code} (${sn.name})`}
            description={
              sn.telemetry
                ? `${sn.telemetry.temperature}°C | %${sn.telemetry.humidity} | ${sn.telemetry.smoke_ppm} ppm`
                : 'Sensör aktif'
            }
          >
            <View style={styles.sensorMarker}>
              <Text style={{ fontSize: 12 }}>📡</Text>
            </View>
          </Marker>
        ))}

        <!-- Cameras -->
        {cameras.map((cm) => (
          <Marker
            key={`cam-${cm.id}`}
            coordinate={{ latitude: cm.latitude, longitude: cm.longitude }}
            title={`${cm.code} (${cm.name})`}
            description="4K Termal/Optik Gözetleme Kamerası"
          >
            <View style={styles.cameraMarker}>
              <Text style={{ fontSize: 12 }}>📷</Text>
            </View>
          </Marker>
        ))}

        <!-- Wildfires (🔥 with pulse) -->
        {fires.map((fire) => (
          <Marker
            key={`fire-${fire.id}`}
            coordinate={{ latitude: fire.latitude, longitude: fire.longitude }}
            onPress={() => setSelectedFire(fire)}
          >
            <View style={styles.fireMarkerWrapper}>
              <View style={styles.firePulse} />
              <View style={styles.fireMarkerInner}>
                <Text style={{ fontSize: 18 }}>🔥</Text>
              </View>
            </View>
          </Marker>
        ))}

        <!-- Polyline from Station to Fire if active -->
        {selectedFire && stations.length > 0 && (
          <Polyline
            coordinates={[
              { latitude: stations[0].latitude, longitude: stations[0].longitude },
              { latitude: selectedFire.latitude, longitude: selectedFire.longitude },
            ]}
            strokeColor="#EF4444"
            strokeWidth={3}
            lineDashPattern={[6, 6]}
          />
        )}
      </MapView>

      <!-- Top Overlay Controls -->
      <View style={styles.topOverlay}>
        <TouchableOpacity
          style={styles.overlayBtn}
          onPress={() => navigation.goBack()}
        >
          <Text style={styles.overlayBtnText}>← Geri</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.overlayBtn, { backgroundColor: '#EF4444' }]}
          onPress={() => {
            if (fires.length > 0 && mapRef.current) {
              mapRef.current.animateToRegion(
                {
                  latitude: fires[0].latitude,
                  longitude: fires[0].longitude,
                  latitudeDelta: 0.02,
                  longitudeDelta: 0.02,
                },
                1000
              );
              setSelectedFire(fires[0]);
            }
          }}
        >
          <Text style={[styles.overlayBtnText, { color: '#FFF' }]}>🔥 Yangına Odaklan</Text>
        </TouchableOpacity>
      </View>

      <!-- Selected Fire Bottom Card -->
      {selectedFire && (
        <View style={styles.bottomCard}>
          <View style={styles.cardTopRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.codeText}>{selectedFire.incident_code}</Text>
              <Text style={styles.titleText}>{selectedFire.title}</Text>
            </View>
            <View style={styles.riskPill}>
              <Text style={styles.riskPillText}>%{Math.round(selectedFire.risk_score)} KRİTİK</Text>
            </View>
          </View>

          <View style={styles.telemetryMiniRow}>
            <Text style={styles.telemetryMini}>
              🌡️ {selectedFire.latest_readings?.temperature ?? 72}°C
            </Text>
            <Text style={styles.telemetryMini}>
              💧 %{selectedFire.latest_readings?.humidity ?? 13}
            </Text>
            <Text style={styles.telemetryMini}>
              💨 {selectedFire.latest_readings?.smoke_ppm ?? 92} ppm
            </Text>
            <Text style={styles.telemetryMini}>
              🏢 {selectedFire.station_name ?? 'Kazdağları'} (2.4 km)
            </Text>
          </View>

          <TouchableOpacity
            style={styles.detailButton}
            onPress={() =>
              navigation.navigate('IncidentDetail', {
                incidentId: selectedFire.id,
                latitude: selectedFire.latitude,
                longitude: selectedFire.longitude,
              })
            }
          >
            <Text style={styles.detailButtonText}>MÜDAHALE VE DETAY EKRANINI AÇ →</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
};

const darkMapStyle = [
  { elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
  { elementType: 'labels.text.stroke', stylers: [{ color: '#0f172a' }] },
  { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
  { featureType: 'landscape.natural', stylers: [{ color: '#091e1b' }] },
  { featureType: 'water', stylers: [{ color: '#030712' }] },
];

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#080C14',
  },
  map: {
    width,
    height,
  },
  topOverlay: {
    position: 'absolute',
    top: 50,
    left: 16,
    right: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  overlayBtn: {
    backgroundColor: 'rgba(15, 23, 42, 0.9)',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#1E293B',
  },
  overlayBtnText: {
    color: '#CBD5E1',
    fontWeight: '800',
    fontSize: 12,
  },
  stationMarker: {
    backgroundColor: '#D97706',
    padding: 6,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: '#FFF',
  },
  sensorMarker: {
    backgroundColor: '#10B981',
    padding: 5,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: '#FFF',
  },
  cameraMarker: {
    backgroundColor: '#06B6D4',
    padding: 5,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: '#FFF',
  },
  fireMarkerWrapper: {
    width: 44,
    height: 44,
    justifyContent: 'center',
    alignItems: 'center',
  },
  firePulse: {
    position: 'absolute',
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(239, 68, 68, 0.4)',
  },
  fireMarkerInner: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#EF4444',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#FFF',
  },
  bottomCard: {
    position: 'absolute',
    bottom: 24,
    left: 16,
    right: 16,
    backgroundColor: '#0F172A',
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.4)',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.5,
    shadowRadius: 16,
    elevation: 8,
    gap: 12,
  },
  cardTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  codeText: {
    fontSize: 10,
    fontFamily: 'monospace',
    color: '#94A3B8',
    fontWeight: '700',
  },
  titleText: {
    fontSize: 15,
    fontWeight: '800',
    color: '#FFF',
  },
  riskPill: {
    backgroundColor: '#EF4444',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  riskPillText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: '900',
  },
  telemetryMiniRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#080C14',
    padding: 8,
    borderRadius: 10,
  },
  telemetryMini: {
    fontSize: 11,
    fontFamily: 'monospace',
    color: '#CBD5E1',
    fontWeight: '600',
  },
  detailButton: {
    backgroundColor: '#EF4444',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  detailButtonText: {
    color: '#FFF',
    fontWeight: '800',
    fontSize: 12,
    letterSpacing: 0.5,
  },
});
