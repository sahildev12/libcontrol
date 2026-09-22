import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_mini_stat.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_progress.dart';

class AttendanceSummaryCard extends StatelessWidget {
  const AttendanceSummaryCard({
    super.key,
    required this.rate,
    required this.present,
    required this.absent,
    required this.late,
  });

  final int rate;
  final int present;
  final int absent;
  final int late;

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
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              AttendanceProgress(percentage: rate),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'You are doing great!',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: AppColors.success,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Keep it up for a consistent learning journey.',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
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
                label: 'Late',
                value: '$late',
                icon: Icons.schedule_rounded,
                backgroundColor: AppColors.warningBg,
                iconColor: AppColors.warning,
              ),
            ],
          ),
        ],
      ),
    );
  }
}
