import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';
import 'package:url_launcher/url_launcher.dart';

class AttendanceHelpScreen extends StatelessWidget {
  const AttendanceHelpScreen({super.key});

  Future<void> _launchPhone(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    final phone = DummyData.libraryPhone.replaceAll(RegExp(r'\s+'), '');
    final uri = Uri(scheme: 'tel', path: phone);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      await Clipboard.setData(ClipboardData(text: DummyData.libraryPhone));
      messenger.showSnackBar(const SnackBar(content: Text('Phone number copied')));
    }
  }

  Future<void> _launchEmail(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    final uri = Uri(
      scheme: 'mailto',
      path: DummyData.libraryEmail,
      queryParameters: {'subject': 'Attendance Query'},
    );
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      await Clipboard.setData(ClipboardData(text: DummyData.libraryEmail));
      messenger.showSnackBar(const SnackBar(content: Text('Email copied')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: CenteredPageHeader(
                title: 'Need Help?',
                subtitle: 'Contact library staff',
                onBack: () => Navigator.of(context).pop(),
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: AppColors.primaryBg,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.support_agent_rounded, color: AppColors.primary, size: 36),
                          const SizedBox(height: 12),
                          Text(
                            'Contact Library Staff',
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'If your attendance record looks incorrect, reach out to your library branch using the options below.',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                  color: AppColors.textSecondary,
                                ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    _ContactTile(
                      icon: Icons.location_on_outlined,
                      title: 'Branch',
                      value: AuthService.instance.student?.homeBranch ?? DummyData.fallbackStudent.homeBranch,
                      onTap: null,
                    ),
                    const SizedBox(height: 12),
                    _ContactTile(
                      icon: Icons.phone_outlined,
                      title: 'Phone',
                      value: DummyData.libraryPhone,
                      onTap: () => _launchPhone(context),
                    ),
                    const SizedBox(height: 12),
                    _ContactTile(
                      icon: Icons.email_outlined,
                      title: 'Email',
                      value: DummyData.libraryEmail,
                      onTap: () => _launchEmail(context),
                    ),
                    const SizedBox(height: 12),
                    _ContactTile(
                      icon: Icons.schedule_outlined,
                      title: 'Library Hours',
                      value: DummyData.libraryHours,
                      onTap: null,
                    ),
                    const SizedBox(height: 20),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Text(
                        'Tip: Mention your student ID (${AuthService.instance.student?.id ?? ''}) and the date of attendance when contacting staff.',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ContactTile extends StatelessWidget {
  const _ContactTile({
    required this.icon,
    required this.title,
    required this.value,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final child = Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: AppColors.primaryBg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: AppColors.primary),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: Theme.of(context).textTheme.bodySmall),
                const SizedBox(height: 2),
                Text(value, style: Theme.of(context).textTheme.bodyMedium),
              ],
            ),
          ),
          if (onTap != null)
            const Icon(Icons.chevron_right_rounded, color: AppColors.textSecondary),
        ],
      ),
    );

    if (onTap == null) return child;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: child,
      ),
    );
  }
}
