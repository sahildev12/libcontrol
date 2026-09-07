import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class CenteredPageHeader extends StatelessWidget {
  const CenteredPageHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.onBack,
    this.trailing,
  });

  final String title;
  final String? subtitle;
  final VoidCallback? onBack;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 8, 4, 0),
      child: SizedBox(
        height: subtitle != null ? 52 : 44,
        child: Stack(
          alignment: Alignment.center,
          children: [
            if (onBack != null)
              Align(
                alignment: Alignment.centerLeft,
                child: HeaderIconButton(
                  icon: Icons.chevron_left_rounded,
                  onPressed: onBack!,
                ),
              ),
            if (trailing != null)
              Align(
                alignment: Alignment.centerRight,
                child: trailing!,
              ),
            Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(title, style: Theme.of(context).textTheme.titleLarge),
                if (subtitle != null) ...[
                  const SizedBox(height: 2),
                  Text(subtitle!, style: Theme.of(context).textTheme.bodySmall),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class HeaderIconButton extends StatelessWidget {
  const HeaderIconButton({
    super.key,
    required this.icon,
    required this.onPressed,
  });

  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: onPressed,
      icon: Icon(icon, size: 30),
      color: AppColors.textDark,
      visualDensity: VisualDensity.compact,
    );
  }
}
