import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_status_badge.dart';

class AttendanceRecordTile extends StatelessWidget {
  const AttendanceRecordTile({
    super.key,
    required this.record,
    this.onTap,
    this.showDivider = true,
  });

  final AttendanceRecord record;
  final VoidCallback? onTap;
  final bool showDivider;

  String _formatDate(DateTime date) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${date.day.toString().padLeft(2, '0')} ${months[date.month - 1]} ${date.year}';
  }

  String _dayName(DateTime date) {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    return days[date.weekday - 1];
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: showDivider
              ? const BoxDecoration(
                  border: Border(bottom: BorderSide(color: AppColors.border)),
                )
              : null,
          child: Row(
            children: [
              Expanded(
                flex: 3,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_formatDate(record.date), style: Theme.of(context).textTheme.bodyMedium),
                    const SizedBox(height: 2),
                    Text(_dayName(record.date), style: Theme.of(context).textTheme.bodySmall),
                  ],
                ),
              ),
              AttendanceStatusBadge(status: record.status),
              const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(record.checkInTime, style: Theme.of(context).textTheme.bodyMedium),
                    const SizedBox(height: 2),
                    Text(record.methodLabel, style: Theme.of(context).textTheme.bodySmall),
                  ],
                ),
              ),
              const SizedBox(width: 4),
              const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary, size: 20),
            ],
          ),
        ),
      ),
    );
  }
}
