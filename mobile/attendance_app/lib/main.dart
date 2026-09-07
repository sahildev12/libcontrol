import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'core/theme/app_theme.dart';
import 'features/auth/login_screen.dart';
import 'features/home/home_screen.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const AttendanceApp());
}

class AttendanceApp extends StatefulWidget {
  const AttendanceApp({super.key});

  @override
  State<AttendanceApp> createState() => _AttendanceAppState();
}

class _AttendanceAppState extends State<AttendanceApp> {
  final _storage = StorageService(const FlutterSecureStorage());
  late final ApiService _api = ApiService(_storage);
  bool _checkingSession = true;
  bool _loggedIn = false;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final token = await _storage.readToken();
    if (!mounted) return;
    setState(() {
      _loggedIn = token != null && token.isNotEmpty;
      _checkingSession = false;
    });
  }

  Future<void> _handleLoggedIn() async {
    setState(() => _loggedIn = true);
  }

  Future<void> _handleLogout() async {
    try {
      await _api.logout();
    } catch (_) {}
    await _storage.clearSession();
    if (!mounted) return;
    setState(() => _loggedIn = false);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'LibControl Attendance',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      home: _checkingSession
          ? const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            )
          : _loggedIn
              ? HomeScreen(api: _api, storage: _storage, onLogout: _handleLogout)
              : LoginScreen(api: _api, storage: _storage, onLoggedIn: _handleLoggedIn),
    );
  }
}
