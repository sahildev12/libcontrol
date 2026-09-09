import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:libcontrol_app/core/config/library_student_styles.dart';
import 'package:libcontrol_app/core/config/student_code_style.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ServerConfig extends ChangeNotifier {
  ServerConfig._();

  static final ServerConfig instance = ServerConfig._();

  static const _storageKey = 'library_api_base_url';
  static const _libraryNameKey = 'library_name';
  static const _libraryCodeKey = 'library_code';
  static const _libraryStylesKey = 'library_student_styles';
  static const _legacyStudentStyleKey = 'student_code_style';

  static const _prefsApiBaseUrlKey = 'library_api_base_url_mirror';
  static const _prefsLibraryNameKey = 'library_name_mirror';
  static const _prefsLibraryCodeKey = 'library_code_mirror';
  static const _prefsLibraryStylesKey = 'library_student_styles_mirror';

  static const String resolverBaseUrl = String.fromEnvironment(
    'RESOLVER_BASE_URL',
    defaultValue: 'https://libcontrol.phenomit.com',
  );

  final _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  String? _apiBaseUrl;
  String? _libraryName;
  String? _libraryCode;
  LibraryStudentStyles? _libraryStudentStyles;
  bool _loaded = false;

  bool get isLoaded => _loaded;
  bool get isConfigured => _apiBaseUrl != null && _apiBaseUrl!.isNotEmpty;
  String? get libraryName => _libraryName;
  String? get libraryCode => _libraryCode;
  LibraryStudentStyles? get libraryStudentStyles => _libraryStudentStyles;
  StudentCodeStyle? get studentCodeStyle => _libraryStudentStyles?.singleStyle;

  String get apiBaseUrl {
    final url = _apiBaseUrl;
    if (url == null || url.isEmpty) {
      throw StateError('Library server is not configured.');
    }

    return url;
  }

  Future<void> load() async {
    final snapshot = await _readStoredLibrary();
    _apiBaseUrl = snapshot?.apiBaseUrl;
    _libraryName = snapshot?.libraryName;
    _libraryCode = snapshot?.libraryCode;
    _libraryStudentStyles = snapshot?.libraryStudentStyles;

    _loaded = true;
    notifyListeners();
  }

  Future<void> setLibrary({
    required String apiBaseUrl,
    String? libraryName,
    String? libraryCode,
    LibraryStudentStyles? libraryStudentStyles,
    StudentCodeStyle? studentCodeStyle,
  }) async {
    final normalized = normalizeBaseUrl(apiBaseUrl);
    final resolvedName = libraryName?.trim();
    final resolvedCode = libraryCode?.trim();

    final resolvedStyles = libraryStudentStyles ??
        (studentCodeStyle != null && studentCodeStyle.isConfigured
            ? LibraryStudentStyles(
                multiBranchPrefixes: false,
                branchStyles: [studentCodeStyle],
              )
            : null);

    await _persistLibrary(
      apiBaseUrl: normalized,
      libraryName: resolvedName?.isNotEmpty == true ? resolvedName : null,
      libraryCode: resolvedCode?.isNotEmpty == true ? resolvedCode : null,
      libraryStudentStyles: resolvedStyles,
    );

    _apiBaseUrl = normalized;
    _libraryName = resolvedName?.isNotEmpty == true ? resolvedName : null;
    _libraryCode = resolvedCode?.isNotEmpty == true ? resolvedCode : null;
    _libraryStudentStyles =
        resolvedStyles != null && resolvedStyles.hasConfiguredStyles
            ? resolvedStyles
            : null;

    notifyListeners();
  }

  Future<void> clear() async {
    await _storage.delete(key: _storageKey);
    await _storage.delete(key: _libraryNameKey);
    await _storage.delete(key: _libraryCodeKey);
    await _storage.delete(key: _libraryStylesKey);
    await _storage.delete(key: _legacyStudentStyleKey);
    await _clearMirror();

    _apiBaseUrl = null;
    _libraryName = null;
    _libraryCode = null;
    _libraryStudentStyles = null;
    notifyListeners();
  }

  Future<_StoredLibrary?> _readStoredLibrary() async {
    final secureApiBaseUrl = await _storage.read(key: _storageKey);
    final secureLibraryName = await _storage.read(key: _libraryNameKey);
    final secureLibraryCode = await _storage.read(key: _libraryCodeKey);
    final secureStylesRaw = await _storage.read(key: _libraryStylesKey);

    if (_hasApiBaseUrl(secureApiBaseUrl)) {
      return _StoredLibrary(
        apiBaseUrl: secureApiBaseUrl!,
        libraryName: secureLibraryName,
        libraryCode: secureLibraryCode,
        libraryStudentStyles: _decodeLibraryStyles(secureStylesRaw),
      );
    }

    final prefs = await SharedPreferences.getInstance();
    final mirroredApiBaseUrl = prefs.getString(_prefsApiBaseUrlKey);
    if (!_hasApiBaseUrl(mirroredApiBaseUrl)) {
      return null;
    }

    return _StoredLibrary(
      apiBaseUrl: mirroredApiBaseUrl!,
      libraryName: prefs.getString(_prefsLibraryNameKey),
      libraryCode: prefs.getString(_prefsLibraryCodeKey),
      libraryStudentStyles: _decodeLibraryStyles(
        prefs.getString(_prefsLibraryStylesKey),
      ),
    );
  }

  Future<void> _persistLibrary({
    required String apiBaseUrl,
    String? libraryName,
    String? libraryCode,
    LibraryStudentStyles? libraryStudentStyles,
  }) async {
    await _storage.write(key: _storageKey, value: apiBaseUrl);

    if (libraryName != null && libraryName.isNotEmpty) {
      await _storage.write(key: _libraryNameKey, value: libraryName);
    } else {
      await _storage.delete(key: _libraryNameKey);
    }

    if (libraryCode != null && libraryCode.isNotEmpty) {
      await _storage.write(key: _libraryCodeKey, value: libraryCode);
    } else {
      await _storage.delete(key: _libraryCodeKey);
    }

    if (libraryStudentStyles != null && libraryStudentStyles.hasConfiguredStyles) {
      final encoded = jsonEncode(libraryStudentStyles.toJson());
      await _storage.write(key: _libraryStylesKey, value: encoded);
    } else {
      await _storage.delete(key: _libraryStylesKey);
    }

    await _storage.delete(key: _legacyStudentStyleKey);
    await _mirrorLibrary(
      apiBaseUrl: apiBaseUrl,
      libraryName: libraryName,
      libraryCode: libraryCode,
      libraryStudentStyles: libraryStudentStyles,
    );
  }

  Future<void> _mirrorLibrary({
    required String apiBaseUrl,
    String? libraryName,
    String? libraryCode,
    LibraryStudentStyles? libraryStudentStyles,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsApiBaseUrlKey, apiBaseUrl);

    if (libraryName != null && libraryName.isNotEmpty) {
      await prefs.setString(_prefsLibraryNameKey, libraryName);
    } else {
      await prefs.remove(_prefsLibraryNameKey);
    }

    if (libraryCode != null && libraryCode.isNotEmpty) {
      await prefs.setString(_prefsLibraryCodeKey, libraryCode);
    } else {
      await prefs.remove(_prefsLibraryCodeKey);
    }

    if (libraryStudentStyles != null && libraryStudentStyles.hasConfiguredStyles) {
      await prefs.setString(
        _prefsLibraryStylesKey,
        jsonEncode(libraryStudentStyles.toJson()),
      );
    } else {
      await prefs.remove(_prefsLibraryStylesKey);
    }
  }

  Future<void> _clearMirror() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsApiBaseUrlKey);
    await prefs.remove(_prefsLibraryNameKey);
    await prefs.remove(_prefsLibraryCodeKey);
    await prefs.remove(_prefsLibraryStylesKey);
  }

  LibraryStudentStyles? _decodeLibraryStyles(String? raw) {
    if (raw == null || raw.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        final styles = LibraryStudentStyles.fromJson(decoded);
        if (styles.hasConfiguredStyles) {
          return styles;
        }
      }
    } catch (_) {
      return null;
    }

    return null;
  }

  bool _hasApiBaseUrl(String? value) {
    return value != null && value.isNotEmpty;
  }

  static String normalizeBaseUrl(String raw) {
    var value = raw.trim();
    if (value.isEmpty) {
      throw ArgumentError('Library URL cannot be empty.');
    }

    if (!value.startsWith('http://') && !value.startsWith('https://')) {
      value = 'https://$value';
    }

    final uri = Uri.parse(value);
    if (!uri.hasScheme || uri.host.isEmpty) {
      throw ArgumentError('Enter a valid library URL.');
    }

    return Uri(
      scheme: uri.scheme,
      host: uri.host,
      port: uri.hasPort ? uri.port : null,
    ).toString().replaceAll(RegExp(r'/+$'), '');
  }

  static String? parseAttendanceQrUrl(String raw) {
    final value = raw.trim();
    if (value.isEmpty) {
      return null;
    }

    final uri = Uri.tryParse(value);
    if (uri == null || !uri.hasScheme || uri.host.isEmpty) {
      return null;
    }

    if (!uri.path.contains('/attendance/check-in/')) {
      return null;
    }

    return normalizeBaseUrl('${uri.scheme}://${uri.host}${uri.hasPort ? ':${uri.port}' : ''}');
  }
}

class _StoredLibrary {
  const _StoredLibrary({
    required this.apiBaseUrl,
    this.libraryName,
    this.libraryCode,
    this.libraryStudentStyles,
  });

  final String apiBaseUrl;
  final String? libraryName;
  final String? libraryCode;
  final LibraryStudentStyles? libraryStudentStyles;
}
