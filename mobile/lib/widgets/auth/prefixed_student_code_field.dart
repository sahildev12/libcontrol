import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/config/student_code_style.dart';

class PrefixedStudentCodeField extends StatelessWidget {
  const PrefixedStudentCodeField({
    super.key,
    required this.controller,
    required this.style,
    this.onSubmitted,
  });

  final TextEditingController controller;
  final StudentCodeStyle style;
  final VoidCallback? onSubmitted;

  @override
  Widget build(BuildContext context) {
    final padding = style.padding.clamp(1, 6);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Student Code',
          style: Theme.of(context).textTheme.labelLarge?.copyWith(
            color: AppColors.textDark,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 8),
        DecoratedBox(
          decoration: BoxDecoration(
            color: AppColors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
                decoration: const BoxDecoration(
                  color: AppColors.blueBg,
                  borderRadius: BorderRadius.horizontal(left: Radius.circular(11)),
                ),
                child: Text(
                  style.displayPrefix,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: AppColors.primary,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
              Container(
                width: 1,
                height: 28,
                color: AppColors.border,
              ),
              Expanded(
                child: TextField(
                  controller: controller,
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(6),
                  ],
                  decoration: InputDecoration(
                    hintText: '1'.padLeft(padding, '0'),
                    isDense: true,
                    border: InputBorder.none,
                    enabledBorder: InputBorder.none,
                    focusedBorder: InputBorder.none,
                    disabledBorder: InputBorder.none,
                    errorBorder: InputBorder.none,
                    focusedErrorBorder: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
                  ),
                  onSubmitted: (_) => onSubmitted?.call(),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 6),
        Text(
          'Full code example: ${style.format('1')}',
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }
}
