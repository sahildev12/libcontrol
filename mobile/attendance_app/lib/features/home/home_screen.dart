import 'package:flutter/material.dart';

import '../../core/models/attendance_models.dart';
import '../../core/models/user_profile.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/api_exception.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/stat_card.dart';
import '../../core/widgets/status_banner.dart';
import '../../services/api_service.dart';
import '../../services/location_service.dart';
import '../../services/storage_service.dart';
import '../students/students_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    super.key,
    required this.api,
    required this.storage,
    required this.onLogout,
  });

  final ApiService api;
  final StorageService storage;
  final Future<void> Function() onLogout;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _location = LocationService();
  UserProfile? _user;
  int? _selectedBranchId;
  bool _loading = true;
  String? _error;
  double? _lat;
  double? _lng;
  bool _insideGeofence = false;
  AttendanceContext? _context;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final user = await widget.api.me();
      final branchId = user.branchId ?? (user.branches.isNotEmpty ? user.branches.first.id : null);

      _user = user;
      _selectedBranchId = branchId;

      if (branchId != null) {
        await _refreshLocationAndContext();
      }
    } on ApiException catch (e) {
      _error = e.message;
    } catch (e) {
      _error = e.toString().replaceFirst('Exception: ', '');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _refreshLocationAndContext() async {
    if (_selectedBranchId == null) {
      throw ApiException('No branch available for your account.');
    }

    final position = await _location.currentPosition();
    _lat = position.latitude;
    _lng = position.longitude;

    final context = await widget.api.attendanceContext(branchId: _selectedBranchId!);
    final settings = context.settings;

    _insideGeofence = _location.withinGeofence(
      lat: _lat!,
      lng: _lng!,
      fenceLat: settings.latitude,
      fenceLng: settings.longitude,
      radiusMeters: settings.radiusMeters,
    );

    _context = context;
  }

  Future<void> _openStudents() async {
    if (_selectedBranchId == null || _lat == null || _lng == null || _context == null) {
      return;
    }

    if (!_insideGeofence) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('You must be inside the library geofence to mark attendance.')),
      );
      return;
    }

    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => StudentsScreen(
          api: widget.api,
          branchId: _selectedBranchId!,
          latitude: _lat!,
          longitude: _lng!,
          initialContext: _context!,
          onChanged: _bootstrap,
        ),
      ),
    );

    await _bootstrap();
  }

  Future<void> _confirmLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sign out?'),
        content: const Text('You will need to sign in again to mark attendance.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Sign out')),
        ],
      ),
    );

    if (confirmed == true) {
      await widget.onLogout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final branches = _user?.branches ?? [];
    final summary = _context?.summary;
    final canMark = _insideGeofence && _context != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Attendance'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _bootstrap,
            icon: const Icon(Icons.refresh_rounded),
          ),
          IconButton(
            tooltip: 'Sign out',
            onPressed: _confirmLogout,
            icon: const Icon(Icons.logout_rounded),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? EmptyState(
                  icon: Icons.cloud_off_outlined,
                  title: 'Could not load dashboard',
                  message: _error!,
                  action: FilledButton(onPressed: _bootstrap, child: const Text('Try again')),
                )
              : RefreshIndicator(
                  onRefresh: _bootstrap,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
                    children: [
                      _WelcomeCard(name: _user?.name ?? 'Staff', email: _user?.email ?? ''),
                      const SizedBox(height: 16),
                      if (branches.isNotEmpty)
                        DropdownButtonFormField<int>(
                          value: _selectedBranchId,
                          decoration: const InputDecoration(
                            labelText: 'Branch',
                            prefixIcon: Icon(Icons.storefront_outlined),
                          ),
                          items: branches
                              .map(
                                (branch) => DropdownMenuItem<int>(
                                  value: branch.id,
                                  child: Text(branch.name),
                                ),
                              )
                              .toList(),
                          onChanged: (value) async {
                            setState(() => _selectedBranchId = value);
                            await _bootstrap();
                          },
                        ),
                      const SizedBox(height: 16),
                      StatusBanner(
                        title: _insideGeofence ? 'Inside library geofence' : 'Outside library geofence',
                        subtitle: _lat == null
                            ? 'Fetching GPS location…'
                            : 'Lat ${_lat!.toStringAsFixed(5)}, Lng ${_lng!.toStringAsFixed(5)}',
                        icon: _insideGeofence ? Icons.location_on_rounded : Icons.location_off_rounded,
                        isPositive: _insideGeofence,
                        trailing: IconButton(
                          onPressed: _bootstrap,
                          icon: const Icon(Icons.my_location_outlined),
                        ),
                      ),
                      if (summary != null) ...[
                        const SizedBox(height: 20),
                        SectionHeader(
                          title: "Today's register",
                          subtitle: _context?.branchName ?? 'Branch summary',
                        ),
                        Row(
                          children: [
                            Expanded(
                              child: StatCard(
                                label: 'Present',
                                value: '${summary.present}',
                                icon: Icons.check_circle_outline,
                                accentColor: AppColors.success,
                                backgroundColor: AppColors.successBg,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: StatCard(
                                label: 'Absent',
                                value: '${summary.absent}',
                                icon: Icons.person_off_outlined,
                                accentColor: AppColors.warning,
                                backgroundColor: AppColors.warningBg,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        StatCard(
                          label: 'Total students',
                          value: '${summary.totalStudents}',
                          icon: Icons.groups_outlined,
                        ),
                      ],
                      const SizedBox(height: 24),
                      FilledButton.icon(
                        onPressed: canMark ? _openStudents : null,
                        icon: const Icon(Icons.how_to_reg_outlined),
                        label: const Text('Mark student attendance'),
                      ),
                      if (!canMark) ...[
                        const SizedBox(height: 10),
                        Text(
                          'Move inside the configured geofence to unlock attendance marking.',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: AppColors.textSecondary,
                              ),
                        ),
                      ],
                    ],
                  ),
                ),
    );
  }
}

class _WelcomeCard extends StatelessWidget {
  const _WelcomeCard({required this.name, required this.email});

  final String name;
  final String email;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(
          children: [
            CircleAvatar(
              radius: 26,
              backgroundColor: AppColors.primaryLight,
              child: Text(
                name.isNotEmpty ? name[0].toUpperCase() : 'S',
                style: const TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 20,
                ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Hello, $name',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    email,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: AppColors.textSecondary,
                        ),
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
