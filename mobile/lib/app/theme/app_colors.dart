import 'package:flutter/material.dart';

abstract final class AppColors {
  static const Color primary = Color(0xFF243A8B);
  static const Color primaryDark = Color(0xFF0C0048);
  static const Color secondary = Color(0xFFFECF25);
  static const Color textDark = Color(0xFF0C0048);
  static const Color textSecondary = Color(0xFF64748B);
  static const Color background = Color(0xFFF4F6FA);
  static const Color white = Color(0xFFFFFFFF);
  static const Color success = Color(0xFF3A995D);
  static const Color warning = Color(0xFFEBB862);
  static const Color danger = Color(0xFFC15858);
  static const Color border = Color(0xFFE2E8F0);
  static const Color muted = Color(0xFF94A3B8);

  static const Color successBg = Color(0xFFE8F5EC);
  static const Color warningBg = Color(0xFFFFF4DE);
  static const Color dangerBg = Color(0xFFFBEAEA);
  static const Color primaryBg = Color(0xFFE8EDF8);
  static const Color actionCardBg = Color(0xFFF0F3FA);
  static const Color statusCardBg = Color(0xFFE8F5EC);
  static const Color statusCardBorder = Color(0xFFB8D9C4);
  static const Color purpleBg = Color(0xFFF0F3FA);
  static const Color blueBg = Color(0xFFE8EDF8);
  static const Color orangeBg = Color(0xFFFFF4DE);
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
