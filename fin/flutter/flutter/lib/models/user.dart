/// نموذج المستخدم
class User {
  final int? id;
  final String? firebaseUid;
  final String name;
  final String email;
  final String? password;
  final String? phone;
  final String? avatarUrl;
  final String role;
  final bool isActive;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  User({
    this.id,
    this.firebaseUid,
    required this.name,
    required this.email,
    this.password,
    this.phone,
    this.avatarUrl,
    this.role = 'customer',
    this.isActive = true,
    this.createdAt,
    this.updatedAt,
  });

  bool get isAdmin => role == 'admin';
  bool get isVendor => role == 'vendor';
  bool get isCustomer => role == 'customer';

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int?,
      firebaseUid: json['firebase_uid'] as String?,
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      password: json['password'] as String?,
      phone: json['phone'] as String?,
      avatarUrl: json['avatar_url'] ?? json['avatar'] as String?,
      role: json['role'] as String? ?? 'customer',
      isActive: json['is_active'] == 1 || json['is_active'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'firebase_uid': firebaseUid,
      'name': name,
      'email': email,
      'password': password,
      'phone': phone,
      'avatar_url': avatarUrl,
      'role': role,
      'is_active': isActive ? 1 : 0,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  Map<String, dynamic> toMap() {
    return {
      if (id != null) 'id': id,
      'firebase_uid': firebaseUid,
      'name': name,
      'email': email,
      'password': password,
      'phone': phone,
      'avatar_url': avatarUrl,
      'role': role,
      'is_active': isActive ? 1 : 0,
    };
  }

  User copyWith({
    int? id,
    String? firebaseUid,
    String? name,
    String? email,
    String? password,
    String? phone,
    String? avatarUrl,
    String? role,
    bool? isActive,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return User(
      id: id ?? this.id,
      firebaseUid: firebaseUid ?? this.firebaseUid,
      name: name ?? this.name,
      email: email ?? this.email,
      password: password ?? this.password,
      phone: phone ?? this.phone,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      role: role ?? this.role,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'User(id: $id, name: $name, email: $email, role: $role)';
  }
}
