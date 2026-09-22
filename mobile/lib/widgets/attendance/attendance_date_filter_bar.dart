import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class AttendanceDateFilterBar extends StatelessWidget {
  const AttendanceDateFilterBar({
    super.key,
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: AppColors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              const Icon(Icons.calendar_month_outlined, size: 20, color: AppColors.primary),
              const SizedBox(width: 10),
              Expanded(
                child: Text(label, style: Theme.of(context).textTheme.bodyMedium),
              ),
              const Icon(Icons.keyboard_arrow_down_rounded, color: AppColors.textSecondary),
            ],
          ),
        ),
      ),
    );
  }
}
