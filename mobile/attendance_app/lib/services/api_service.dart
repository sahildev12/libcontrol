import 'package:dio/dio.dart';

import '../core/models/attendance_models.dart';
import '../core/models/user_profile.dart';
import '../core/utils/api_exception.dart';
import 'storage_service.dart';

class ApiService {
  ApiService(this._storage);

  final StorageService _storage;

  Future<Dio> _client() async {
    final baseUrl = await _storage.readBaseUrl();
    if (baseUrl == null || baseUrl.isEmpty) {
      throw ApiException('Set your library server URL first.');
    }

    final token = await _storage.readToken();
    return Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 20),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      },
    ));
  }

  Future<T> _guard<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }

  Future<UserProfile> login({
    required String email,
    required String password,
  }) async {
    return _guard(() async {
      final dio = await _client();
      final response = await dio.post('/api/v1/auth/login', data: {
        'email': email,
        'password': password,
        'device_name': 'android-attendance',
      });

      final token = response.data['token'] as String;
      await _storage.writeToken(token);

      return UserProfile.fromJson(Map<String, dynamic>.from(response.data['user'] as Map));
    });
  }

  Future<void> logout() async {
    await _guard(() async {
      final dio = await _client();
      await dio.post('/api/v1/auth/logout');
    });
  }

  Future<UserProfile> me() async {
    return _guard(() async {
      final dio = await _client();
      final response = await dio.get('/api/v1/auth/me');
      return UserProfile.fromJson(Map<String, dynamic>.from(response.data['user'] as Map));
    });
  }

  Future<AttendanceContext> attendanceContext({required int branchId}) async {
    return _guard(() async {
      final dio = await _client();
      final response = await dio.get('/api/v1/attendance/context', queryParameters: {
        'branch_id': branchId,
      });

      return AttendanceContext.fromJson(Map<String, dynamic>.from(response.data as Map));
    });
  }

  Future<void> checkInStudent({
    required int studentId,
    required int branchId,
    required double latitude,
    required double longitude,
  }) async {
    await _guard(() async {
      final dio = await _client();
      await dio.post('/api/v1/attendance/check-in', data: {
        'student_id': studentId,
        'branch_id': branchId,
        'latitude': latitude,
        'longitude': longitude,
        'device_name': 'android-attendance',
      });
    });
  }

  Future<void> bulkCheckIn({
    required List<int> studentIds,
    required int branchId,
    required double latitude,
    required double longitude,
  }) async {
    await _guard(() async {
      final dio = await _client();
      await dio.post('/api/v1/attendance/check-in/bulk', data: {
        'student_ids': studentIds,
        'branch_id': branchId,
        'latitude': latitude,
        'longitude': longitude,
      });
    });
  }
}
