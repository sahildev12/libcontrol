import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/core/config/server_config.dart';
import 'package:libcontrol_app/screens/auth/library_connect_screen.dart';
import 'package:libcontrol_app/screens/auth/student_code_screen.dart';
import 'package:libcontrol_app/screens/main_shell.dart';

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  bool _initializing = true;

  @override
  void initState() {
    super.initState();
    AuthService.instance.addListener(_onStateChanged);
    ServerConfig.instance.addListener(_onStateChanged);
    _initialize();
  }

  @override
  void dispose() {
    AuthService.instance.removeListener(_onStateChanged);
    ServerConfig.instance.removeListener(_onStateChanged);
    super.dispose();
  }

  Future<void> _initialize() async {
    await ServerConfig.instance.load();

    if (ServerConfig.instance.isConfigured && !AuthService.instance.isBootstrapped) {
      await AuthService.instance.bootstrap();
    }

    if (mounted) {
      setState(() => _initializing = false);
    }
  }

  void _onStateChanged() {
    if (mounted) setState(() {});
  }

  Future<void> _onLibraryConnected() async {
    setState(() => _initializing = true);
    await AuthService.instance.bootstrap();
    if (mounted) {
      setState(() => _initializing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_initializing || !ServerConfig.instance.isLoaded) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: AppColors.primary),
        ),
      );
    }

    if (!ServerConfig.instance.isConfigured) {
      return LibraryConnectScreen(onConnected: _onLibraryConnected);
    }

    final auth = AuthService.instance;

    if (!auth.isBootstrapped) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: AppColors.primary),
        ),
      );
    }

    if (auth.isAuthenticated) {
      return const MainShell();
    }

    return StudentCodeScreen(
      onAuthSuccess: () {},
    );
  }
}
