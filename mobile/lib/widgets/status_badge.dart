import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class StatusBadge extends StatelessWidget {
  const StatusBadge({
    super.key,
    required this.label,
    required this.backgroundColor,
    required this.foregroundColor,
  });

  const StatusBadge.success({super.key, required String label})
      : label = label,
        backgroundColor = AppColors.successBg,
        foregroundColor = AppColors.success;

  const StatusBadge.warning({super.key, required String label})
      : label = label,
        backgroundColor = AppColors.warningBg,
        foregroundColor = AppColors.warning;

  const StatusBadge.danger({super.key, required String label})
      : label = label,
        backgroundColor = AppColors.dangerBg,
        foregroundColor = AppColors.danger;

  const StatusBadge.primary({super.key, required String label})
      : label = label,
        backgroundColor = AppColors.primaryBg,
        foregroundColor = AppColors.primary;

  final String label;
  final Color backgroundColor;
  final Color foregroundColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: foregroundColor,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
