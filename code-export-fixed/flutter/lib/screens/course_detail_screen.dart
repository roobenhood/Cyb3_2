import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/courses_provider.dart';
import '../providers/auth_provider.dart';
import '../providers/cart_provider.dart';
import '../models/course.dart';

class CourseDetailScreen extends StatelessWidget {
  final int courseId;
  const CourseDetailScreen({super.key, required this.courseId});

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Course?>(
      future: context.read<CoursesProvider>().getCourseById(courseId),
      builder: (context, snapshot) {
        if (!snapshot.hasData) return const Scaffold(body: Center(child: CircularProgressIndicator()));
        final course = snapshot.data!;
        return Scaffold(
          appBar: AppBar(title: Text(course.title)),
          body: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Container(height: 200, color: Colors.blue.shade100, child: const Center(child: Icon(Icons.play_circle, size: 80))),
              const SizedBox(height: 16),
              Text(course.title, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text('${course.instructorName}', style: TextStyle(color: Colors.grey.shade600)),
              const SizedBox(height: 16),
              Row(children: [
                const Icon(Icons.star, color: Colors.amber), Text(' ${course.rating}'),
                const SizedBox(width: 16),
                const Icon(Icons.people), Text(' ${course.studentsCount} طالب'),
                const SizedBox(width: 16),
                const Icon(Icons.timer), Text(' ${course.duration}'),
              ]),
              const SizedBox(height: 16),
              Text(course.description),
              const SizedBox(height: 24),
              const Text('الدروس', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              ...course.lessons.map((lesson) => ListTile(
                leading: Icon(lesson.isPreview ? Icons.play_circle : Icons.lock),
                title: Text(lesson.title),
                subtitle: Text(lesson.duration ?? ''),
              )),
            ],
          ),
          bottomNavigationBar: Padding(
            padding: const EdgeInsets.all(16),
            child: ElevatedButton(
              onPressed: () async {
                final user = context.read<AuthProvider>().user;
                if (user != null) {
                  await context.read<CartProvider>().addToCart(user.id!, course);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تمت الإضافة للسلة')));
                }
              },
              child: Text('إضافة للسلة - ${course.effectivePrice} \$'),
            ),
          ),
        );
      },
    );
  }
}
