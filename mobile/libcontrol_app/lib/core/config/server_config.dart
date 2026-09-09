import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

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
  bool _loaded = false;

  bool get isLoaded => _loaded;
  bool get isConfigured => _apiBaseUrl != null && _apiBaseUrl!.isNotEmpty;
  String? get libraryName => _libraryName;

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

    _loaded = true;
    notifyListeners();
  }

  Future<void> setLibrary({
    required String apiBaseUrl,
    String? libraryName,
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

    notifyListeners();
  }

  Future<void> clear() async {
    await _storage.delete(key: _storageKey);
    await _storage.delete(key: 'library_name');
    _apiBaseUrl = null;
    _libraryName = null;
    notifyListeners();
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
