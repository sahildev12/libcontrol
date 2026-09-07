import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class AttendanceProgress extends StatelessWidget {
  const AttendanceProgress({super.key, required this.percentage});

  final int percentage;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 108,
      height: 108,
      child: Stack(
        alignment: Alignment.center,
        children: [
          SizedBox(
            width: 108,
            height: 108,
            child: CircularProgressIndicator(
              value: percentage / 100,
              strokeWidth: 10,
              strokeCap: StrokeCap.round,
              backgroundColor: AppColors.border,
              color: AppColors.success,
            ),
          ),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '$percentage%',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(fontSize: 22),
              ),
              const SizedBox(height: 2),
              Text(
                'Attendance\nRate',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 11),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
