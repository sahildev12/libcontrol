import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class AppHeader extends StatelessWidget {
  const AppHeader({
    super.key,
    this.title,
    this.showBack = false,
    this.showDrawer = false,
    this.showSettings = false,
    this.onSettingsTap,
    this.onDrawerTap,
    this.greeting,
    this.subtitle,
  });

  final String? title;
  final bool showBack;
  final bool showDrawer;
  final bool showSettings;
  final VoidCallback? onSettingsTap;
  final VoidCallback? onDrawerTap;
  final String? greeting;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    if (greeting != null) {
      return _DashboardHeader(
        greeting: greeting!,
        subtitle: subtitle ?? '',
        showDrawer: showDrawer,
        onDrawerTap: onDrawerTap,
      );
    }

    return Row(
      children: [
        if (showBack)
          IconButton(
            onPressed: () => Navigator.of(context).maybePop(),
            icon: const Icon(Icons.arrow_back_rounded),
            style: IconButton.styleFrom(
              backgroundColor: AppColors.white,
              side: const BorderSide(color: AppColors.border),
            ),
          ),
        if (showDrawer)
          IconButton(
            onPressed: onDrawerTap,
            icon: const Icon(Icons.menu_rounded),
            color: AppColors.textDark,
          ),
        Expanded(
          child: Text(
            title ?? '',
            style: Theme.of(context).textTheme.titleLarge,
          ),
        ),
        if (showSettings)
          IconButton(
            onPressed: onSettingsTap,
            icon: const Icon(Icons.settings_outlined),
            style: IconButton.styleFrom(
              backgroundColor: AppColors.white,
              side: const BorderSide(color: AppColors.border),
            ),
          ),
      ],
    );
  }
}

class _DashboardHeader extends StatelessWidget {
  const _DashboardHeader({
    required this.greeting,
    required this.subtitle,
    required this.showDrawer,
    this.onDrawerTap,
  });

  final String greeting;
  final String subtitle;
  final bool showDrawer;
  final VoidCallback? onDrawerTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        if (showDrawer)
          IconButton(
            onPressed: onDrawerTap,
            icon: const Icon(Icons.menu_rounded),
            color: AppColors.textDark,
            visualDensity: VisualDensity.compact,
          ),
        CircleAvatar(
          radius: 22,
          backgroundColor: AppColors.primaryBg,
          child: Text(
            subtitle.isNotEmpty ? subtitle.characters.first : 'A',
            style: const TextStyle(
              color: AppColors.primary,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(greeting, style: Theme.of(context).textTheme.bodySmall),
              Text(
                subtitle,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.titleLarge,
              ),
            ],
          ),
        ),
      ],
    );
  }
}
