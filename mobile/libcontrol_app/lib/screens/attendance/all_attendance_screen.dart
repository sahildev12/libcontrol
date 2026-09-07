import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_date_filter_bar.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_overview_strip.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_record_card.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';

class AllAttendanceScreen extends StatefulWidget {
  const AllAttendanceScreen({super.key});

  @override
  State<AllAttendanceScreen> createState() => _AllAttendanceScreenState();
}

class _AllAttendanceScreenState extends State<AllAttendanceScreen> {
  late DateTime _filterStart;
  late DateTime _filterEnd;

  @override
  void initState() {
    super.initState();
    _filterStart = DateTime(2026, 8, 1);
    _filterEnd = DateTime(2026, 9, 30);
  }

  List<AttendanceRecord> get _filteredRecords {
    return DummyData.attendanceRecords
        .where((record) {
          final date = DateTime(record.date.year, record.date.month, record.date.day);
          final start = DateTime(_filterStart.year, _filterStart.month, _filterStart.day);
          final end = DateTime(_filterEnd.year, _filterEnd.month, _filterEnd.day);
          return !date.isBefore(start) && !date.isAfter(end);
        })
        .toList()
      ..sort((a, b) => b.date.compareTo(a.date));
  }

  int get _presentCount => _filteredRecords.where((r) => r.status == AttendanceStatus.present).length;

  int get _absentCount => _filteredRecords.where((r) => r.status == AttendanceStatus.absent).length;

  int get _leaveCount => _filteredRecords.where((r) => r.status == AttendanceStatus.late || r.status == AttendanceStatus.notMarked).length;

  int get _attendanceRate {
    final total = _presentCount + _absentCount + _leaveCount;
    if (total == 0) return 0;
    return ((_presentCount / total) * 100).round();
  }

  String get _filterLabel {
    const months = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December',
    ];

    final startLabel = '${months[_filterStart.month - 1]} ${_filterStart.year}';
    final endLabel = '${months[_filterEnd.month - 1]} ${_filterEnd.year}';

    if (_filterStart.year == _filterEnd.year && _filterStart.month == _filterEnd.month) {
      return startLabel;
    }

    return '$startLabel - $endLabel';
  }

  Future<void> _openDateFilter() async {
    DateTime tempStart = _filterStart;
    DateTime tempEnd = _filterEnd;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            Future<void> pickFrom() async {
              final picked = await showDatePicker(
                context: context,
                initialDate: tempStart,
                firstDate: DateTime(2020),
                lastDate: DateTime(2030),
              );
              if (picked != null) {
                setSheetState(() {
                  tempStart = picked;
                  if (tempEnd.isBefore(tempStart)) {
                    tempEnd = tempStart;
                  }
                });
              }
            }

            Future<void> pickTo() async {
              final picked = await showDatePicker(
                context: context,
                initialDate: tempEnd,
                firstDate: tempStart,
                lastDate: DateTime(2030),
              );
              if (picked != null) {
                setSheetState(() => tempEnd = picked);
              }
            }

            return Padding(
              padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.paddingOf(context).bottom + 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Filter by date', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 16),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('From'),
                    subtitle: Text(_formatPickerDate(tempStart)),
                    trailing: const Icon(Icons.calendar_today_outlined, size: 18),
                    onTap: pickFrom,
                  ),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('To'),
                    subtitle: Text(_formatPickerDate(tempEnd)),
                    trailing: const Icon(Icons.calendar_today_outlined, size: 18),
                    onTap: pickTo,
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () {
                        setState(() {
                          _filterStart = tempStart;
                          _filterEnd = tempEnd;
                        });
                        Navigator.of(sheetContext).pop();
                      },
                      style: FilledButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Apply filter'),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  String _formatPickerDate(DateTime date) {
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    final records = _filteredRecords;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Column(
          children: [
            CenteredPageHeader(
              title: 'All Attendance',
              subtitle: 'Check your daily attendance record',
              onBack: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                children: [
                  AttendanceOverviewStrip(
                    rate: _attendanceRate,
                    present: _presentCount,
                    absent: _absentCount,
                    leave: _leaveCount,
                  ),
                  const SizedBox(height: 12),
                  AttendanceDateFilterBar(
                    label: _filterLabel,
                    onTap: _openDateFilter,
                  ),
                  const SizedBox(height: 12),
                  if (records.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(32),
                      decoration: BoxDecoration(
                        color: AppColors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Center(
                        child: Text(
                          'No attendance records for this period.',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ),
                    )
                  else
                    ...records.map(
                      (record) => Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: AttendanceRecordCard(
                          record: record,
                          onTap: () => Navigator.of(context).pushNamed(
                            AppRoutes.attendanceDetail,
                            arguments: record,
                          ),
                        ),
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
