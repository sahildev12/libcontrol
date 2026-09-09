import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:libcontrol_app/core/config/server_config.dart';

class LibraryConnection {
  const LibraryConnection({
    required this.code,
    required this.apiBaseUrl,
    this.name,
  });

  final String code;
  final String apiBaseUrl;
  final String? name;

  factory LibraryConnection.fromJson(Map<String, dynamic> json) {
    return LibraryConnection(
      code: json['code'] as String? ?? '',
      apiBaseUrl: ServerConfig.normalizeBaseUrl(json['api_base_url'] as String? ?? ''),
      name: json['name'] as String?,
    );
  }
}

class LibraryResolverService {
  LibraryResolverService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Future<LibraryConnection> resolveCode(String code) async {
    final normalizedCode = code.trim().toUpperCase();
    if (normalizedCode.isEmpty) {
      throw LibraryResolverException('Enter your library code.');
    }

    final resolverBase = ServerConfig.resolverBaseUrl.replaceAll(RegExp(r'/+$'), '');
    final url = '$resolverBase/api/v1/mobile/libraries/$normalizedCode';

    final response = await _client.get(
      Uri.parse(url),
      headers: const {'Accept': 'application/json'},
    );

    Map<String, dynamic> data = {};
    if (response.body.isNotEmpty) {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      }
    }

    if (response.statusCode == 404) {
      throw LibraryResolverException(
        data['message'] as String? ?? 'Library not found. Check the code with your library staff.',
      );
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw LibraryResolverException(
        data['message'] as String? ?? 'Could not look up your library. Try again later.',
      );
    }

    return LibraryConnection.fromJson(data);
  }

  Future<void> validateLibraryServer(String apiBaseUrl) async {
    final base = ServerConfig.normalizeBaseUrl(apiBaseUrl);
    final response = await _client.get(
      Uri.parse('$base/up'),
      headers: const {'Accept': 'application/json'},
    );

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw LibraryResolverException('Could not reach the library server. Check the URL or QR code.');
    }
  }
}

class LibraryResolverException implements Exception {
  LibraryResolverException(this.message);

  final String message;

  @override
  String toString() => message;
}
