import 'package:flutter/material.dart';
import 'package:libcontrol_app/app/routes/app_routes.dart';
import 'package:libcontrol_app/app/theme/app_colors.dart';
import 'package:libcontrol_app/data/dummy_data.dart';
import 'package:libcontrol_app/models/attendance_record.dart';
import 'package:libcontrol_app/widgets/attendance/attendance_record_tile.dart';
import 'package:libcontrol_app/widgets/centered_page_header.dart';

class AllAttendanceScreen extends StatelessWidget {
  const AllAttendanceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final records = List<AttendanceRecord>.from(DummyData.attendanceRecords)
      ..sort((a, b) => b.date.compareTo(a.date));

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            CenteredPageHeader(
              title: 'All Attendance',
              onBack: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                child: Container(
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
                      for (var i = 0; i < records.length; i++)
                        AttendanceRecordTile(
                          record: records[i],
                          showDivider: i < records.length - 1,
                          onTap: () => Navigator.of(context).pushNamed(
                            AppRoutes.attendanceDetail,
                            arguments: records[i],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
