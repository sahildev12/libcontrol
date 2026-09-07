import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/screens/auth/student_code_screen.dart';
import 'package:libcontrol_app/screens/main_shell.dart';

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  @override
  void initState() {
    super.initState();
    AuthService.instance.addListener(_onAuthChanged);
    if (!AuthService.instance.isBootstrapped) {
      AuthService.instance.bootstrap();
    }
  }

  @override
  void dispose() {
    AuthService.instance.removeListener(_onAuthChanged);
    super.dispose();
  }

  void _onAuthChanged() {
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
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
