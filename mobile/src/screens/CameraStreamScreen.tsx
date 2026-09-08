import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
  Dimensions,
} from 'react-native';

const { width, height } = Dimensions.get('window');

interface Props {
  route: any;
  navigation: any;
}

export const CameraStreamScreen: React.FC<Props> = ({ route, navigation }) => {
  const incident = route.params?.incident;
  const [thermalMode, setThermalMode] = useState(true);

  return (
    <View style={styles.container}>
      <!-- Top HUD -->
      <View style={styles.topHud}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.closeBtn}>
          <Text style={styles.closeBtnText}>✕ Kapat</Text>
        </TouchableOpacity>

        <View>
          <Text style={styles.camTitle}>CAM-022 ZEYTİNLİ VADİ PTZ</Text>
          <Text style={styles.camSub}>4K Ultra HD Dual Vision (Optik + Termal)</Text>
        </View>

        <TouchableOpacity
          style={[styles.toggleBtn, thermalMode && styles.toggleBtnActive]}
          onPress={() => setThermalMode(!thermalMode)}
        >
          <Text style={styles.toggleBtnText}>{thermalMode ? 'IR TERMAL' : 'OPTİK'}</Text>
        </TouchableOpacity>
      </View>

      <!-- Video Feed Simulator -->
      <View style={styles.feedWrapper}>
        <Image
          source={{
            uri: thermalMode
              ? 'https://images.unsplash.com/photo-1516214104703-d870798883c5?w=1000'
              : 'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?w=1000',
          }}
          style={styles.feedImage}
        />

        <!-- HUD Crosshairs & Detection Box -->
        <View style={styles.crosshairCenter}>
          <View style={styles.targetBox}>
            <Text style={styles.targetText}>🔥 ALEV ODAĞI (%98)</Text>
          </View>
        </View>

        <View style={styles.bottomHud}>
          <Text style={styles.hudText}>FPS: 60 ● BITRATE: 12.4 Mbps ● LAT: 39.7180 LNG: 26.8720</Text>
          <Text style={styles.hudText}>Sektör: Silvanus Bölgesi 03 ● AI Model: YOLO-Wildfire-V4</Text>
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  topHud: {
    position: 'absolute',
    top: 50,
    left: 20,
    right: 20,
    zIndex: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    padding: 12,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#1E293B',
  },
  closeBtn: {
    padding: 6,
  },
  closeBtnText: {
    color: '#FFF',
    fontSize: 12,
    fontWeight: '800',
  },
  camTitle: {
    color: '#FFF',
    fontSize: 12,
    fontWeight: '800',
    textAlign: 'center',
  },
  camSub: {
    color: '#94A3B8',
    fontSize: 10,
    textAlign: 'center',
  },
  toggleBtn: {
    backgroundColor: '#1E293B',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  toggleBtnActive: {
    backgroundColor: '#EF4444',
  },
  toggleBtnText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: '900',
  },
  feedWrapper: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  feedImage: {
    width,
    height,
    resizeMode: 'cover',
  },
  crosshairCenter: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  targetBox: {
    width: 140,
    height: 140,
    borderWidth: 2,
    borderColor: '#EF4444',
    borderStyle: 'dashed',
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'flex-start',
    padding: 4,
    backgroundColor: 'rgba(239, 68, 68, 0.1)',
  },
  targetText: {
    color: '#FFF',
    backgroundColor: '#EF4444',
    fontSize: 9,
    fontWeight: '900',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  bottomHud: {
    position: 'absolute',
    bottom: 30,
    left: 20,
    right: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.75)',
    padding: 10,
    borderRadius: 10,
    gap: 2,
  },
  hudText: {
    color: '#CBD5E1',
    fontFamily: 'monospace',
    fontSize: 10,
    textAlign: 'center',
  },
});
