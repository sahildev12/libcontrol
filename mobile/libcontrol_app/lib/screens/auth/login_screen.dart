import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/models/student_lookup.dart';
import 'package:libcontrol_app/widgets/primary_button.dart';
import 'package:local_auth/local_auth.dart';

class PinLoginScreen extends StatefulWidget {
  const PinLoginScreen({
    super.key,
    required this.lookup,
    this.onLoginSuccess,
  });

  final StudentLookup lookup;
  final VoidCallback? onLoginSuccess;

  @override
  State<PinLoginScreen> createState() => _PinLoginScreenState();
}

class _PinLoginScreenState extends State<PinLoginScreen> {
  final _pinController = TextEditingController();
  final _localAuth = LocalAuthentication();
  bool _obscurePin = true;
  bool _rememberMe = true;
  bool _loading = false;
  bool _biometricLoading = false;

  @override
  void dispose() {
    _pinController.dispose();
    super.dispose();
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  Future<void> _signIn() async {
    final pin = _pinController.text.trim();

    if (!RegExp(r'^\d{4,6}$').hasMatch(pin)) {
      _showMessage('Enter a valid 4–6 digit PIN.');
      return;
    }

    setState(() => _loading = true);

    try {
      await AuthService.instance.login(
        studentCode: widget.lookup.studentCode,
        pin: pin,
      );

      if (_rememberMe) {
        await AuthService.instance.setBiometricEnabled(true);
      }

      widget.onLoginSuccess?.call();
    } on ApiException catch (e) {
      _showMessage(e.message);
    } catch (_) {
      _showMessage('Could not sign in. Check your connection and try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _biometricLogin() async {
    if (_biometricLoading) return;

    setState(() => _biometricLoading = true);

    try {
      final isSupported = await _localAuth.isDeviceSupported();
      if (!isSupported) {
        _showMessage('Biometric login is not supported on this device.');
        return;
      }

      if (!await AuthService.instance.isBiometricEnabled()) {
        _showMessage('Sign in with your PIN first to enable biometric unlock.');
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
      if (unlocked) {
        widget.onLoginSuccess?.call();
      } else {
        _showMessage('Session expired. Sign in with your student code and PIN.');
      }
    } on PlatformException catch (e) {
      _showMessage(e.message ?? 'Biometric authentication failed.');
    } on LocalAuthException catch (e) {
      if (e.code != LocalAuthExceptionCode.userCanceled &&
          e.code != LocalAuthExceptionCode.systemCanceled) {
        _showMessage('Biometric authentication failed. Please try again.');
      }
    } finally {
      if (mounted) setState(() => _biometricLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sign In'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.lookup.name,
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 4),
              Text(
                '${widget.lookup.studentCode}${widget.lookup.homeBranch.isNotEmpty ? ' · ${widget.lookup.homeBranch}' : ''}',
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 28),
              TextField(
                controller: _pinController,
                obscureText: _obscurePin,
                keyboardType: TextInputType.number,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(6),
                ],
                decoration: InputDecoration(
                  labelText: 'PIN',
                  prefixIcon: const Icon(Icons.lock_outline_rounded),
                  suffixIcon: IconButton(
                    onPressed: () => setState(() => _obscurePin = !_obscurePin),
                    icon: Icon(_obscurePin ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                  ),
                ),
                onSubmitted: (_) => _loading ? null : _signIn(),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Checkbox(
                    value: _rememberMe,
                    onChanged: (value) => setState(() => _rememberMe = value ?? false),
                  ),
                  const Expanded(child: Text('Enable biometric unlock after sign in')),
                ],
              ),
              const SizedBox(height: 16),
              PrimaryButton(
                label: _loading ? 'Signing In...' : 'Sign In',
                onPressed: _loading ? null : _signIn,
              ),
              const SizedBox(height: 24),
              const Row(
                children: [
                  Expanded(child: Divider()),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 12),
                    child: Text('or', style: TextStyle(color: AppColors.textSecondary)),
                  ),
                  Expanded(child: Divider()),
                ],
              ),
              const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: _biometricLoading ? null : _biometricLogin,
                icon: _biometricLoading
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.fingerprint_rounded),
                label: Text(_biometricLoading ? 'Authenticating...' : 'Use Biometric Login'),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  side: const BorderSide(color: AppColors.border),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
