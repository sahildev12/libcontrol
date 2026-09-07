import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/models/attendance_record.dart';

class AttendanceStatusBadge extends StatelessWidget {
  const AttendanceStatusBadge({super.key, required this.status});

  final AttendanceStatus status;

  @override
  Widget build(BuildContext context) {
    final (label, color, bg) = switch (status) {
      AttendanceStatus.present => ('Present', AppColors.success, AppColors.successBg),
      AttendanceStatus.absent => ('Absent', AppColors.danger, AppColors.dangerBg),
      AttendanceStatus.late => ('Late', AppColors.warning, AppColors.warningBg),
      AttendanceStatus.notMarked => ('Not Marked', AppColors.muted, AppColors.background),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}
