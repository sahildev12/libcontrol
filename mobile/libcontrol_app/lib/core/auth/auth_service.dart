import 'dart:convert';

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
  static const _setupTokenKey = 'student_pin_setup_token';
  static const _biometricEnabledKey = 'student_biometric_enabled';
  static const _prefsTokenKey = 'student_auth_token_mirror';
  static const _prefsStudentKey = 'student_auth_profile_mirror';
  static const _rememberedStudentKey = 'remembered_student_lookup';

  final _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );
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

  Future<bool> bootstrap({bool validateOnline = true}) async {
    if (!await _loadStoredSession()) {
      _bootstrapped = true;
      notifyListeners();
      return false;
    }

    _bootstrapped = true;

    if (!validateOnline) {
      notifyListeners();
      return true;
    }

    try {
      final response = await _api.getJson(AppConfig.studentMeUrl, authenticated: true);
      final student = Student.fromJson(response['student'] as Map<String, dynamic>);
      await _persistSession(_token!, student);
      _student = student;
      notifyListeners();
      return true;
    } on ApiException catch (error) {
      if (error.statusCode == 401 || error.statusCode == 403) {
        await clearSession();
        notifyListeners();
        return false;
      }

      notifyListeners();
      return true;
    } catch (_) {
      notifyListeners();
      return true;
    }
  }

  void markBootstrapped() {
    _bootstrapped = true;
    notifyListeners();
  }

  Future<bool> hasStoredSession() async {
    return await _readStoredCredentials() != null;
  }

  Future<bool> canUseBiometricQuickLogin() async {
    if (!await isBiometricEnabled()) {
      return false;
    }

    return await hasStoredSession();
  }

  Future<bool> hasRememberedStudent() async {
    final lookup = await getRememberedStudentLookup();
    return lookup != null && lookup.studentCode.isNotEmpty;
  }

  Future<StudentLookup?> getRememberedStudentLookup() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_rememberedStudentKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map<String, dynamic>) {
        return null;
      }

      final lookup = StudentLookup(
        studentCode: decoded['student_code'] as String? ?? '',
        name: decoded['name'] as String? ?? '',
        homeBranch: decoded['home_branch'] as String? ?? '',
        needsPinSetup: decoded['needs_pin_setup'] as bool? ?? false,
      );

      if (lookup.studentCode.isEmpty) {
        return null;
      }

      return lookup;
    } catch (_) {
      return null;
    }
  }

  Future<void> rememberStudentProfile(Student student) async {
    await rememberStudentLookup(
      StudentLookup(
        studentCode: student.id,
        name: student.name,
        homeBranch: student.homeBranch,
        needsPinSetup: false,
      ),
    );
  }

  Future<void> rememberStudentLookup(StudentLookup lookup) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _rememberedStudentKey,
      jsonEncode({
        'student_code': lookup.studentCode,
        'name': lookup.name,
        'home_branch': lookup.homeBranch,
        'needs_pin_setup': lookup.needsPinSetup,
      }),
    );
  }

  Future<void> migrateRememberedStudentFromSession() async {
    if (await hasRememberedStudent()) {
      return;
    }

    final student = await peekStoredStudent();
    if (student != null) {
      await rememberStudentProfile(student);
    }
  }

  Future<Student?> peekStoredStudent() async {
    final credentials = await _readStoredCredentials();
    if (credentials == null) {
      return null;
    }

    try {
      final decoded = jsonDecode(credentials.studentJson);
      if (decoded is Map<String, dynamic>) {
        return Student.fromJson(decoded);
      }
    } catch (_) {
      return null;
    }

    return null;
  }

  Future<StudentLookup> checkCode(String studentCode) async {
    return _checkCode(studentCode, forgotPin: false);
  }

  Future<StudentLookup> requestPinReset(String studentCode) async {
    return _checkCode(studentCode, forgotPin: true);
  }

  Future<StudentLookup> _checkCode(String studentCode, {required bool forgotPin}) async {
    final response = await _api.postJson(
      AppConfig.studentCheckCodeUrl,
      body: {
        'student_code': studentCode.trim(),
        if (forgotPin) 'forgot_pin': true,
      },
    );

    final lookup = StudentLookup.fromJson(response);
    await _storeSetupToken(lookup.setupToken);

    return lookup;
  }

  Future<Student> setupPin({
    required String pin,
    required String pinConfirmation,
    String? setupToken,
    bool? enableBiometric,
  }) async {
    final useBiometric = enableBiometric ?? await isBiometricEnabled();
    final setupTokenValue = await _resolveSetupToken(setupToken);
    if (setupTokenValue == null) {
      throw ApiException('Session expired. Enter your student code again.');
    }

    final response = await _api.postJson(
      AppConfig.studentSetupPinUrl,
      body: {
        'setup_token': setupTokenValue,
        'pin': pin.trim(),
        'pin_confirmation': pinConfirmation.trim(),
        'device_name': 'libcontrol-student-app',
      },
    );

    final authToken = response['token'] as String;
    final student = Student.fromJson(response['student'] as Map<String, dynamic>);

    await _clearSetupToken();
    await setBiometricEnabled(useBiometric);
    await rememberStudentProfile(student);
    await _persistSession(authToken, student);
    _token = authToken;
    _student = student;
    _api.setToken(authToken);

    notifyListeners();

    return student;
  }

  Future<void> clearPinSetup() async {
    await _clearSetupToken();
  }

  Future<Student> login({
    required String studentCode,
    required String pin,
    bool enableBiometric = false,
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

    await setBiometricEnabled(enableBiometric);
    await rememberStudentProfile(student);
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

    await clearSession(clearPinSetup: true, clearRememberedStudent: true);
    await setBiometricEnabled(false);
    notifyListeners();
  }

  Future<void> clearSession({
    bool clearPinSetup = false,
    bool clearRememberedStudent = false,
  }) async {
    _token = null;
    _student = null;
    _api.setToken(null);
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _studentKey);
    await _clearBiometricMirror();

    if (clearPinSetup) {
      await _clearSetupToken();
    }

    if (clearRememberedStudent) {
      await _clearRememberedStudent();
    }
  }

  Future<void> _clearRememberedStudent() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_rememberedStudentKey);
  }

  Future<void> _persistSession(String token, Student student) async {
    final studentJson = student.toJsonString();

    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _studentKey, value: studentJson);

    if (await isBiometricEnabled()) {
      await _mirrorSession(token, studentJson);
    } else {
      await _clearBiometricMirror();
    }
  }

  Future<void> _storeSetupToken(String? token) async {
    final normalized = token?.trim();
    if (normalized == null || normalized.isEmpty) {
      await _clearSetupToken();
      return;
    }

    _setupToken = normalized;
    await _storage.write(key: _setupTokenKey, value: normalized);
  }

  Future<String?> _resolveSetupToken(String? setupToken) async {
    final direct = setupToken?.trim();
    if (direct != null && direct.isNotEmpty) {
      _setupToken = direct;
      return direct;
    }

    final inMemory = _setupToken?.trim();
    if (inMemory != null && inMemory.isNotEmpty) {
      return inMemory;
    }

    final stored = await _storage.read(key: _setupTokenKey);
    final normalized = stored?.trim();
    if (normalized != null && normalized.isNotEmpty) {
      _setupToken = normalized;
      return normalized;
    }

    return null;
  }

  Future<void> _clearSetupToken() async {
    _setupToken = null;
    await _storage.delete(key: _setupTokenKey);
  }

  Future<void> setBiometricEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_biometricEnabledKey, enabled);

    if (!enabled) {
      await _clearBiometricMirror();
      return;
    }

    if (_token != null && _student != null) {
      await _mirrorSession(_token!, _student!.toJsonString());
    }
  }

  Future<bool> isBiometricEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_biometricEnabledKey) ?? false;
  }

  Future<bool> unlockWithStoredSession() async {
    if (!await isBiometricEnabled()) {
      return false;
    }

    if (!await hasStoredSession()) {
      return false;
    }

    final unlocked = await bootstrap(validateOnline: false);
    if (unlocked) {
      _refreshSessionInBackground();
    }

    return unlocked;
  }

  Future<bool> _loadStoredSession() async {
    final credentials = await _readStoredCredentials();
    if (credentials == null) {
      return false;
    }

    try {
      final decoded = jsonDecode(credentials.studentJson);
      if (decoded is! Map<String, dynamic>) {
        return false;
      }

      _token = credentials.token;
      _student = Student.fromJson(decoded);
      _api.setToken(credentials.token);

      return true;
    } catch (_) {
      return false;
    }
  }

  Future<void> _refreshSessionInBackground() async {
    if (_token == null) {
      return;
    }

    try {
      final response = await _api.getJson(AppConfig.studentMeUrl, authenticated: true);
      final student = Student.fromJson(response['student'] as Map<String, dynamic>);
      await _persistSession(_token!, student);
      _student = student;
      notifyListeners();
    } catch (_) {
      // Keep the local biometric session even if refresh fails offline.
    }
  }

  Future<_StoredCredentials?> _readStoredCredentials() async {
    final secureToken = await _storage.read(key: _tokenKey);
    final secureStudent = await _storage.read(key: _studentKey);
    if (_credentialsAreValid(secureToken, secureStudent)) {
      return _StoredCredentials(
        token: secureToken!,
        studentJson: secureStudent!,
      );
    }

    final prefs = await SharedPreferences.getInstance();
    final mirroredToken = prefs.getString(_prefsTokenKey);
    final mirroredStudent = prefs.getString(_prefsStudentKey);
    if (_credentialsAreValid(mirroredToken, mirroredStudent)) {
      return _StoredCredentials(
        token: mirroredToken!,
        studentJson: mirroredStudent!,
      );
    }

    return null;
  }

  bool _credentialsAreValid(String? token, String? studentJson) {
    return token != null &&
        token.isNotEmpty &&
        studentJson != null &&
        studentJson.isNotEmpty;
  }

  Future<void> _mirrorSession(String token, String studentJson) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsTokenKey, token);
    await prefs.setString(_prefsStudentKey, studentJson);
  }

  Future<void> _clearBiometricMirror() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsTokenKey);
    await prefs.remove(_prefsStudentKey);
  }
}

class _StoredCredentials {
  const _StoredCredentials({
    required this.token,
    required this.studentJson,
  });

  final String token;
  final String studentJson;
}
