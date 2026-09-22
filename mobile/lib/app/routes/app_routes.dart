import 'package:flutter/material.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/screens/attendance/all_attendance_screen.dart';
import 'package:libcontrol_app/screens/attendance/attendance_detail_screen.dart';
import 'package:libcontrol_app/screens/attendance/attendance_help_screen.dart';
import 'package:libcontrol_app/screens/auth/auth_gate.dart';
import 'package:libcontrol_app/screens/auth/student_code_screen.dart';
import 'package:libcontrol_app/screens/notifications/notifications_screen.dart';
import 'package:libcontrol_app/screens/profile/edit_profile_screen.dart';
import 'package:libcontrol_app/screens/profile/profile_settings_screen.dart';

abstract final class AppRoutes {
  static const login = '/login';
  static const home = '/';
  static const notifications = '/notifications';
  static const allAttendance = '/attendance/all';
  static const attendanceDetail = '/attendance/detail';
  static const attendanceHelp = '/attendance/help';
  static const profileSettings = '/profile/settings';
  static const editProfile = '/profile/edit';

  static Map<String, WidgetBuilder> get routes => {
        login: (_) => const StudentCodeScreen(),
        home: (_) => const AuthGate(),
        notifications: (_) => const NotificationsScreen(),
        allAttendance: (_) => const AllAttendanceScreen(),
        attendanceHelp: (_) => const AttendanceHelpScreen(),
        profileSettings: (_) => const ProfileSettingsScreen(),
        editProfile: (_) => const EditProfileScreen(),
        attendanceDetail: (context) {
          final record = ModalRoute.of(context)!.settings.arguments! as AttendanceRecord;
          return AttendanceDetailScreen(record: record);
        },
      };
}
