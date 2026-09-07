import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';

class AppHeader extends StatelessWidget {
  const AppHeader({
    super.key,
    this.title,
    this.showBack = false,
    this.showDrawer = false,
    this.showSettings = false,
    this.showNotification = false,
    this.onNotificationTap,
    this.onSettingsTap,
    this.onDrawerTap,
    this.greeting,
    this.subtitle,
    this.trailingBadge,
  });

  final String? title;
  final bool showBack;
  final bool showDrawer;
  final bool showSettings;
  final bool showNotification;
  final VoidCallback? onNotificationTap;
  final VoidCallback? onSettingsTap;
  final VoidCallback? onDrawerTap;
  final String? greeting;
  final String? subtitle;
  final int? trailingBadge;

  @override
  Widget build(BuildContext context) {
    if (greeting != null) {
      return _DashboardHeader(
        greeting: greeting!,
        subtitle: subtitle ?? '',
        showDrawer: showDrawer,
        onDrawerTap: onDrawerTap,
        onNotificationTap: onNotificationTap,
        trailingBadge: trailingBadge,
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
            style: IconButton.styleFrom(
              backgroundColor: AppColors.white,
              side: const BorderSide(color: AppColors.border),
            ),
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
        if (showNotification)
          Stack(
            clipBehavior: Clip.none,
            children: [
              IconButton(
                onPressed: onNotificationTap,
                icon: const Icon(Icons.notifications_none_rounded),
                style: IconButton.styleFrom(
                  backgroundColor: AppColors.white,
                  side: const BorderSide(color: AppColors.border),
                ),
              ),
              if ((trailingBadge ?? 0) > 0)
                Positioned(
                  right: 8,
                  top: 8,
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.danger,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
            ],
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
    this.onNotificationTap,
    this.trailingBadge,
  });

  final String greeting;
  final String subtitle;
  final bool showDrawer;
  final VoidCallback? onDrawerTap;
  final VoidCallback? onNotificationTap;
  final int? trailingBadge;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (showDrawer)
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: IconButton(
              onPressed: onDrawerTap,
              icon: const Icon(Icons.menu_rounded),
              style: IconButton.styleFrom(
                backgroundColor: AppColors.white,
                side: const BorderSide(color: AppColors.border),
              ),
            ),
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
        Stack(
          clipBehavior: Clip.none,
          children: [
            IconButton(
              onPressed: onNotificationTap,
              icon: const Icon(Icons.notifications_none_rounded),
              style: IconButton.styleFrom(
                backgroundColor: AppColors.white,
                side: const BorderSide(color: AppColors.border),
              ),
            ),
            if ((trailingBadge ?? 0) > 0)
              Positioned(
                right: 8,
                top: 8,
                child: Container(
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(
                    color: AppColors.danger,
                    shape: BoxShape.circle,
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }
}
