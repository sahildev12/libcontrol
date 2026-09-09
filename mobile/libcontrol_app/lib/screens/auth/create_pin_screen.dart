import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/auth/auth_navigation.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/models/student_lookup.dart';
import 'package:libcontrol_app/widgets/primary_button.dart';

class CreatePinScreen extends StatefulWidget {
  const CreatePinScreen({
    super.key,
    required this.lookup,
    this.onAuthSuccess,
    this.isReset = false,
    this.showBackButton = true,
  });

  final StudentLookup lookup;
  final VoidCallback? onAuthSuccess;
  final bool isReset;
  final bool showBackButton;

  @override
  State<CreatePinScreen> createState() => _CreatePinScreenState();
}

class _CreatePinScreenState extends State<CreatePinScreen> {
  final _pinController = TextEditingController();
  final _confirmPinController = TextEditingController();
  bool _obscurePin = true;
  bool _obscureConfirmPin = true;
  bool _loading = false;

  @override
  void dispose() {
    _pinController.dispose();
    _confirmPinController.dispose();
    super.dispose();
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating),
    );
  }

  Future<void> _createPin() async {
    final pin = _pinController.text.trim();
    final confirmation = _confirmPinController.text.trim();

    if (!RegExp(r'^\d{4,6}$').hasMatch(pin)) {
      _showMessage('PIN must be 4–6 digits.');
      return;
    }

    if (pin != confirmation) {
      _showMessage('PINs do not match.');
      return;
    }

    setState(() => _loading = true);

    try {
      await AuthService.instance.setupPin(
        pin: pin,
        pinConfirmation: confirmation,
        setupToken: widget.lookup.setupToken,
      );

      if (!mounted) return;
      if (widget.showBackButton) {
        completeStudentAuthFlow(context);
      }
      widget.onAuthSuccess?.call();
    } on ApiException catch (e) {
      _showMessage(e.message);
    } catch (_) {
      _showMessage('Could not save your PIN. Please try again.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.isReset ? 'Reset PIN' : 'Create PIN';
    final subtitle = widget.isReset
        ? 'Choose a new 4–6 digit PIN for signing in to the app.'
        : 'Create a 4–6 digit PIN for signing in to the app.';

    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: widget.showBackButton,
        title: Text(title),
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
              const SizedBox(height: 8),
              Text(
                subtitle,
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
                  labelText: widget.isReset ? 'New PIN' : 'PIN',
                  prefixIcon: const Icon(Icons.lock_outline_rounded),
                  suffixIcon: IconButton(
                    onPressed: () => setState(() => _obscurePin = !_obscurePin),
                    icon: Icon(_obscurePin ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _confirmPinController,
                obscureText: _obscureConfirmPin,
                keyboardType: TextInputType.number,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(6),
                ],
                decoration: InputDecoration(
                  labelText: 'Confirm PIN',
                  prefixIcon: const Icon(Icons.lock_outline_rounded),
                  suffixIcon: IconButton(
                    onPressed: () => setState(() => _obscureConfirmPin = !_obscureConfirmPin),
                    icon: Icon(_obscureConfirmPin ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                  ),
                ),
                onSubmitted: (_) => _loading ? null : _createPin(),
              ),
              const SizedBox(height: 24),
              PrimaryButton(
                label: _loading
                    ? (widget.isReset ? 'Saving PIN...' : 'Creating PIN...')
                    : (widget.isReset ? 'Save new PIN' : 'Create PIN'),
                isLoading: _loading,
                onPressed: _loading ? null : _createPin,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
