export type IncidentSeverity = 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';

export type IncidentStatus = 
  | 'DETECTED' 
  | 'NOTIFIED' 
  | 'ACCEPTED' 
  | 'ON_THE_WAY' 
  | 'ARRIVED' 
  | 'UNDER_CONTROL' 
  | 'RESOLVED';

export type DeviceStatus = 'ACTIVE' | 'WARNING' | 'OFFLINE';

export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  role: 'ADMIN' | 'OPERATOR' | 'FIREFIGHTER' | 'FIRE_CHIEF';
  role_label: string;
  avatar_url?: string;
  firefighter?: FirefighterProfile | null;
}

export interface FirefighterProfile {
  id: number;
  badge_number: string;
  rank: string;
  status: string;
  station?: {
    id: number;
    name: string;
    code: string;
    latitude?: number;
    longitude?: number;
  } | null;
}

export interface SensorTelemetry {
  temperature: number;
  humidity: number;
  smoke_ppm: number;
  co_gas_ppm: number;
  recorded_at?: string;
}

export interface SensorItem {
  id: number;
  code: string;
  name: string;
  zone_name: string;
  latitude: number;
  longitude: number;
  status: DeviceStatus;
  battery_level: number;
  last_ping_at?: string;
  telemetry?: SensorTelemetry | null;
}

export interface CameraItem {
  id: number;
  code: string;
  name: string;
  zone_name: string;
  latitude: number;
  longitude: number;
  status: DeviceStatus;
  fov_degrees: number;
  orientation_degrees: number;
  stream_url?: string;
  snapshot_url?: string;
  has_thermal: boolean;
}

export interface FireStationItem {
  id: number;
  code: string;
  name: string;
  latitude: number;
  longitude: number;
  phone?: string;
  coverage_radius_km: number;
  active_units_count: number;
  available_firefighters_count?: number;
}

export interface IncidentTimelineItem {
  id: number;
  fire_incident_id: number;
  user_id?: number;
  status_from?: string;
  status_to: IncidentStatus;
  action_title?: string;
  note?: string;
  duration_from_previous_seconds?: number;
  recorded_at: string;
  user?: { name: string } | null;
}

export interface FireIncidentItem {
  id: number;
  incident_code: string;
  title: string;
  zone_name: string;
  latitude: number;
  longitude: number;
  altitude?: number;
  risk_score: number;
  severity: IncidentSeverity;
  severity_label?: string;
  status: IncidentStatus;
  status_label?: string;
  ai_confidence: number;
  detected_at: string;
  camera_stream_url?: string;
  station_name?: string;
  fire_station?: FireStationItem | null;
  primary_sensor?: SensorItem | null;
  primary_camera?: CameraItem | null;
  latest_readings?: SensorTelemetry | null;
  timelines?: IncidentTimelineItem[];
  weather_snapshot?: {
    temperature: number;
    humidity: number;
    smoke_ppm: number;
    wind_speed_kmh?: number;
    wind_direction?: string;
  };
  ai_analysis_summary?: {
    summary: string;
    breakdown?: Record<string, any>;
    dispatch?: {
      station_name: string;
      distance_km: number;
      estimated_minutes: number;
    };
  };
}

export interface MapElementsResponse {
  success: boolean;
  timestamp: string;
  counts: {
    active_fires: number;
    sensors: number;
    cameras: number;
    stations: number;
  };
  data: {
    fires: FireIncidentItem[];
    sensors: SensorItem[];
    cameras: CameraItem[];
    stations: FireStationItem[];
  };
}
