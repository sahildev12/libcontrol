import 'package:flutter/material.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/screens/attendance/all_attendance_screen.dart';
import 'package:libcontrol_app/screens/attendance/attendance_detail_screen.dart';
import 'package:libcontrol_app/screens/attendance/attendance_help_screen.dart';
import 'package:libcontrol_app/screens/auth/login_screen.dart';
import 'package:libcontrol_app/screens/main_shell.dart';
import 'package:libcontrol_app/screens/notifications/notifications_screen.dart';

abstract final class AppRoutes {
  static const login = '/login';
  static const home = '/';
  static const notifications = '/notifications';
  static const allAttendance = '/attendance/all';
  static const attendanceDetail = '/attendance/detail';
  static const attendanceHelp = '/attendance/help';

  static Map<String, WidgetBuilder> get routes => {
        login: (_) => const LoginScreen(),
        home: (_) => const MainShell(),
        notifications: (_) => const NotificationsScreen(),
        allAttendance: (_) => const AllAttendanceScreen(),
        attendanceHelp: (_) => const AttendanceHelpScreen(),
        attendanceDetail: (context) {
          final record = ModalRoute.of(context)!.settings.arguments! as AttendanceRecord;
          return AttendanceDetailScreen(record: record);
        },
      };
}
