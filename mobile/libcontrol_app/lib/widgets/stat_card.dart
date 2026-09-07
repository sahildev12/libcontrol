import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class StatCard extends StatelessWidget {
  const StatCard({
    super.key,
    required this.label,
    required this.value,
    this.trailing,
    this.valueColor,
  });

  final String label;
  final String value;
  final Widget? trailing;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.bodySmall),
          const SizedBox(height: 8),
          if (trailing != null)
            trailing!
          else
            Text(
              value,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    color: valueColor ?? AppColors.textDark,
                  ),
            ),
        ],
      ),
    );
  }
}
