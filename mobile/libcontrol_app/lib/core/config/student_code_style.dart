class StudentCodeStyle {
  const StudentCodeStyle({
    required this.prefix,
    this.padding = 3,
  });

  final String prefix;
  final int padding;

  bool get isConfigured => prefix.trim().isNotEmpty;

  String get displayPrefix => '${prefix.toUpperCase()}-';

  String format(String input) {
    final raw = input.trim().toUpperCase();
    if (raw.isEmpty) {
      return '';
    }

    if (raw.contains('-')) {
      return raw;
    }

    if (!isConfigured) {
      return raw;
    }

    final digits = raw.replaceAll(RegExp(r'\D'), '');
    if (digits.isEmpty) {
      return '';
    }

    final padded = digits.padLeft(padding.clamp(1, 6), '0');
    return '$prefix-$padded';
  }

  factory StudentCodeStyle.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const StudentCodeStyle(prefix: '');
    }

    return StudentCodeStyle(
      prefix: (json['student_code_prefix'] as String? ?? '').trim().toUpperCase(),
      padding: (json['student_code_padding'] as num?)?.toInt() ?? 3,
    );
  }

  Map<String, dynamic> toJson() => {
    'student_code_prefix': prefix,
    'student_code_padding': padding,
  };
}
