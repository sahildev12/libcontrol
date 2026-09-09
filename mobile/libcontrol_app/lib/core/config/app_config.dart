import 'package:libcontrol_app/core/config/server_config.dart';

class AppConfig {
  const AppConfig._();

  static String get studentCheckCodeUrl =>
      '${ServerConfig.instance.apiBaseUrl}/api/v1/student/auth/check-code';

  static String get studentSetupPinUrl =>
      '${ServerConfig.instance.apiBaseUrl}/api/v1/student/auth/setup-pin';

  static String get studentLoginUrl =>
      '${ServerConfig.instance.apiBaseUrl}/api/v1/student/auth/login';

  static String get studentLogoutUrl =>
      '${ServerConfig.instance.apiBaseUrl}/api/v1/student/auth/logout';

  static String get studentMeUrl =>
      '${ServerConfig.instance.apiBaseUrl}/api/v1/student/auth/me';
}
