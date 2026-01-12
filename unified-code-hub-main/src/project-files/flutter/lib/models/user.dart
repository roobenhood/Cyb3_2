class User {
  final int? id;
  final String? firebaseUid;
  final String name;
  final String email;
  final String? password;
  final String? phone;
  final String? avatarUrl;
  final String? address;
  final String? city;
  final String? country;
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
    this.address,
    this.city,
    this.country,
    this.role = 'customer',
    this.isActive = true,
    this.createdAt,
    this.updatedAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int?,
      firebaseUid: json['firebase_uid'] as String?,
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      password: json['password'] as String?,
      phone: json['phone'] as String?,
      avatarUrl: json['avatar_url'] as String?,
      address: json['address'] as String?,
      city: json['city'] as String?,
      country: json['country'] as String?,
      role: json['role'] as String? ?? 'customer',
      isActive: (json['is_active'] as int?) == 1,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'] as String)
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
      'address': address,
      'city': city,
      'country': country,
      'role': role,
      'is_active': isActive ? 1 : 0,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
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
    String? address,
    String? city,
    String? country,
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
      address: address ?? this.address,
      city: city ?? this.city,
      country: country ?? this.country,
      role: role ?? this.role,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  bool get isAdmin => role == 'admin';
  bool get isCustomer => role == 'customer';
}
