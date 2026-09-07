import 'package:dio/dio.dart';

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  @override
  String toString() => message;

  static ApiException fromDio(DioException error) {
    final data = error.response?.data;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          return ApiException(first.first.toString());
        }
      }
      final message = data['message'];
      if (message is String && message.isNotEmpty) {
        return ApiException(message);
      }
    }

    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.receiveTimeout:
      case DioExceptionType.sendTimeout:
        return ApiException('Connection timed out. Check your network.');
      case DioExceptionType.connectionError:
        return ApiException('Cannot reach the server. Check the library URL and Wi‑Fi.');
      default:
        return ApiException('Request failed (${error.response?.statusCode ?? 'network'}).');
    }
  }
}
