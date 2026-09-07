import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
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
  late DateTime _visibleMonth;
  late DateTime _selectedDate;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _visibleMonth = DateTime(now.year, now.month, 1);
    _selectedDate = DateTime(now.year, now.month, now.day);
  }

  void _changeMonth(int delta) {
    setState(() {
      _visibleMonth = DateTime(_visibleMonth.year, _visibleMonth.month + delta, 1);
    });
  }

  @override
  Widget build(BuildContext context) {
    final summary = DummyData.attendanceSummary;
    final recentRecords = DummyData.attendanceRecords;

    return SafeArea(
      child: SingleChildScrollView(
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
              marks: DummyData.attendanceMarks,
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
                    onActionTap: () => Navigator.of(context).pushNamed(AppRoutes.allAttendance),
                  ),
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
        ),
      ),
    );
  }
}
