import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/widgets/app_header.dart';
import 'package:libcontrol_app/widgets/profile_info_tile.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final student = DummyData.student;

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            AppHeader(
              title: 'My Profile',
              showSettings: true,
              onSettingsTap: () {},
            ),
            const SizedBox(height: 24),
            Center(
              child: Stack(
                children: [
                  CircleAvatar(
                    radius: 48,
                    backgroundColor: AppColors.primaryBg,
                    child: Text(
                      student.name.characters.first,
                      style: const TextStyle(
                        fontSize: 36,
                        color: AppColors.primary,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  Positioned(
                    right: 0,
                    bottom: 0,
                    child: Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        color: AppColors.white,
                        borderRadius: BorderRadius.circular(999),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: const Icon(Icons.edit_outlined, size: 18),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Center(child: Text(student.name, style: Theme.of(context).textTheme.titleLarge)),
            const SizedBox(height: 4),
            Center(child: Text(student.id, style: Theme.of(context).textTheme.bodySmall)),
            const SizedBox(height: 24),
            ProfileInfoTile(
              icon: Icons.event_seat_outlined,
              label: 'Current Seat',
              value: '${student.currentHall} — Seat ${student.currentSeat}',
            ),
            ProfileInfoTile(
              icon: Icons.calendar_month_outlined,
              label: 'Plan Valid Till',
              value: student.planValidTill,
              iconBackground: AppColors.successBg,
            ),
            ProfileInfoTile(
              icon: Icons.home_work_outlined,
              label: 'Home Branch',
              value: student.homeBranch,
              iconBackground: AppColors.blueBg,
            ),
            ProfileInfoTile(
              icon: Icons.phone_outlined,
              label: 'Phone Number',
              value: student.phone,
              iconBackground: AppColors.purpleBg,
            ),
            ProfileInfoTile(
              icon: Icons.email_outlined,
              label: 'Email Address',
              value: student.email,
              iconBackground: AppColors.orangeBg,
            ),
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: () => Navigator.of(context).pushReplacementNamed(AppRoutes.login),
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
                foregroundColor: AppColors.danger,
                side: const BorderSide(color: AppColors.danger),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Log Out'),
            ),
          ],
        ),
      ),
    );
  }
}
