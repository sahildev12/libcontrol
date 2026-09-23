import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

class ScanAttendanceSuccess {
  const ScanAttendanceSuccess({
    required this.isCheckOut,
    required this.at,
  });

  static const displayDuration = Duration(hours: 1);

  final bool isCheckOut;
  final DateTime at;

  DateTime get expiresAt => at.add(displayDuration);

  bool get isActive => DateTime.now().isBefore(expiresAt);

  Map<String, dynamic> toJson() => {
    'is_check_out': isCheckOut,
    'at': at.toUtc().toIso8601String(),
  };

  factory ScanAttendanceSuccess.fromJson(Map<String, dynamic> json) {
    final atRaw = json['at'] as String?;
    return ScanAttendanceSuccess(
      isCheckOut: json['is_check_out'] == true,
      at: atRaw != null ? DateTime.parse(atRaw).toLocal() : DateTime.now(),
    );
  }
}

class ScanAttendanceSuccessStore {
  static const _prefsKey = 'scan_attendance_success_v1';

  static Future<ScanAttendanceSuccess?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_prefsKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map<String, dynamic>) {
        return null;
      }
      final success = ScanAttendanceSuccess.fromJson(decoded);
      if (!success.isActive) {
        await clear();
        return null;
      }
      return success;
    } catch (_) {
      await clear();
      return null;
    }
  }

  static Future<void> save(ScanAttendanceSuccess success) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKey, jsonEncode(success.toJson()));
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKey);
  }
}
