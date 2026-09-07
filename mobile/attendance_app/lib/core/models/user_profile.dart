class UserProfile {
  const UserProfile({
    required this.id,
    required this.name,
    required this.email,
    this.branchId,
    this.branchName,
    required this.isPlatformAdmin,
    required this.branches,
  });

  final int id;
  final String name;
  final String email;
  final int? branchId;
  final String? branchName;
  final bool isPlatformAdmin;
  final List<BranchOption> branches;

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    final branches = (json['branches'] as List? ?? [])
        .map((item) => BranchOption.fromJson(Map<String, dynamic>.from(item as Map)))
        .toList();

    return UserProfile(
      id: json['id'] as int,
      name: json['name'] as String? ?? 'Staff',
      email: json['email'] as String? ?? '',
      branchId: json['branch_id'] as int?,
      branchName: json['branch_name'] as String?,
      isPlatformAdmin: json['is_platform_admin'] == true,
      branches: branches,
    );
  }
}

class BranchOption {
  const BranchOption({required this.id, required this.name});

  final int id;
  final String name;

  factory BranchOption.fromJson(Map<String, dynamic> json) {
    return BranchOption(
      id: json['id'] as int,
      name: json['name'] as String? ?? 'Branch',
    );
  }
}
