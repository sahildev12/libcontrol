import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:libcontrol_app/core/config/library_student_styles.dart';
import 'package:libcontrol_app/core/config/student_code_style.dart';

class ServerConfig extends ChangeNotifier {
  ServerConfig._();

  static final ServerConfig instance = ServerConfig._();

  static const _storageKey = 'library_api_base_url';

  static const String resolverBaseUrl = String.fromEnvironment(
    'RESOLVER_BASE_URL',
    defaultValue: 'https://libcontrol.phenomit.com',
  );

  final _storage = const FlutterSecureStorage();

  String? _apiBaseUrl;
  String? _libraryName;
  LibraryStudentStyles? _libraryStudentStyles;
  bool _loaded = false;

  bool get isLoaded => _loaded;
  bool get isConfigured => _apiBaseUrl != null && _apiBaseUrl!.isNotEmpty;
  String? get libraryName => _libraryName;
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
    _apiBaseUrl = await _storage.read(key: _storageKey);
    _libraryName = await _storage.read(key: 'library_name');
    _libraryStudentStyles = await _readLibraryStudentStyles();

    _loaded = true;
    notifyListeners();
  }

  Future<void> setLibrary({
    required String apiBaseUrl,
    String? libraryName,
    LibraryStudentStyles? libraryStudentStyles,
    StudentCodeStyle? studentCodeStyle,
  }) async {
    final normalized = normalizeBaseUrl(apiBaseUrl);
    await _storage.write(key: _storageKey, value: normalized);
    _apiBaseUrl = normalized;

    if (libraryName != null && libraryName.trim().isNotEmpty) {
      await _storage.write(key: 'library_name', value: libraryName.trim());
      _libraryName = libraryName.trim();
    } else {
      await _storage.delete(key: 'library_name');
      _libraryName = null;
    }

    final resolvedStyles = libraryStudentStyles ??
        (studentCodeStyle != null && studentCodeStyle.isConfigured
            ? LibraryStudentStyles(
                multiBranchPrefixes: false,
                branchStyles: [studentCodeStyle],
              )
            : null);

    if (resolvedStyles != null && resolvedStyles.hasConfiguredStyles) {
      await _storage.write(
        key: 'library_student_styles',
        value: jsonEncode(resolvedStyles.toJson()),
      );
      _libraryStudentStyles = resolvedStyles;
    } else {
      await _storage.delete(key: 'library_student_styles');
      _libraryStudentStyles = null;
    }

    await _storage.delete(key: 'student_code_style');

    notifyListeners();
  }

  Future<void> clear() async {
    await _storage.delete(key: _storageKey);
    await _storage.delete(key: 'library_name');
    await _storage.delete(key: 'library_student_styles');
    await _storage.delete(key: 'student_code_style');
    _apiBaseUrl = null;
    _libraryName = null;
    _libraryStudentStyles = null;
    notifyListeners();
  }

  Future<LibraryStudentStyles?> _readLibraryStudentStyles() async {
    final raw = await _storage.read(key: 'library_student_styles');
    if (raw != null && raw.isNotEmpty) {
      try {
        final decoded = jsonDecode(raw);
        if (decoded is Map<String, dynamic>) {
          final styles = LibraryStudentStyles.fromJson(decoded);
          if (styles.hasConfiguredStyles) {
            return styles;
          }
        }
      } catch (_) {
        // Fall through to legacy storage.
      }
    }

    final legacyRaw = await _storage.read(key: 'student_code_style');
    if (legacyRaw == null || legacyRaw.isEmpty) {
      return null;
    }

    try {
      final decoded = jsonDecode(legacyRaw);
      if (decoded is Map<String, dynamic>) {
        final style = StudentCodeStyle.fromJson(decoded);
        if (style.isConfigured) {
          return LibraryStudentStyles(
            multiBranchPrefixes: false,
            branchStyles: [style],
          );
        }
      }
    } catch (_) {
      return null;
    }

    return null;
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
