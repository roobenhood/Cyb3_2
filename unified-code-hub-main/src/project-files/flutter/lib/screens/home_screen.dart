import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../providers/courses_provider.dart';
import '../models/course.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    context.read<CoursesProvider>().loadCourses();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('منصة الكورسات'),
        actions: [
          IconButton(icon: const Icon(Icons.shopping_cart), onPressed: () => Navigator.pushNamed(context, '/cart')),
          IconButton(icon: const Icon(Icons.logout), onPressed: () {
            context.read<AuthProvider>().logout();
            Navigator.pushReplacementNamed(context, '/login');
          }),
        ],
      ),
      body: Consumer<CoursesProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) return const Center(child: CircularProgressIndicator());
          return RefreshIndicator(
            onRefresh: () => provider.loadCourses(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (provider.featuredCourses.isNotEmpty) ...[
                  const Text('الكورسات المميزة', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 220,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      itemCount: provider.featuredCourses.length,
                      itemBuilder: (context, index) => _CourseCard(course: provider.featuredCourses[index]),
                    ),
                  ),
                  const SizedBox(height: 24),
                ],
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('جميع الكورسات', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                    DropdownButton<String>(
                      hint: const Text('الفئة'),
                      items: provider.categories.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
                      onChanged: (v) => provider.setFilters(category: v),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                ...provider.courses.map((c) => _CourseListTile(course: c)),
              ],
            ),
          );
        },
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (i) {
          setState(() => _currentIndex = i);
          if (i == 1) Navigator.pushNamed(context, '/courses');
          if (i == 2) Navigator.pushNamed(context, '/profile');
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home), label: 'الرئيسية'),
          BottomNavigationBarItem(icon: Icon(Icons.school), label: 'الكورسات'),
          BottomNavigationBarItem(icon: Icon(Icons.person), label: 'حسابي'),
        ],
      ),
    );
  }
}

class _CourseCard extends StatelessWidget {
  final Course course;
  const _CourseCard({required this.course});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(left: 12),
      child: InkWell(
        onTap: () => Navigator.pushNamed(context, '/course-detail', arguments: course.id),
        child: SizedBox(
          width: 200,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(height: 80, color: Colors.blue.shade100, child: const Center(child: Icon(Icons.play_circle, size: 40))),
                const SizedBox(height: 8),
                Text(course.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.bold)),
                const Spacer(),
                Row(children: [const Icon(Icons.star, size: 16, color: Colors.amber), Text(' ${course.rating}')]),
                Text('${course.effectivePrice} \$', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _CourseListTile extends StatelessWidget {
  final Course course;
  const _CourseListTile({required this.course});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        onTap: () => Navigator.pushNamed(context, '/course-detail', arguments: course.id),
        leading: Container(width: 60, height: 60, color: Colors.blue.shade100, child: const Icon(Icons.play_circle)),
        title: Text(course.title),
        subtitle: Text('${course.instructorName ?? ""} • ${course.duration ?? ""}'),
        trailing: Text('${course.effectivePrice} \$', style: const TextStyle(color: Colors.green)),
      ),
    );
  }
}
