import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class SeatActiveBadge extends StatelessWidget {
  const SeatActiveBadge({
    super.key,
    required this.label,
    this.compact = false,
    this.onDark = false,
  });

  final String label;
  final bool compact;
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    final isActive = label.toLowerCase() == 'active';
    final bg = onDark
        ? Colors.white.withValues(alpha: 0.18)
        : (isActive ? AppColors.successBg : AppColors.warningBg);
    final dot = onDark ? AppColors.secondary : (isActive ? AppColors.success : AppColors.warning);
    final textColor = onDark ? Colors.white : (isActive ? AppColors.success : AppColors.warning);

    return Container(
      padding: EdgeInsets.symmetric(horizontal: compact ? 8 : 10, vertical: compact ? 4 : 5),
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
            decoration: BoxDecoration(color: dot, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              color: textColor,
              fontSize: compact ? 11 : 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
