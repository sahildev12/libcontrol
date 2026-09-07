import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class StorageService {
  StorageService(this._storage);

  final FlutterSecureStorage _storage;

  static const _baseUrlKey = 'api_base_url';
  static const _tokenKey = 'auth_token';

  Future<String?> readBaseUrl() => _storage.read(key: _baseUrlKey);

  Future<void> writeBaseUrl(String value) =>
      _storage.write(key: _baseUrlKey, value: value.trim().replaceAll(RegExp(r'/+$'), ''));

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> writeToken(String value) => _storage.write(key: _tokenKey, value: value);

  Future<void> clearSession() async {
    await _storage.delete(key: _tokenKey);
  }
}
