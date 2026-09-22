import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/core/api/api_client.dart';
import 'package:libcontrol_app/core/api/attendance_api.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_calendar.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_help_card.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_record_tile.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_summary_card.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';
import 'package:libcontrol_app/widgets/section_header.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key, this.onBack});

  final VoidCallback? onBack;

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  final _api = AttendanceApi();
  late DateTime _visibleMonth;
  late DateTime _selectedDate;

  AttendanceDashboard? _dashboard;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _visibleMonth = DateTime(now.year, now.month, 1);
    _selectedDate = DateTime(now.year, now.month, now.day);
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final dashboard = await _api.fetchDashboard();
      if (!mounted) return;
      setState(() {
        _dashboard = dashboard;
        _loading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'Could not load attendance. Pull to refresh.';
        _loading = false;
      });
    }
  }

  void _changeMonth(int delta) {
    setState(() {
      _visibleMonth = DateTime(_visibleMonth.year, _visibleMonth.month + delta, 1);
    });
  }

  @override
  Widget build(BuildContext context) {
    final summary = _dashboard;
    final recentRecords = summary?.recent ?? const <AttendanceRecord>[];
    final marks = summary?.marks ?? const <DateTime, AttendanceStatus>{};

    return SafeArea(
      child: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _load,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CenteredPageHeader(
                title: 'My Attendance',
                subtitle: 'Track your library visits',
                onBack: widget.onBack,
              ),
              const SizedBox(height: 20),
              if (_loading && summary == null)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 48),
                  child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
                )
              else if (_error != null && summary == null)
                _ErrorBanner(message: _error!, onRetry: _load)
              else if (summary != null) ...[
                AttendanceSummaryCard(
                  rate: summary.rate,
                  present: summary.present,
                  absent: summary.absent,
                  late: summary.late,
                ),
                const SizedBox(height: 16),
                AttendanceCalendar(
                  month: _visibleMonth,
                  selectedDate: _selectedDate,
                  marks: marks,
                  onDateSelected: (date) => setState(() => _selectedDate = date),
                  onPreviousMonth: () => _changeMonth(-1),
                  onNextMonth: () => _changeMonth(1),
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 4),
                  decoration: BoxDecoration(
                    color: AppColors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                    boxShadow: const [
                      BoxShadow(color: Color(0x08000000), blurRadius: 12, offset: Offset(0, 4)),
                    ],
                  ),
                  child: Column(
                    children: [
                      SectionHeader(
                        title: 'Recent Attendance',
                        actionLabel: 'View All',
                        onActionTap: () => Navigator.of(context).pushNamed(
                          AppRoutes.allAttendance,
                          arguments: summary.records,
                        ),
                      ),
                      if (recentRecords.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: Text(
                            'No check-ins yet. Scan the library QR to mark attendance.',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        )
                      else
                        ...recentRecords.asMap().entries.map(
                          (entry) => AttendanceRecordTile(
                            record: entry.value,
                            showDivider: entry.key < recentRecords.length - 1,
                            onTap: () => Navigator.of(context).pushNamed(
                              AppRoutes.attendanceDetail,
                              arguments: entry.value,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const AttendanceHelpCard(),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.dangerBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.danger.withValues(alpha: 0.35)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(message, style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 12),
          TextButton(
            onPressed: onRetry,
            child: const Text('Try again'),
          ),
        ],
      ),
    );
  }
}
