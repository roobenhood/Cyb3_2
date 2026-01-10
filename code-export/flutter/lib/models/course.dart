class Course {
  final int id;
  final String title;
  final String description;
  final String instructor;
  final double price;
  final double rating;
  final int students;
  final String duration;
  final String category;
  final String imageUrl;
  final List<Lesson> lessons;

  Course({
    required this.id,
    required this.title,
    required this.description,
    required this.instructor,
    required this.price,
    required this.rating,
    required this.students,
    required this.duration,
    required this.category,
    required this.imageUrl,
    required this.lessons,
  });

  factory Course.fromJson(Map<String, dynamic> json) {
    return Course(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      instructor: json['instructor'] ?? '',
      price: (json['price'] ?? 0).toDouble(),
      rating: (json['rating'] ?? 0).toDouble(),
      students: json['students'] ?? 0,
      duration: json['duration'] ?? '',
      category: json['category'] ?? '',
      imageUrl: json['image_url'] ?? '',
      lessons: (json['lessons'] as List<dynamic>?)
              ?.map((e) => Lesson.fromJson(e))
              .toList() ??
          [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'instructor': instructor,
      'price': price,
      'rating': rating,
      'students': students,
      'duration': duration,
      'category': category,
      'image_url': imageUrl,
      'lessons': lessons.map((e) => e.toJson()).toList(),
    };
  }
}

class Lesson {
  final int id;
  final String title;
  final String duration;
  final String videoUrl;
  final bool isPreview;

  Lesson({
    required this.id,
    required this.title,
    required this.duration,
    required this.videoUrl,
    required this.isPreview,
  });

  factory Lesson.fromJson(Map<String, dynamic> json) {
    return Lesson(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      duration: json['duration'] ?? '',
      videoUrl: json['video_url'] ?? '',
      isPreview: json['is_preview'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'duration': duration,
      'video_url': videoUrl,
      'is_preview': isPreview,
    };
  }
}
