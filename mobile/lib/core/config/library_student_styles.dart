import 'package:libcontrol_app/core/config/student_code_style.dart';

class LibraryStudentStyles {
  const LibraryStudentStyles({
    required this.multiBranchPrefixes,
    required this.branchStyles,
  });

  final bool multiBranchPrefixes;
  final List<StudentCodeStyle> branchStyles;

  bool get hasConfiguredStyles =>
      branchStyles.any((style) => style.isConfigured);

  StudentCodeStyle? get singleStyle {
    if (!hasConfiguredStyles || multiBranchPrefixes) {
      return null;
    }

    final uniquePrefixes =
        branchStyles.map((style) => style.prefix).where((p) => p.isNotEmpty).toSet();

    if (uniquePrefixes.length != 1) {
      return null;
    }

    return branchStyles.firstWhere((style) => style.isConfigured);
  }

  List<String> get sampleCodes => branchStyles
      .where((style) => style.isConfigured)
      .map((style) => style.format('1'))
      .toList();

  String format(String input) {
    final raw = input.trim().toUpperCase();
    if (raw.isEmpty) {
      return '';
    }

    if (raw.contains('-')) {
      return raw;
    }

    final single = singleStyle;
    if (single != null) {
      return single.format(raw);
    }

    for (final style in branchStyles) {
      if (!style.isConfigured) {
        continue;
      }

      if (raw.startsWith(style.prefix) && raw.length > style.prefix.length) {
        final digits =
            raw.substring(style.prefix.length).replaceAll(RegExp(r'\D'), '');
        if (digits.isNotEmpty) {
          return style.format(digits);
        }
      }
    }

    return raw;
  }

  factory LibraryStudentStyles.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const LibraryStudentStyles(
        multiBranchPrefixes: false,
        branchStyles: [],
      );
    }

    final styles = <StudentCodeStyle>[];
    final branchStyles = json['branch_styles'];
    if (branchStyles is List) {
      for (final item in branchStyles) {
        if (item is Map<String, dynamic>) {
          styles.add(StudentCodeStyle.fromJson(item));
        }
      }
    }

    return LibraryStudentStyles(
      multiBranchPrefixes: json['multi_branch_prefixes'] == true,
      branchStyles: styles,
    );
  }

  Map<String, dynamic> toJson() => {
    'multi_branch_prefixes': multiBranchPrefixes,
    'branch_styles': branchStyles.map((style) => style.toJson()).toList(),
  };
}
