import 'package:flutter/material.dart';

enum NotificationCategory { announcement, reminder, general }

class NotificationItem {
  const NotificationItem({
    required this.id,
    required this.title,
    required this.message,
    required this.timeLabel,
    required this.category,
    required this.icon,
    required this.iconBackground,
    required this.isRead,
  });

  final String id;
  final String title;
  final String message;
  final String timeLabel;
  final NotificationCategory category;
  final IconData icon;
  final Color iconBackground;
  final bool isRead;
}
