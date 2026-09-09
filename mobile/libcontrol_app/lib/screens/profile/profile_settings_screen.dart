import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/auth/auth_service.dart';
import 'package:libcontrol_app/core/config/server_config.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';

class ProfileSettingsScreen extends StatefulWidget {
  const ProfileSettingsScreen({super.key});

  @override
  State<ProfileSettingsScreen> createState() => _ProfileSettingsScreenState();
}

class _ProfileSettingsScreenState extends State<ProfileSettingsScreen> {
  bool _pushNotifications = true;
  bool _emailUpdates = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: CenteredPageHeader(
                title: 'Settings',
                subtitle: 'Manage your preferences',
                onBack: () => Navigator.of(context).pop(),
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                children: [
                  _SettingsCard(
                    children: [
                      SwitchListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                        title: const Text('Push Notifications'),
                        subtitle: const Text('Receive alerts for announcements'),
                        value: _pushNotifications,
                        activeThumbColor: AppColors.primary,
                        onChanged: (value) => setState(() => _pushNotifications = value),
                      ),
                      const Divider(height: 1, color: AppColors.border),
                      SwitchListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                        title: const Text('Email Updates'),
                        subtitle: const Text('Get attendance summaries by email'),
                        value: _emailUpdates,
                        activeThumbColor: AppColors.primary,
                        onChanged: (value) => setState(() => _emailUpdates = value),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _SettingsCard(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.domain_rounded, color: AppColors.primary),
                        title: const Text('Connected library'),
                        subtitle: Text(
                          [
                            if (ServerConfig.instance.libraryCode != null)
                              'Code ${ServerConfig.instance.libraryCode}',
                            ServerConfig.instance.libraryName ??
                                ServerConfig.instance.apiBaseUrl,
                          ].join(' · '),
                        ),
                      ),
                      const Divider(height: 1, color: AppColors.border),
                      ListTile(
                        leading: const Icon(Icons.swap_horiz_rounded, color: AppColors.primary),
                        title: const Text('Change library'),
                        subtitle: const Text('Switch to another library server'),
                        onTap: () async {
                          await AuthService.instance.logout();
                          await ServerConfig.instance.clear();
                          if (!context.mounted) return;
                          Navigator.of(context).popUntil((route) => route.isFirst);
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _SettingsCard(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.info_outline_rounded, color: AppColors.primary),
                        title: const Text('About LibControl'),
                        subtitle: const Text('Version 1.0.0'),
                        onTap: () {},
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SettingsCard extends StatelessWidget {
  const _SettingsCard({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(children: children),
    );
  }
}
