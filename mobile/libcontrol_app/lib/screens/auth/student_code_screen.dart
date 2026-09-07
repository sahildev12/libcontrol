import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/screens/auth/create_pin_screen.dart';
import 'package:libcontrol_app/screens/auth/login_screen.dart';
import 'package:libcontrol_app/widgets/libcontrol_logo.dart';
import 'package:libcontrol_app/widgets/primary_button.dart';

class StudentCodeScreen extends StatefulWidget {
  const StudentCodeScreen({super.key, this.onAuthSuccess});

  final VoidCallback? onAuthSuccess;

  @override
  State<StudentCodeScreen> createState() => _StudentCodeScreenState();
}

class _StudentCodeScreenState extends State<StudentCodeScreen> {
  final _studentCodeController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _studentCodeController.dispose();
    super.dispose();
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  Future<void> _continue() async {
    final studentCode = _studentCodeController.text.trim();

    if (studentCode.isEmpty) {
      _showMessage('Enter your student code.');
      return;
    }

    setState(() => _loading = true);

    try {
      final lookup = await AuthService.instance.checkCode(studentCode);
      if (!mounted) return;

      final nextScreen = lookup.needsPinSetup
          ? CreatePinScreen(lookup: lookup, onAuthSuccess: widget.onAuthSuccess)
          : PinLoginScreen(lookup: lookup, onLoginSuccess: widget.onAuthSuccess);

      await Navigator.of(context).push(
        MaterialPageRoute(builder: (_) => nextScreen),
      );
    } on ApiException catch (e) {
      _showMessage(e.message);
    } catch (_) {
      _showMessage('Could not verify your student code. Check your connection and try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: Column(
            children: [
              const SizedBox(height: 24),
              const LibControlLogo(size: 80),
              const SizedBox(height: 16),
              Text('LibControl', style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 6),
              const Text(
                'YOUR STUDENT LIBRARY, SIMPLIFIED.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 12,
                  letterSpacing: 1.1,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 40),
              Align(
                alignment: Alignment.centerLeft,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Welcome', style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 6),
                    const Text(
                      'Enter the student code provided by your library',
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              TextField(
                controller: _studentCodeController,
                textCapitalization: TextCapitalization.characters,
                decoration: const InputDecoration(
                  labelText: 'Student Code',
                  prefixIcon: Icon(Icons.badge_outlined),
                ),
                onSubmitted: (_) => _loading ? null : _continue(),
              ),
              const SizedBox(height: 24),
              PrimaryButton(
                label: _loading ? 'Checking...' : 'Continue',
                onPressed: _loading ? null : _continue,
              ),
              const SizedBox(height: 32),
              const Text(
                "Don't have a student code? Contact your library.",
                style: TextStyle(color: AppColors.textSecondary),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
