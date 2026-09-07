import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_mini_stat.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_progress.dart';

class AttendanceOverviewStrip extends StatelessWidget {
  const AttendanceOverviewStrip({
    super.key,
    required this.rate,
    required this.present,
    required this.absent,
    required this.leave,
  });

  final int rate;
  final int present;
  final int absent;
  final int leave;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
        boxShadow: const [
          BoxShadow(color: Color(0x08000000), blurRadius: 12, offset: Offset(0, 4)),
        ],
      ),
      child: Row(
        children: [
          AttendanceProgress(percentage: rate),
          const SizedBox(width: 12),
          Expanded(
            child: Row(
              children: [
                AttendanceMiniStat(
                  label: 'Present',
                  value: '$present',
                  icon: Icons.person_outline_rounded,
                  backgroundColor: AppColors.successBg,
                  iconColor: AppColors.success,
                ),
                const SizedBox(width: 8),
                AttendanceMiniStat(
                  label: 'Absent',
                  value: '$absent',
                  icon: Icons.person_off_outlined,
                  backgroundColor: AppColors.dangerBg,
                  iconColor: AppColors.danger,
                ),
                const SizedBox(width: 8),
                AttendanceMiniStat(
                  label: 'Leave',
                  value: '$leave',
                  icon: Icons.event_busy_outlined,
                  backgroundColor: AppColors.background,
                  iconColor: AppColors.muted,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
