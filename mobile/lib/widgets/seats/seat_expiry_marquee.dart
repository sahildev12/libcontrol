import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class SeatExpiryMarquee extends StatefulWidget {
  const SeatExpiryMarquee({
    super.key,
    required this.message,
  });

  final String message;

  @override
  State<SeatExpiryMarquee> createState() => _SeatExpiryMarqueeState();
}

class _SeatExpiryMarqueeState extends State<SeatExpiryMarquee> with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 14),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 40,
      decoration: BoxDecoration(
        color: AppColors.warningBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.warning.withValues(alpha: 0.35)),
      ),
      clipBehavior: Clip.hardEdge,
      child: Row(
        children: [
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 10),
            child: Icon(Icons.warning_amber_rounded, color: AppColors.warning, size: 20),
          ),
          Expanded(
            child: AnimatedBuilder(
              animation: _controller,
              builder: (context, child) {
                return Transform.translate(
                  offset: Offset(-_controller.value * 200, 0),
                  child: child,
                );
              },
              child: Row(
                children: [
                  Text(
                    widget.message,
                    style: const TextStyle(
                      color: Color(0xFF8A5A00),
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(width: 48),
                  Text(
                    widget.message,
                    style: const TextStyle(
                      color: Color(0xFF8A5A00),
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
