import 'package:flutter/material.dart';

abstract final class AppColors {
  static const Color primary = Color(0xFF4F46E5);
  static const Color secondary = Color(0xFFFACC15);
  static const Color textDark = Color(0xFF111827);
  static const Color textSecondary = Color(0xFF64748B);
  static const Color background = Color(0xFFF8FAFC);
  static const Color white = Color(0xFFFFFFFF);
  static const Color success = Color(0xFF16A34A);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);
  static const Color border = Color(0xFFE2E8F0);
  static const Color muted = Color(0xFF94A3B8);

  static const Color successBg = Color(0xFFDCFCE7);
  static const Color warningBg = Color(0xFFFEF3C7);
  static const Color dangerBg = Color(0xFFFEE2E2);
  static const Color primaryBg = Color(0xFFEEF2FF);
  static const Color actionCardBg = Color(0xFFF5F7FF);
  static const Color statusCardBg = Color(0xFFF0FDF4);
  static const Color statusCardBorder = Color(0xFFBBF7D0);
  static const Color purpleBg = Color(0xFFF3E8FF);
  static const Color blueBg = Color(0xFFDBEAFE);
  static const Color orangeBg = Color(0xFFFFEDD5);
}

abstract final class AppSpacing {
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 20;
  static const double xxl = 24;
  static const double xxxl = 32;
}

abstract final class AppRadius {
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
}
