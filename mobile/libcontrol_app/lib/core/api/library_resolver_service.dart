import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:libcontrol_app/core/config/server_config.dart';
import 'package:libcontrol_app/core/config/library_student_styles.dart';
import 'package:libcontrol_app/core/config/student_code_style.dart';

class LibraryConnection {
  const LibraryConnection({
    required this.code,
    required this.apiBaseUrl,
    this.name,
    this.studentCodeStyle,
    this.sampleStudentCode,
  });

  final String code;
  final String apiBaseUrl;
  final String? name;
  final StudentCodeStyle? studentCodeStyle;
  final String? sampleStudentCode;

  factory LibraryConnection.fromJson(Map<String, dynamic> json) {
    final style = StudentCodeStyle.fromJson(json);

    return LibraryConnection(
      code: json['code'] as String? ?? '',
      apiBaseUrl: ServerConfig.normalizeBaseUrl(json['api_base_url'] as String? ?? ''),
      name: json['name'] as String?,
      studentCodeStyle: style.isConfigured ? style : null,
      sampleStudentCode: json['sample_student_code'] as String?,
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

    try {
      final response = await _client.get(
        url,
        headers: const {'Accept': 'application/json'},
      );

      final data = _decodeJsonBody(response.body);

      if (response.statusCode == 404) {
        throw LibraryResolverException(
          data['message'] as String? ??
              'Library not found on libcontrol.phenomit.com. Ask staff to sync the library code.',
        );
      }

      if (response.statusCode >= 500) {
        throw LibraryResolverException(
          'libcontrol.phenomit.com is temporarily unavailable. Try again later.',
        );
      }

      if (response.statusCode < 200 || response.statusCode >= 300) {
        final message = data['message'] as String?;
        if (message != null && message.isNotEmpty && message.toLowerCase() != 'server error') {
          throw LibraryResolverException(message);
        }

        throw LibraryResolverException('Could not look up your library on libcontrol.phenomit.com.');
      }

      return LibraryConnection.fromJson(data);
    } on LibraryResolverException {
      rethrow;
    } on SocketException {
      throw LibraryResolverException(
        'Could not reach libcontrol.phenomit.com. Check your internet connection.',
      );
    } on http.ClientException {
      throw LibraryResolverException(
        'Could not reach libcontrol.phenomit.com. Check your internet connection.',
      );
    }
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
        'Invalid response from libcontrol.phenomit.com.',
      );
    }

    return {};
  }

  Future<LibraryStudentStyles> fetchBranchStyles(String apiBaseUrl) async {
    final base = ServerConfig.normalizeBaseUrl(apiBaseUrl);
    final url = Uri.parse('$base/api/v1/mobile/library/styles');

    try {
      final response = await _client.get(
        url,
        headers: const {'Accept': 'application/json'},
      );

      final data = _decodeJsonBody(response.body);

      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw LibraryResolverException(
          data['message'] as String? ??
              'Could not load student ID styles from your library.',
        );
      }

      return LibraryStudentStyles.fromJson(data);
    } on LibraryResolverException {
      rethrow;
    } on SocketException {
      throw LibraryResolverException(
        'Could not reach your library server. Check your internet connection.',
      );
    } on http.ClientException {
      throw LibraryResolverException(
        'Could not reach your library server. Check your internet connection.',
      );
    }
  }

  Future<void> validateLibraryServer(String apiBaseUrl) async {
    final base = ServerConfig.normalizeBaseUrl(apiBaseUrl);
    final host = Uri.tryParse(base)?.host.toLowerCase() ?? '';

    if (host == '127.0.0.1' || host == 'localhost' || host == '::1') {
      throw LibraryResolverException(
        'This library is registered as $base on Phenomit. Phones cannot reach localhost — set LIBCONTROL_PUBLIC_URL to your PC IP in .env and run php artisan app:sync-runtime-metrics.',
      );
    }

    try {
      final response = await _client.get(
        Uri.parse('$base/up'),
        headers: const {'Accept': 'application/json'},
      );

      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw LibraryResolverException(
          'Your library server at $base is not responding. Check that Laravel is running and reachable from your phone.',
        );
      }
    } on SocketException {
      throw LibraryResolverException(
        'Could not reach your library server at $base. Use the same Wi‑Fi as your PC and ensure php artisan serve --host=0.0.0.0 is running.',
      );
    } on http.ClientException {
      throw LibraryResolverException(
        'Could not reach your library server at $base. Check your network connection.',
      );
    }
  }
}

class LibraryResolverException implements Exception {
  LibraryResolverException(this.message);

  final String message;

  @override
  String toString() => message;
}
