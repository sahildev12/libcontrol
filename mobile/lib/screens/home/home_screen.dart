import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/widgets/action_card.dart';
import 'package:libcontrol_app/widgets/app_header.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({
    super.key,
    this.onOpenDrawer,
    this.onNavigateTab,
  });

  final VoidCallback? onOpenDrawer;
  final ValueChanged<int>? onNavigateTab;

  @override
  Widget build(BuildContext context) {
    final student = AuthService.instance.student ?? DummyData.fallbackStudent;
    final unreadCount = DummyData.notifications.where((item) => !item.isRead).length;

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AppHeader(
              greeting: 'Good Morning',
              subtitle: student.name,
              showDrawer: true,
              onDrawerTap: onOpenDrawer,
            ),
            const SizedBox(height: 16),
            Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () => onNavigateTab?.call(2),
                borderRadius: BorderRadius.circular(16),
                child: Ink(
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [AppColors.primary, AppColors.primaryDark],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: const [
                      BoxShadow(color: Color(0x33243A8B), blurRadius: 12, offset: Offset(0, 6)),
                    ],
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.secondary,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.qr_code_scanner_rounded, color: AppColors.primaryDark, size: 28),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Ready to check in?',
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(color: AppColors.white),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Scan your branch attendance QR from the Scan tab.',
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.white70),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.05,
              children: [
                ActionCard(
                  title: 'My Attendance',
                  icon: Icons.calendar_month_outlined,
                  onTap: () => onNavigateTab?.call(1),
                ),
                ActionCard(
                  title: 'Scan QR',
                  icon: Icons.qr_code_scanner_outlined,
                  onTap: () => onNavigateTab?.call(2),
                ),
                ActionCard(
                  title: 'My Seat',
                  icon: Icons.event_seat_outlined,
                  onTap: () => onNavigateTab?.call(3),
                ),
                ActionCard(
                  title: 'Notifications',
                  icon: Icons.notifications_outlined,
                  badge: unreadCount > 0 ? '$unreadCount' : null,
                  onTap: () => Navigator.of(context).pushNamed(AppRoutes.notifications),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
