import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/courses_provider.dart';

class CoursesScreen extends StatelessWidget {
  const CoursesScreen({super.key});
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الكورسات')),
      body: Consumer<CoursesProvider>(builder: (context, provider, child) {
        if (provider.isLoading) return const Center(child: CircularProgressIndicator());
        return ListView.builder(
          itemCount: provider.courses.length,
          itemBuilder: (context, index) {
            final course = provider.courses[index];
            return ListTile(
              title: Text(course.title),
              subtitle: Text('${course.effectivePrice} \$'),
              onTap: () => Navigator.pushNamed(context, '/course-detail', arguments: course.id),
            );
          },
        );
      }),
    );
  }
}
