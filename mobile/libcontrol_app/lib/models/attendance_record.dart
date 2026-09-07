enum AttendanceStatus { present, absent, late, notMarked }

enum AttendanceMethod { studentQr, biometric, staff, manual, none }

class AttendanceRecord {
  const AttendanceRecord({
    required this.date,
    required this.status,
    required this.checkInTime,
    this.checkOutTime,
    required this.method,
  });

  final DateTime date;
  final AttendanceStatus status;
  final String checkInTime;
  final String? checkOutTime;
  final AttendanceMethod method;

  String get methodLabel => switch (method) {
        AttendanceMethod.studentQr => 'Student QR',
        AttendanceMethod.biometric => 'Biometric',
        AttendanceMethod.staff => 'Staff',
        AttendanceMethod.manual => 'Manual',
        AttendanceMethod.none => '—',
      };
}
