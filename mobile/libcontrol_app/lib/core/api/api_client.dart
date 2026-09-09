import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

class ApiClient {
  ApiClient({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;
  String? _token;

  void setToken(String? token) {
    _token = token;
  }

  String? get token => _token;

  Future<Map<String, dynamic>> postJson(
    String url, {
    Map<String, dynamic>? body,
    bool authenticated = false,
  }) async {
    try {
      final response = await _client.post(
        Uri.parse(url),
        headers: _headers(authenticated: authenticated),
        body: jsonEncode(body ?? {}),
      );

      return _decodeResponse(response);
    } catch (error) {
      throw _connectionError(error);
    }
  }

  Future<Map<String, dynamic>> getJson(
    String url, {
    bool authenticated = false,
  }) async {
    try {
      final response = await _client.get(
        Uri.parse(url),
        headers: _headers(authenticated: authenticated),
      );

      return _decodeResponse(response);
    } catch (error) {
      throw _connectionError(error);
    }
  }

  ApiException _connectionError(Object error) {
    if (error is ApiException) {
      return error;
    }

    if (error is SocketException || error is http.ClientException) {
      return ApiException(
        'Could not reach your library server. Connect to your library first, then check your internet.',
      );
    }

    return ApiException('Could not reach your library server. Please try again.');
  }

  Map<String, String> _headers({required bool authenticated}) {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (authenticated && _token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }

    return headers;
  }

  Map<String, dynamic> _decodeResponse(http.Response response) {
    Map<String, dynamic> data = {};

    if (response.body.isNotEmpty) {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    }

    final message = _extractErrorMessage(data);

    throw ApiException(message, statusCode: response.statusCode);
  }

  String _extractErrorMessage(Map<String, dynamic> data) {
    final errors = data['errors'];
    if (errors is Map) {
      for (final entry in errors.entries) {
        final value = entry.value;
        if (value is List && value.isNotEmpty) {
          return value.first.toString();
        }
      }
    }

    if (data['message'] is String) {
      return data['message'] as String;
    }

    return 'Something went wrong. Please try again.';
  }
}

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}
