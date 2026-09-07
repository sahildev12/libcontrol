import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_status_badge.dart';

class AttendanceRecordCard extends StatelessWidget {
  const AttendanceRecordCard({
    super.key,
    required this.record,
    this.onTap,
  });

  final AttendanceRecord record;
  final VoidCallback? onTap;

  String _monthYear(DateTime date) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${months[date.month - 1]} ${date.year}';
  }

  String _dayName(DateTime date) {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    return days[date.weekday - 1];
  }

  Color _dateBackground() {
    return switch (record.status) {
      AttendanceStatus.present => AppColors.successBg,
      AttendanceStatus.absent => AppColors.dangerBg,
      AttendanceStatus.late => AppColors.warningBg,
      AttendanceStatus.notMarked => AppColors.background,
    };
  }

  String _methodLabel() {
    if (record.status == AttendanceStatus.absent) {
      return 'No Record';
    }
    return record.methodLabel;
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
            boxShadow: const [
              BoxShadow(color: Color(0x06000000), blurRadius: 10, offset: Offset(0, 3)),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 72,
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                decoration: BoxDecoration(
                  color: _dateBackground(),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Text(
                      record.date.day.toString().padLeft(2, '0'),
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontSize: 24),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _monthYear(record.date),
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 10),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _dayName(record.date),
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 10),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              AttendanceStatusBadge(status: record.status),
              const Spacer(),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.schedule_rounded,
                        size: 14,
                        color: record.status == AttendanceStatus.absent
                            ? AppColors.muted
                            : AppColors.textSecondary,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        record.checkInTime,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(_methodLabel(), style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
              const SizedBox(width: 6),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary, size: 20),
            ],
          ),
        ),
      ),
    );
  }
}
