import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/api/library_resolver_service.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/core/config/server_config.dart';
import 'package:libcontrol_app/core/config/student_code_style.dart';
import 'package:libcontrol_app/screens/auth/create_pin_screen.dart';
import 'package:libcontrol_app/screens/auth/login_screen.dart';
import 'package:libcontrol_app/widgets/auth/prefixed_student_code_field.dart';
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
  final _resolver = LibraryResolverService();
  bool _loading = false;
  bool _refreshingStyle = false;

  StudentCodeStyle? get _loginStyle => ServerConfig.instance.studentCodeStyle;

  @override
  void initState() {
    super.initState();
    ServerConfig.instance.addListener(_onConfigChanged);
    _refreshLoginStyleIfNeeded();
  }

  @override
  void dispose() {
    ServerConfig.instance.removeListener(_onConfigChanged);
    _studentCodeController.dispose();
    super.dispose();
  }

  void _onConfigChanged() {
    if (mounted) setState(() {});
  }

  Future<void> _refreshLoginStyleIfNeeded() async {
    final config = ServerConfig.instance;
    if (config.studentCodeStyle != null) {
      return;
    }

    if (!config.isConfigured) {
      return;
    }

    setState(() => _refreshingStyle = true);

    try {
      final libraryCode = config.libraryCode;
      if (libraryCode != null && libraryCode.isNotEmpty) {
        try {
          final connection = await _resolver.resolveCode(libraryCode);
          final style = connection.studentCodeStyle;
          if (style != null && style.isConfigured) {
            await config.updateLoginStudentStyle(style);
            return;
          }
        } catch (_) {
          // Fall through to library API styles.
        }
      }

      final stored = config.libraryStudentStyles;
      final storedLogin = stored?.loginStyle;
      if (storedLogin != null) {
        await config.updateLoginStudentStyle(storedLogin);
        return;
      }

      try {
        final fetched = await _resolver.fetchBranchStyles(config.apiBaseUrl);
        StudentCodeStyle? fallback;
        for (final style in fetched.branchStyles) {
          if (style.isConfigured) {
            fallback = style;
            break;
          }
        }
        final style = fetched.loginStyle ?? fallback;
        if (style != null && style.isConfigured) {
          await config.updateLoginStudentStyle(style);
        }
      } catch (_) {
        // Keep generic field if styles cannot be loaded.
      }
    } finally {
      if (mounted) setState(() => _refreshingStyle = false);
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  String _resolvedStudentCode() {
    final input = _studentCodeController.text.trim();
    final loginStyle = _loginStyle;

    if (loginStyle != null) {
      return loginStyle.format(input);
    }

    final styles = ServerConfig.instance.libraryStudentStyles;
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
    final prefixStyle = _loginStyle;
    final usesPrefixedNumber = prefixStyle != null;

    return Scaffold(
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              child: ConstrainedBox(
                constraints: BoxConstraints(minHeight: constraints.maxHeight - 32),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const LibControlLogo(height: 52, wide: true),
                    const SizedBox(height: 32),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Welcome', style: Theme.of(context).textTheme.titleLarge),
                          const SizedBox(height: 6),
                          Text(
                            usesPrefixedNumber
                                ? 'Enter your student code — we add ${prefixStyle.displayPrefix} for you'
                                : _refreshingStyle
                                    ? 'Loading your library student code format…'
                                    : 'Enter the student code provided by your library',
                            style: const TextStyle(color: AppColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    if (usesPrefixedNumber)
                      PrefixedStudentCodeField(
                        controller: _studentCodeController,
                        style: prefixStyle,
                        onSubmitted: _loading ? null : _continue,
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
                      textAlign: TextAlign.center,
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}
