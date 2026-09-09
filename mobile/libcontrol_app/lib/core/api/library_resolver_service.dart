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

  /// Accepts a numeric library code; non-digits are stripped.
  static String normalizeLibraryCode(String input) {
    return input.replaceAll(RegExp(r'\D'), '');
  }

  Future<LibraryConnection> resolveCode(String code) async {
    final normalizedCode = normalizeLibraryCode(code);
    if (normalizedCode.isEmpty) {
      throw LibraryResolverException('Enter your library code.');
    }

    final resolverBase = ServerConfig.resolverBaseUrl.replaceAll(RegExp(r'/+$'), '');
    final url = Uri.parse(resolverBase).replace(
      path: '/api/v1/mobile/libraries/${Uri.encodeComponent(normalizedCode)}',
    );

    final response = await _client.get(
      url,
      headers: const {'Accept': 'application/json'},
    );

    final data = _decodeJsonBody(response.body);

    if (response.statusCode == 404) {
      throw LibraryResolverException(
        data['message'] as String? ??
            'Library not found. Check the code with your library staff.',
      );
    }

    if (response.statusCode >= 500) {
      throw LibraryResolverException(
        'Library lookup is temporarily unavailable. Try again later or scan the attendance QR code.',
      );
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      final message = data['message'] as String?;
      if (message != null && message.isNotEmpty && message.toLowerCase() != 'server error') {
        throw LibraryResolverException(message);
      }

      throw LibraryResolverException(
        'Could not look up your library. Try again later or scan the attendance QR code.',
      );
    }

    return LibraryConnection.fromJson(data);
  }

  Map<String, dynamic> _decodeJsonBody(String body) {
    if (body.isEmpty) {
      return {};
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      throw LibraryResolverException(
        'Could not reach the library lookup service. Check your internet connection.',
      );
    }

    return {};
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
