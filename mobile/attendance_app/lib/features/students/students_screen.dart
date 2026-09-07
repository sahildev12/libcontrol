import 'package:flutter/material.dart';

import '../../core/models/attendance_models.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/api_exception.dart';
import '../../core/widgets/common_widgets.dart';
import '../../services/api_service.dart';

class StudentsScreen extends StatefulWidget {
  const StudentsScreen({
    super.key,
    required this.api,
    required this.branchId,
    required this.latitude,
    required this.longitude,
    required this.initialContext,
    required this.onChanged,
  });

  final ApiService api;
  final int branchId;
  final double latitude;
  final double longitude;
  final AttendanceContext initialContext;
  final Future<void> Function() onChanged;

  @override
  State<StudentsScreen> createState() => _StudentsScreenState();
}

class _StudentsScreenState extends State<StudentsScreen> {
  late List<StudentAttendanceRow> _students;
  final _searchController = TextEditingController();
  String _search = '';
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _students = List<StudentAttendanceRow>.from(widget.initialContext.students);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  List<StudentAttendanceRow> get _filtered {
    final term = _search.trim().toLowerCase();
    if (term.isEmpty) return _students;

    return _students.where((row) {
      final haystack = '${row.studentName} ${row.studentCode}'.toLowerCase();
      return haystack.contains(term);
    }).toList();
  }

  int get _pendingCount => _filtered.where((row) => !row.present).length;

  Future<void> _markPresent(StudentAttendanceRow row) async {
    if (row.present) return;

    setState(() => _busy = true);
    try {
      await widget.api.checkInStudent(
        studentId: row.studentId,
        branchId: widget.branchId,
        latitude: widget.latitude,
        longitude: widget.longitude,
      );
      setState(() {
        row.present = true;
        row.methodLabel = 'Staff GPS';
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Marked ${row.studentName} present')),
        );
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _markAllPending() async {
    final pending = _filtered.where((row) => !row.present).toList();
    if (pending.isEmpty) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Mark all pending present?'),
        content: Text('This will mark ${pending.length} student(s) as present.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Confirm')),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() => _busy = true);
    try {
      await widget.api.bulkCheckIn(
        studentIds: pending.map((row) => row.studentId).toList(),
        branchId: widget.branchId,
        latitude: widget.latitude,
        longitude: widget.longitude,
      );
      setState(() {
        for (final row in pending) {
          row.present = true;
          row.methodLabel = 'Staff GPS';
        }
      });
      await widget.onChanged();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Marked ${pending.length} students present')),
        );
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _filtered;

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.initialContext.branchName),
        actions: [
          if (_pendingCount > 0)
            TextButton(
              onPressed: _busy ? null : _markAllPending,
              child: Text('Mark all ($_pendingCount)'),
            ),
        ],
      ),
      body: Stack(
        children: [
          Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded),
                    hintText: 'Search by name or code',
                    suffixIcon: _search.isEmpty
                        ? null
                        : IconButton(
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _search = '');
                            },
                            icon: const Icon(Icons.close_rounded),
                          ),
                  ),
                  onChanged: (value) => setState(() => _search = value),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                child: Row(
                  children: [
                    _FilterChip(
                      label: 'All (${_students.length})',
                      selected: true,
                    ),
                    const SizedBox(width: 8),
                    _FilterChip(
                      label: 'Pending ($_pendingCount)',
                      selected: false,
                    ),
                  ],
                ),
              ),
              Expanded(
                child: filtered.isEmpty
                    ? const EmptyState(
                        icon: Icons.person_search_outlined,
                        title: 'No students found',
                        message: 'Try a different search term.',
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                        itemCount: filtered.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 10),
                        itemBuilder: (context, index) {
                          final row = filtered[index];
                          return _StudentTile(
                            row: row,
                            onMark: _busy || row.present ? null : () => _markPresent(row),
                          );
                        },
                      ),
              ),
            ],
          ),
          if (_busy) const LoadingOverlay(message: 'Saving attendance…'),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.selected});

  final String label;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: selected ? AppColors.primaryLight : Colors.white,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: selected ? AppColors.primary : AppColors.border),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: selected ? AppColors.primary : AppColors.textSecondary,
        ),
      ),
    );
  }
}

class _StudentTile extends StatelessWidget {
  const _StudentTile({required this.row, required this.onMark});

  final StudentAttendanceRow row;
  final VoidCallback? onMark;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onMark,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              CircleAvatar(
                backgroundColor: row.present ? AppColors.successBg : AppColors.primaryLight,
                child: Text(
                  row.initials,
                  style: TextStyle(
                    color: row.present ? AppColors.success : AppColors.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      row.studentName,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      row.studentCode,
                      style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
                    ),
                    if (row.seatLabel != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        row.seatLabel!,
                        style: const TextStyle(color: AppColors.textSecondary, fontSize: 12),
                      ),
                    ],
                  ],
                ),
              ),
              if (row.present)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.successBg,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: const Text(
                    'Present',
                    style: TextStyle(
                      color: AppColors.success,
                      fontWeight: FontWeight.w700,
                      fontSize: 12,
                    ),
                  ),
                )
              else
                FilledButton(
                  onPressed: onMark,
                  style: FilledButton.styleFrom(
                    minimumSize: const Size(72, 40),
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                  ),
                  child: const Text('Mark'),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
