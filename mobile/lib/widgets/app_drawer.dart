import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/data/dummy_data.dart';

class AppDrawer extends StatelessWidget {
  const AppDrawer({
    super.key,
    required this.currentIndex,
    required this.onNavigate,
    required this.onNotifications,
  });

  final int currentIndex;
  final ValueChanged<int> onNavigate;
  final VoidCallback onNotifications;

  @override
  Widget build(BuildContext context) {
    final student = AuthService.instance.student ?? DummyData.fallbackStudent;

    return Drawer(
      backgroundColor: AppColors.white,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 12),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: AppColors.primaryBg,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.menu_book_rounded, color: AppColors.primary),
                  ),
                  const SizedBox(width: 12),
                  Text('LibControl', style: Theme.of(context).textTheme.titleLarge),
                ],
              ),
            ),
            const Divider(),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                children: [
                  _DrawerItem(
                    icon: Icons.home_outlined,
                    label: 'Home',
                    selected: currentIndex == 0,
                    onTap: () => onNavigate(0),
                  ),
                  _DrawerItem(
                    icon: Icons.calendar_month_outlined,
                    label: 'My Attendance',
                    selected: currentIndex == 1,
                    onTap: () => onNavigate(1),
                  ),
                  _DrawerItem(
                    icon: Icons.event_seat_outlined,
                    label: 'My Seat',
                    selected: currentIndex == 3,
                    onTap: () => onNavigate(3),
                  ),
                  _DrawerItem(
                    icon: Icons.qr_code_scanner_outlined,
                    label: 'Scan QR',
                    selected: currentIndex == 2,
                    onTap: () => onNavigate(2),
                  ),
                  _DrawerItem(
                    icon: Icons.notifications_none_rounded,
                    label: 'Notifications',
                    selected: false,
                    onTap: onNotifications,
                  ),
                  _DrawerItem(
                    icon: Icons.person_outline_rounded,
                    label: 'My Profile',
                    selected: currentIndex == 4,
                    onTap: () => onNavigate(4),
                  ),
                ],
              ),
            ),
            Container(
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.background,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: AppColors.primaryBg,
                    child: Text(
                      student.name.characters.first,
                      style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          student.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        Text(student.id, style: Theme.of(context).textTheme.bodySmall),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.selected = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: selected ? AppColors.primary : AppColors.textSecondary),
      title: Text(
        label,
        style: TextStyle(
          color: selected ? AppColors.primary : AppColors.textDark,
          fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
        ),
      ),
      selected: selected,
      selectedTileColor: AppColors.primaryBg,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      onTap: onTap,
    );
  }
}
