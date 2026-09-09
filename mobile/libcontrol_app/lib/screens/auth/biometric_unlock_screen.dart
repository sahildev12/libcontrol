import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/models/student.dart';
import 'package:libcontrol_app/widgets/libcontrol_logo.dart';
import 'package:libcontrol_app/widgets/primary_button.dart';
import 'package:local_auth/local_auth.dart';

class BiometricUnlockScreen extends StatefulWidget {
  const BiometricUnlockScreen({
    super.key,
    required this.onUnlocked,
    required this.onUseStudentCode,
  });

  final VoidCallback onUnlocked;
  final VoidCallback onUseStudentCode;

  @override
  State<BiometricUnlockScreen> createState() => _BiometricUnlockScreenState();
}

class _BiometricUnlockScreenState extends State<BiometricUnlockScreen> {
  final _localAuth = LocalAuthentication();
  Student? _student;
  bool _loading = false;
  bool _autoPrompted = false;

  @override
  void initState() {
    super.initState();
    _loadStudent();
    WidgetsBinding.instance.addPostFrameCallback((_) => _unlockWithBiometric());
  }

  Future<void> _loadStudent() async {
    final student = await AuthService.instance.peekStoredStudent();
    if (mounted) {
      setState(() => _student = student);
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  Future<void> _unlockWithBiometric() async {
    if (_loading) return;

    setState(() => _loading = true);

    try {
      final isSupported = await _localAuth.isDeviceSupported();
      if (!isSupported) {
        _showMessage('Biometric login is not supported on this device.');
        return;
      }

      final biometrics = await _localAuth.getAvailableBiometrics();
      if (biometrics.isEmpty) {
        _showMessage('No biometrics enrolled. Set up fingerprint or face unlock first.');
        return;
      }

      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Confirm your identity to open LibControl',
        biometricOnly: true,
      );

      if (!authenticated) {
        return;
      }

      final unlocked = await AuthService.instance.unlockWithStoredSession();
      if (!mounted) return;

      if (unlocked) {
        widget.onUnlocked();
      } else {
        _showMessage('Quick unlock is unavailable. Sign in with your PIN once to restore it.');
      }
    } on PlatformException catch (e) {
      _showMessage(e.message ?? 'Biometric authentication failed.');
    } on LocalAuthException catch (e) {
      if (!_autoPrompted &&
          (e.code == LocalAuthExceptionCode.userCanceled ||
              e.code == LocalAuthExceptionCode.systemCanceled)) {
        return;
      }

      if (e.code != LocalAuthExceptionCode.userCanceled &&
          e.code != LocalAuthExceptionCode.systemCanceled) {
        _showMessage('Biometric authentication failed. Please try again.');
      }
    } finally {
      _autoPrompted = true;
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final student = _student;

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: Column(
            children: [
              const Spacer(),
              const LibControlLogo(size: 80),
              const SizedBox(height: 20),
              Text('Welcome back', style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 8),
              if (student != null) ...[
                Text(
                  student.name,
                  style: Theme.of(context).textTheme.titleLarge,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 4),
                Text(
                  student.id,
                  style: const TextStyle(color: AppColors.textSecondary),
                  textAlign: TextAlign.center,
                ),
              ],
              const SizedBox(height: 12),
              const Text(
                'Use your fingerprint or face to sign in.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondary),
              ),
              const Spacer(),
              PrimaryButton(
                label: _loading ? 'Unlocking...' : 'Unlock with Biometric',
                isLoading: _loading,
                onPressed: _loading ? null : _unlockWithBiometric,
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: _loading ? null : widget.onUseStudentCode,
                child: const Text('Sign in with PIN instead'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
