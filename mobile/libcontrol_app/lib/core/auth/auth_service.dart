import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/config/app_config.dart';
import 'package:libcontrol_app/models/student.dart';
import 'package:libcontrol_app/models/student_lookup.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AuthService extends ChangeNotifier {
  AuthService._();

  static final AuthService instance = AuthService._();

  static const _tokenKey = 'student_auth_token';
  static const _studentKey = 'student_auth_profile';
  static const _biometricEnabledKey = 'student_biometric_enabled';

  final _storage = const FlutterSecureStorage();
  final _api = ApiClient();

  Student? _student;
  String? _token;
  String? _setupToken;
  bool _bootstrapped = false;

  Student? get student => _student;
  String? get token => _token;
  String? get setupToken => _setupToken;
  bool get isAuthenticated => _token != null && _student != null;
  bool get isBootstrapped => _bootstrapped;

  Future<bool> bootstrap() async {
    _token = await _storage.read(key: _tokenKey);
    final studentJson = await _storage.read(key: _studentKey);

    if (_token == null || studentJson == null) {
      _bootstrapped = true;
      notifyListeners();
      return false;
    }

    _api.setToken(_token);

    try {
      final response = await _api.getJson(AppConfig.studentMeUrl, authenticated: true);
      final student = Student.fromJson(response['student'] as Map<String, dynamic>);
      await _persistSession(_token!, student);
      _student = student;
      _bootstrapped = true;
      notifyListeners();
      return true;
    } catch (_) {
      await clearSession();
      _bootstrapped = true;
      notifyListeners();
      return false;
    }
  }

  Future<StudentLookup> checkCode(String studentCode) async {
    final response = await _api.postJson(
      AppConfig.studentCheckCodeUrl,
      body: {'student_code': studentCode.trim()},
    );

    final lookup = StudentLookup.fromJson(response);
    _setupToken = lookup.setupToken;

    return lookup;
  }

  Future<Student> setupPin({
    required String pin,
    required String pinConfirmation,
    bool enableBiometric = true,
  }) async {
    if (_setupToken == null) {
      throw ApiException('Session expired. Enter your student code again.');
    }

    final response = await _api.postJson(
      AppConfig.studentSetupPinUrl,
      body: {
        'setup_token': _setupToken,
        'pin': pin.trim(),
        'pin_confirmation': pinConfirmation.trim(),
        'device_name': 'libcontrol-student-app',
      },
    );

    final token = response['token'] as String;
    final student = Student.fromJson(response['student'] as Map<String, dynamic>);

    _setupToken = null;
    await _persistSession(token, student);
    _token = token;
    _student = student;
    _api.setToken(token);

    if (enableBiometric) {
      await setBiometricEnabled(true);
    }

    notifyListeners();

    return student;
  }

  void clearPinSetup() {
    _setupToken = null;
  }

  Future<Student> login({
    required String studentCode,
    required String pin,
  }) async {
    final response = await _api.postJson(
      AppConfig.studentLoginUrl,
      body: {
        'student_code': studentCode.trim(),
        'pin': pin.trim(),
        'device_name': 'libcontrol-student-app',
      },
    );

    final token = response['token'] as String;
    final student = Student.fromJson(response['student'] as Map<String, dynamic>);

    await _persistSession(token, student);
    _token = token;
    _student = student;
    _api.setToken(token);
    notifyListeners();

    return student;
  }

  Future<void> logout() async {
    if (_token != null) {
      try {
        await _api.postJson(AppConfig.studentLogoutUrl, authenticated: true);
      } catch (_) {
        // Ignore network errors during logout.
      }
    }

    await clearSession();
    notifyListeners();
  }

  Future<void> clearSession() async {
    _token = null;
    _student = null;
    _setupToken = null;
    _api.setToken(null);
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _studentKey);
  }

  Future<void> _persistSession(String token, Student student) async {
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _studentKey, value: student.toJsonString());
  }

  Future<void> setBiometricEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_biometricEnabledKey, enabled);
  }

  Future<bool> isBiometricEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_biometricEnabledKey) ?? false;
  }

  Future<bool> unlockWithStoredSession() async {
    if (!await isBiometricEnabled()) {
      return false;
    }

    return bootstrap();
  }
}
