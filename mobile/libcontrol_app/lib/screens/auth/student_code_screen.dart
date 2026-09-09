import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/core/config/server_config.dart';
import 'package:libcontrol_app/core/config/library_student_styles.dart';
import 'package:libcontrol_app/core/config/student_code_style.dart';
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

  LibraryStudentStyles? get _styles => ServerConfig.instance.libraryStudentStyles;

  StudentCodeStyle? get _singleStyle => _styles?.singleStyle;

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

  String _resolvedStudentCode() {
    final styles = _styles;
    final input = _studentCodeController.text.trim();

    if (styles != null && styles.hasConfiguredStyles) {
      return styles.format(input);
    }

    return input.toUpperCase();
  }

  Future<void> _continue() async {
    if (!ServerConfig.instance.isConfigured) {
      _showMessage('Connect to your library first.');
      return;
    }

    final studentCode = _resolvedStudentCode();

    if (studentCode.isEmpty) {
      _showMessage('Enter your student number.');
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
    } on StateError {
      _showMessage('Connect to your library first.');
    } catch (_) {
      _showMessage('Could not verify your student code. Check your connection and try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final styles = _styles;
    final style = _singleStyle;
    final usesSinglePrefix = style != null && style.isConfigured;
    final usesMultiPrefix = styles != null &&
        styles.multiBranchPrefixes &&
        styles.hasConfiguredStyles;
    final sampleCodes = styles?.sampleCodes ?? const <String>[];

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
                    Text(
                      usesMultiPrefix
                          ? 'Enter your full student ID (e.g. ${sampleCodes.take(2).join(' or ')})'
                          : usesSinglePrefix
                              ? 'Enter your student number — we add ${style!.displayPrefix} for you'
                              : 'Enter the student code provided by your library',
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              if (usesMultiPrefix)
                TextField(
                  controller: _studentCodeController,
                  textCapitalization: TextCapitalization.characters,
                  decoration: InputDecoration(
                    labelText: 'Student ID',
                    hintText: sampleCodes.isNotEmpty ? sampleCodes.first : 'ABC-001',
                    prefixIcon: const Icon(Icons.badge_outlined),
                  ),
                  onSubmitted: (_) => _loading ? null : _continue(),
                )
              else if (usesSinglePrefix)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 56,
                      alignment: Alignment.center,
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.08),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
                      ),
                      child: Text(
                        style!.displayPrefix,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: TextField(
                        controller: _studentCodeController,
                        keyboardType: TextInputType.number,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(style!.padding.clamp(1, 6)),
                        ],
                        decoration: InputDecoration(
                          labelText: 'Student number',
                          hintText: '1'.padLeft(style!.padding, '0'),
                        ),
                        onSubmitted: (_) => _loading ? null : _continue(),
                      ),
                    ),
                  ],
                )
              else
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
