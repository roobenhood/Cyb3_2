class Course {
  final int? id;
  final String title;
  final String? description;
  final int? instructorId;
  final String? instructorName;
  final double price;
  final double? discountPrice;
  final double rating;
  final int studentsCount;
  final String? duration;
  final String? category;
  final String level;
  final String? language;
  final String? imageUrl;
  final String? previewVideoUrl;
  final bool isPublished;
  final bool isFeatured;
  final List<Lesson> lessons;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Course({
    this.id,
    required this.title,
    this.description,
    this.instructorId,
    this.instructorName,
    this.price = 0,
    this.discountPrice,
    this.rating = 0,
    this.studentsCount = 0,
    this.duration,
    this.category,
    this.level = 'beginner',
    this.language = 'ar',
    this.imageUrl,
    this.previewVideoUrl,
    this.isPublished = false,
    this.isFeatured = false,
    this.lessons = const [],
    this.createdAt,
    this.updatedAt,
  });

  double get effectivePrice => discountPrice ?? price;

  bool get hasDiscount => discountPrice != null && discountPrice! < price;

  double get discountPercentage {
    if (!hasDiscount) return 0;
    return ((price - discountPrice!) / price * 100);
  }

  factory Course.fromJson(Map<String, dynamic> json) {
    return Course(
      id: json['id'] as int?,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      instructorId: json['instructor_id'] as int?,
      instructorName: json['instructor_name'] as String?,
      price: (json['price'] as num?)?.toDouble() ?? 0.0,
      discountPrice: json['discount_price'] != null
          ? (json['discount_price'] as num).toDouble()
          : null,
      rating: (json['rating'] as num?)?.toDouble() ?? 0.0,
      studentsCount: json['students_count'] as int? ?? 0,
      duration: json['duration'] as String?,
      category: json['category'] as String?,
      level: json['level'] as String? ?? 'beginner',
      language: json['language'] as String? ?? 'ar',
      imageUrl: json['image_url'] as String?,
      previewVideoUrl: json['preview_video_url'] as String?,
      isPublished: (json['is_published'] as int?) == 1,
      isFeatured: (json['is_featured'] as int?) == 1,
      lessons: json['lessons'] != null
          ? (json['lessons'] as List).map((l) => Lesson.fromJson(l)).toList()
          : [],
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
      'title': title,
      'description': description,
      'instructor_id': instructorId,
      'instructor_name': instructorName,
      'price': price,
      'discount_price': discountPrice,
      'rating': rating,
      'students_count': studentsCount,
      'duration': duration,
      'category': category,
      'level': level,
      'language': language,
      'image_url': imageUrl,
      'preview_video_url': previewVideoUrl,
      'is_published': isPublished ? 1 : 0,
      'is_featured': isFeatured ? 1 : 0,
      'lessons': lessons.map((l) => l.toJson()).toList(),
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }
}

class Lesson {
  final int? id;
  final int courseId;
  final String title;
  final String? description;
  final String? videoUrl;
  final String? duration;
  final int orderIndex;
  final bool isPreview;
  final String? resources;
  final DateTime? createdAt;

  Lesson({
    this.id,
    required this.courseId,
    required this.title,
    this.description,
    this.videoUrl,
    this.duration,
    this.orderIndex = 0,
    this.isPreview = false,
    this.resources,
    this.createdAt,
  });

  factory Lesson.fromJson(Map<String, dynamic> json) {
    return Lesson(
      id: json['id'] as int?,
      courseId: json['course_id'] as int? ?? 0,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      videoUrl: json['video_url'] as String?,
      duration: json['duration'] as String?,
      orderIndex: json['order_index'] as int? ?? 0,
      isPreview: (json['is_preview'] as int?) == 1,
      resources: json['resources'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'course_id': courseId,
      'title': title,
      'description': description,
      'video_url': videoUrl,
      'duration': duration,
      'order_index': orderIndex,
      'is_preview': isPreview ? 1 : 0,
      'resources': resources,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}
