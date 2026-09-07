class AppConfig {
  const AppConfig._();

  /// Override with: flutter run --dart-define=API_BASE_URL=http://10.0.2.2/libspace/public
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2/libspace/public',
  );

  static String get studentCheckCodeUrl => '$apiBaseUrl/api/v1/student/auth/check-code';
  static String get studentSetupPinUrl => '$apiBaseUrl/api/v1/student/auth/setup-pin';
  static String get studentLoginUrl => '$apiBaseUrl/api/v1/student/auth/login';
  static String get studentLogoutUrl => '$apiBaseUrl/api/v1/student/auth/logout';
  static String get studentMeUrl => '$apiBaseUrl/api/v1/student/auth/me';
}
