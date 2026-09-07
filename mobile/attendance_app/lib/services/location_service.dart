import 'package:geolocator/geolocator.dart';
import 'package:permission_handler/permission_handler.dart';

class LocationService {
  Future<Position> currentPosition() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw Exception('Turn on location services to mark attendance.');
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (permission == LocationPermission.denied) {
      final status = await Permission.locationWhenInUse.request();
      if (!status.isGranted) {
        throw Exception('Location permission is required.');
      }
    }

    if (permission == LocationPermission.deniedForever) {
      throw Exception('Location permission denied. Enable it in app settings.');
    }

    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
    );
  }

  bool withinGeofence({
    required double lat,
    required double lng,
    required double? fenceLat,
    required double? fenceLng,
    required int radiusMeters,
  }) {
    if (fenceLat == null || fenceLng == null) {
      return true;
    }

    final distance = Geolocator.distanceBetween(lat, lng, fenceLat, fenceLng);
    return distance <= radiusMeters;
  }
}
