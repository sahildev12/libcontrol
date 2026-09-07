class StudentLookup {
  const StudentLookup({
    required this.studentCode,
    required this.name,
    required this.homeBranch,
    required this.needsPinSetup,
    this.setupToken,
  });

  final String studentCode;
  final String name;
  final String homeBranch;
  final bool needsPinSetup;
  final String? setupToken;

  factory StudentLookup.fromJson(Map<String, dynamic> json) {
    final student = json['student'] as Map<String, dynamic>? ?? {};

    return StudentLookup(
      studentCode: student['student_code'] as String? ?? '',
      name: student['name'] as String? ?? '',
      homeBranch: student['home_branch'] as String? ?? '',
      needsPinSetup: json['needs_pin_setup'] as bool? ?? false,
      setupToken: json['setup_token'] as String?,
    );
  }
}
