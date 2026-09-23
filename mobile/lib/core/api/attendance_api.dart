import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/core/config/app_config.dart';
import 'package:libcontrol_app/models/attendance_record.dart';

class AttendanceDashboard {
  const AttendanceDashboard({
    required this.rate,
    required this.present,
    required this.absent,
    required this.late,
    required this.recent,
    required this.records,
    required this.marks,
  });

  final int rate;
  final int present;
  final int absent;
  final int late;
  final List<AttendanceRecord> recent;
  final List<AttendanceRecord> records;
  final Map<DateTime, AttendanceStatus> marks;
}

class AttendanceApi {
  AttendanceApi({ApiClient? client}) : _client = client ?? ApiClient();

  final ApiClient _client;

  static AttendanceDashboard empty() {
    return AttendanceDashboard(
      rate: 0,
      present: 0,
      absent: 0,
      late: 0,
      recent: const [],
      records: const [],
      marks: const {},
    );
  }

  Future<AttendanceDashboard> fetchDashboard() async {
    final token = AuthService.instance.token;
    if (token == null) {
      throw ApiException('Please sign in again.');
    }

    _client.setToken(token);
    final data = await _client.getJson(AppConfig.studentAttendanceUrl, authenticated: true);

    final summary = data['summary'] as Map<String, dynamic>? ?? {};
    final recentJson = data['recent'] as List<dynamic>? ?? [];
    final recordsJson = data['records'] as List<dynamic>? ?? recentJson;
    final marksJson = data['marks'] as Map<String, dynamic>? ?? {};

    final marks = <DateTime, AttendanceStatus>{};
    for (final entry in marksJson.entries) {
      final date = DateTime.tryParse(entry.key);
      if (date == null) {
        continue;
      }
      marks[DateTime(date.year, date.month, date.day)] = _statusFromApi(entry.value as String?);
    }

    return AttendanceDashboard(
      rate: (summary['rate'] as num?)?.toInt() ?? 0,
      present: (summary['present'] as num?)?.toInt() ?? 0,
      absent: (summary['absent'] as num?)?.toInt() ?? 0,
      late: (summary['late'] as num?)?.toInt() ?? 0,
      recent: recentJson.map((item) => _recordFromRecent(item as Map<String, dynamic>)).toList(),
      records: recordsJson.map((item) => _recordFromRecent(item as Map<String, dynamic>)).toList(),
      marks: marks,
    );
  }

  Future<String> checkInFromQr(String rawValue) async {
    final token = AuthService.instance.token;
    if (token == null) {
      throw ApiException('Please sign in again.');
    }

    _client.setToken(token);

    final body = <String, dynamic>{'qr_url': rawValue.trim()};
    final qrToken = _extractToken(rawValue);
    if (qrToken != null) {
      body['qr_token'] = qrToken;
    }

    final data = await _client.postJson(
      AppConfig.studentAttendanceCheckInUrl,
      body: body,
      authenticated: true,
    );

    return data['message'] as String? ?? 'Check-in successful.';
  }

  Future<String> checkOutFromQr(String rawValue) async {
    final token = AuthService.instance.token;
    if (token == null) {
      throw ApiException('Please sign in again.');
    }

    _client.setToken(token);

    final body = <String, dynamic>{'qr_url': rawValue.trim()};
    final qrToken = _extractToken(rawValue);
    if (qrToken != null) {
      body['qr_token'] = qrToken;
    }

    final data = await _client.postJson(
      AppConfig.studentAttendanceCheckOutUrl,
      body: body,
      authenticated: true,
    );

    return data['message'] as String? ?? 'Check-out successful.';
  }

  AttendanceStatus _statusFromApi(String? value) {
    switch (value) {
      case 'late':
        return AttendanceStatus.late;
      case 'absent':
        return AttendanceStatus.absent;
      case 'present':
        return AttendanceStatus.present;
      default:
        return AttendanceStatus.notMarked;
    }
  }

  AttendanceRecord _recordFromRecent(Map<String, dynamic> json) {
    final status = _statusFromApi(json['status'] as String?);
    final dateLabel = json['date_label'] as String? ?? '';
    final checkIn = json['check_in_at'] as String? ?? '—';

    return AttendanceRecord(
      date: _parseDateLabel(dateLabel),
      status: status,
      checkInTime: checkIn,
      method: AttendanceMethod.studentQr,
    );
  }

  DateTime _parseDateLabel(String label) {
    final parts = label.split(' ');
    if (parts.length >= 3) {
      const months = {
        'Jan': 1,
        'Feb': 2,
        'Mar': 3,
        'Apr': 4,
        'May': 5,
        'Jun': 6,
        'Jul': 7,
        'Aug': 8,
        'Sep': 9,
        'Oct': 10,
        'Nov': 11,
        'Dec': 12,
      };
      final month = months[parts[0]];
      final day = int.tryParse(parts[1].replaceAll(',', ''));
      final year = int.tryParse(parts[2]);
      if (month != null && day != null && year != null) {
        return DateTime(year, month, day);
      }
    }

    return DateTime.now();
  }

  String? _extractToken(String raw) {
    final uri = Uri.tryParse(raw.trim());
    if (uri == null) {
      return null;
    }

    final match = RegExp(r'/attendance/check-in/([^/?#]+)').firstMatch(uri.path);
    return match?.group(1);
  }
}
