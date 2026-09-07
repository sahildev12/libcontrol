import 'package:flutter/material.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/models/notification_item.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';
import 'package:libcontrol_app/widgets/notifications/notification_filter_tabs.dart';
import 'package:libcontrol_app/widgets/notification_card.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  int _tabIndex = 0;

  List<NotificationItem> get _filtered {
    return switch (_tabIndex) {
      1 => DummyData.notifications
          .where((item) => item.category == NotificationCategory.announcement)
          .toList(),
      2 => DummyData.notifications
          .where((item) => item.category == NotificationCategory.reminder)
          .toList(),
      _ => DummyData.notifications,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: CenteredPageHeader(
                title: 'Notifications',
                subtitle: 'Stay updated with library news',
                onBack: () => Navigator.of(context).pop(),
              ),
            ),
            const SizedBox(height: 16),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: NotificationFilterTabs(
                selectedIndex: _tabIndex,
                onChanged: (index) => setState(() => _tabIndex = index),
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                itemCount: _filtered.length,
                separatorBuilder: (_, _) => const SizedBox(height: 12),
                itemBuilder: (context, index) => NotificationCard(item: _filtered[index]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
