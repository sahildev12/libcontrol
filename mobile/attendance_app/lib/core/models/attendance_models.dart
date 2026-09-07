class AttendanceSummary {
  const AttendanceSummary({
    required this.totalStudents,
    required this.present,
    required this.absent,
    required this.qrCheckIns,
    required this.staffCheckIns,
  });

  final int totalStudents;
  final int present;
  final int absent;
  final int qrCheckIns;
  final int staffCheckIns;

  factory AttendanceSummary.fromJson(Map<String, dynamic> json) {
    return AttendanceSummary(
      totalStudents: (json['total_students'] as num?)?.toInt() ?? 0,
      present: (json['present'] as num?)?.toInt() ?? 0,
      absent: (json['absent'] as num?)?.toInt() ?? 0,
      qrCheckIns: (json['qr_check_ins'] as num?)?.toInt() ?? 0,
      staffCheckIns: (json['staff_check_ins'] as num?)?.toInt() ?? 0,
    );
  }
}

class GeofenceSettings {
  const GeofenceSettings({
    required this.staffGpsEnabled,
    this.latitude,
    this.longitude,
    required this.radiusMeters,
  });

  final bool staffGpsEnabled;
  final double? latitude;
  final double? longitude;
  final int radiusMeters;

  factory GeofenceSettings.fromJson(Map<String, dynamic> json) {
    return GeofenceSettings(
      staffGpsEnabled: json['staff_gps_enabled'] == true,
      latitude: (json['geofence_latitude'] as num?)?.toDouble(),
      longitude: (json['geofence_longitude'] as num?)?.toDouble(),
      radiusMeters: (json['geofence_radius_meters'] as num?)?.toInt() ?? 100,
    );
  }
}

class StudentAttendanceRow {
  StudentAttendanceRow({
    required this.studentId,
    required this.studentCode,
    required this.studentName,
    this.hallName,
    this.seatNumber,
    required this.present,
    this.checkInAt,
    this.methodLabel,
  });

  final int studentId;
  final String studentCode;
  final String studentName;
  final String? hallName;
  final String? seatNumber;
  bool present;
  final String? checkInAt;
  String? methodLabel;

  String get initials {
    final parts = studentName.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
  }

  String? get seatLabel {
    if (hallName == null && seatNumber == null) return null;
    if (hallName != null && seatNumber != null) return '$hallName · #$seatNumber';
    return hallName ?? seatNumber;
  }

  factory StudentAttendanceRow.fromJson(Map<String, dynamic> json) {
    return StudentAttendanceRow(
      studentId: json['student_id'] as int,
      studentCode: json['student_code'] as String? ?? '',
      studentName: json['student_name'] as String? ?? 'Student',
      hallName: json['hall_name'] as String?,
      seatNumber: json['seat_number']?.toString(),
      present: json['present'] == true,
      checkInAt: json['check_in_at'] as String?,
      methodLabel: json['method_label'] as String?,
    );
  }
}

class AttendanceContext {
  const AttendanceContext({
    required this.branchName,
    required this.settings,
    required this.summary,
    required this.students,
  });

  final String branchName;
  final GeofenceSettings settings;
  final AttendanceSummary summary;
  final List<StudentAttendanceRow> students;

  factory AttendanceContext.fromJson(Map<String, dynamic> json) {
    final students = (json['students'] as List? ?? [])
        .map((item) => StudentAttendanceRow.fromJson(Map<String, dynamic>.from(item as Map)))
        .toList();

    return AttendanceContext(
      branchName: (json['branch'] as Map?)?['name'] as String? ?? 'Branch',
      settings: GeofenceSettings.fromJson(Map<String, dynamic>.from(json['settings'] as Map)),
      summary: AttendanceSummary.fromJson(Map<String, dynamic>.from(json['summary'] as Map)),
      students: students,
    );
  }
}
