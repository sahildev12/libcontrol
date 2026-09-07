import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/models/notification_item.dart';
import 'package:libcontrol_app/models/seat.dart';
import 'package:libcontrol_app/models/student.dart';

abstract final class DummyData {
  static const student = Student(
    name: 'Aarav Sharma',
    id: 'MLC-001',
    type: 'Student',
    email: 'aarav.sharma@example.com',
    phone: '98765 43210',
    homeBranch: 'Main Library Center',
    currentSeat: 'A7',
    currentHall: 'Reading Hall — Ground Floor',
    planValidTill: '23 Sep 2026',
    isCheckedIn: true,
    checkedInAt: '09:14 AM',
    avatarUrl: '',
  );

  static const libraryHours = '6:00 AM – 10:00 PM';
  static const isLibraryOpen = true;
  static const libraryPhone = '+91 98765 12345';
  static const libraryEmail = 'support@mainlibrary.com';

  static const attendanceSummary = (
    present: 23,
    absent: 2,
    late: 1,
    rate: 92,
  );

  static final attendanceRecords = <AttendanceRecord>[
    AttendanceRecord(
      date: DateTime(2026, 9, 4),
      status: AttendanceStatus.present,
      checkInTime: '09:14 AM',
      method: AttendanceMethod.studentQr,
    ),
    AttendanceRecord(
      date: DateTime(2026, 9, 3),
      status: AttendanceStatus.present,
      checkInTime: '09:07 AM',
      method: AttendanceMethod.biometric,
    ),
    AttendanceRecord(
      date: DateTime(2026, 9, 2),
      status: AttendanceStatus.absent,
      checkInTime: '—',
      method: AttendanceMethod.none,
    ),
    AttendanceRecord(
      date: DateTime(2026, 9, 1),
      status: AttendanceStatus.present,
      checkInTime: '09:21 AM',
      method: AttendanceMethod.studentQr,
    ),
    AttendanceRecord(
      date: DateTime(2026, 8, 31),
      status: AttendanceStatus.present,
      checkInTime: '09:18 AM',
      method: AttendanceMethod.studentQr,
    ),
  ];

  static final attendanceMarks = <DateTime, AttendanceStatus>{
    DateTime(2026, 9, 1): AttendanceStatus.present,
    DateTime(2026, 9, 2): AttendanceStatus.absent,
    DateTime(2026, 9, 3): AttendanceStatus.present,
    DateTime(2026, 9, 4): AttendanceStatus.present,
    DateTime(2026, 9, 5): AttendanceStatus.present,
    DateTime(2026, 8, 31): AttendanceStatus.present,
  };

  static final notifications = <NotificationItem>[
    NotificationItem(
      id: '1',
      title: 'Library will be closed',
      message: 'Tomorrow (5 Sep) due to maintenance.',
      timeLabel: '2 hours ago',
      category: NotificationCategory.announcement,
      icon: Icons.campaign_outlined,
      iconBackground: AppColors.blueBg,
      isRead: false,
    ),
    NotificationItem(
      id: '2',
      title: 'New seats available',
      message: 'Quiet Zone — First Floor has new seats available.',
      timeLabel: 'Yesterday',
      category: NotificationCategory.announcement,
      icon: Icons.event_seat_outlined,
      iconBackground: AppColors.primaryBg,
      isRead: false,
    ),
    NotificationItem(
      id: '3',
      title: 'Overdue Notice',
      message: 'You have 2 books overdue.',
      timeLabel: '2 days ago',
      category: NotificationCategory.reminder,
      icon: Icons.menu_book_outlined,
      iconBackground: AppColors.orangeBg,
      isRead: true,
    ),
    NotificationItem(
      id: '4',
      title: 'Rule Update',
      message: 'Food is not allowed inside the library.',
      timeLabel: '3 days ago',
      category: NotificationCategory.announcement,
      icon: Icons.rule_folder_outlined,
      iconBackground: AppColors.purpleBg,
      isRead: true,
    ),
    NotificationItem(
      id: '5',
      title: 'Seat Booked',
      message: 'Your seat A7 has been confirmed.',
      timeLabel: '4 days ago',
      category: NotificationCategory.general,
      icon: Icons.check_circle_outline,
      iconBackground: AppColors.successBg,
      isRead: true,
    ),
  ];

  static List<SeatItem> seatsForHall(String hall) {
    const codes = [
      'A1', 'A2', 'A3', 'A4', 'A5',
      'A6', 'A7', 'A8', 'A9', 'A10',
      'B1', 'B2', 'B3', 'B4', 'B5',
      'B6', 'B7', 'B8', 'B9', 'B10',
    ];

    const occupied = {'A2', 'A4', 'A9', 'B1', 'B5', 'B8'};

    return codes
        .map(
          (code) => SeatItem(
            code: code,
            status: occupied.contains(code) ? SeatStatus.occupied : SeatStatus.available,
          ),
        )
        .toList();
  }

  static const halls = [
    'Reading Hall — Ground Floor',
    'Quiet Zone — First Floor',
    'Group Study — Second Floor',
  ];
}
