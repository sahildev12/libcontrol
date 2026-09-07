import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/models/seat.dart';

class SeatTile extends StatelessWidget {
  const SeatTile({
    super.key,
    required this.seat,
    required this.onTap,
  });

  final SeatItem seat;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isOccupied = seat.status == SeatStatus.occupied;
    final isSelected = seat.status == SeatStatus.selected;

    Color background;
    Color foreground;
    Color border;

    if (isSelected) {
      background = AppColors.primary;
      foreground = AppColors.white;
      border = AppColors.primary;
    } else if (isOccupied) {
      background = AppColors.dangerBg;
      foreground = AppColors.danger;
      border = AppColors.dangerBg;
    } else {
      background = AppColors.successBg;
      foreground = AppColors.success;
      border = AppColors.successBg;
    }

    return Material(
      color: background,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: isOccupied ? null : onTap,
        borderRadius: BorderRadius.circular(8),
        child: Ink(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: border),
          ),
          child: Center(
            child: Text(
              seat.code,
              style: TextStyle(
                color: foreground,
                fontWeight: FontWeight.w700,
                fontSize: 13,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
