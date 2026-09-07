import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
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
    final student = DummyData.student;
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
              onNotificationTap: () => Navigator.of(context).pushNamed(AppRoutes.notifications),
              trailingBadge: unreadCount,
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
                  title: 'Library Rules',
                  icon: Icons.rule_folder_outlined,
                  onTap: () {},
                ),
                ActionCard(
                  title: 'Announcements',
                  icon: Icons.campaign_outlined,
                  badge: unreadCount > 0 ? '$unreadCount' : null,
                  onTap: () => Navigator.of(context).pushNamed(AppRoutes.notifications),
                ),
                ActionCard(
                  title: 'Feedback',
                  icon: Icons.feedback_outlined,
                  onTap: () {},
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
