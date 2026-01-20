import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../conestant/responsiveSize.dart';
import '../config/app_config.dart';

class IntroductionScreen extends StatefulWidget {
  const IntroductionScreen({super.key});

  @override
  State<IntroductionScreen> createState() => _IntroductionScreenState();
}

class _IntroductionScreenState extends State<IntroductionScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;

  final List<IntroPage> _pages = [
    IntroPage(
      icon: Icons.shopping_bag,
      title: 'تصفح المنتجات',
      description: 'اكتشف آلاف المنتجات من مختلف الفئات بأفضل الأسعار',
    ),
    IntroPage(
      icon: Icons.favorite,
      title: 'احفظ مفضلاتك',
      description: 'أضف المنتجات إلى قائمة المفضلة للوصول إليها بسهولة',
    ),
    IntroPage(
      icon: Icons.local_shipping,
      title: 'توصيل سريع',
      description: 'استلم طلباتك بسرعة مع خدمة التوصيل المميزة',
    ),
    IntroPage(
      icon: Icons.security,
      title: 'دفع آمن',
      description: 'تسوق بأمان مع طرق دفع متعددة ومحمية',
    ),
  ];

  Future<void> _completeIntro() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConfig.introSeenKey, true);

    if (mounted) {
      Navigator.of(context).pushReplacementNamed('/login');
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            // زر التخطي
            Align(
              alignment: Alignment.topLeft,
              child: TextButton(
                onPressed: _completeIntro,
                child: const Text('تخطي'),
              ),
            ),

            // محتوى الصفحات
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                itemCount: _pages.length,
                onPageChanged: (index) {
                  setState(() => _currentPage = index);
                },
                itemBuilder: (context, index) {
                  return _buildPage(_pages[index]);
                },
              ),
            ),

            // مؤشرات الصفحات
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                _pages.length,
                (index) => AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  margin: EdgeInsets.symmetric(horizontal: 4.w(context)),
                  width: _currentPage == index ? 24.w(context) : 8.w(context),
                  height: 8.h(context),
                  decoration: BoxDecoration(
                    color: _currentPage == index
                        ? Theme.of(context).colorScheme.primary
                        : Theme.of(context).colorScheme.outline,
                    borderRadius: BorderRadius.circular(4.w(context)),
                  ),
                ),
              ),
            ),

            SizedBox(height: 32.h(context)),

            // أزرار التنقل
            Padding(
              padding: EdgeInsets.all(24.w(context)),
              child: Row(
                children: [
                  if (_currentPage > 0)
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () {
                          _pageController.previousPage(
                            duration: const Duration(milliseconds: 300),
                            curve: Curves.easeInOut,
                          );
                        },
                        child: const Text('السابق'),
                      ),
                    ),
                  if (_currentPage > 0) SizedBox(width: 16.w(context)),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () {
                        if (_currentPage == _pages.length - 1) {
                          _completeIntro();
                        } else {
                          _pageController.nextPage(
                            duration: const Duration(milliseconds: 300),
                            curve: Curves.easeInOut,
                          );
                        }
                      },
                      child: Text(
                        _currentPage == _pages.length - 1
                            ? 'ابدأ الآن'
                            : 'التالي',
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPage(IntroPage page) {
    return Padding(
      padding: EdgeInsets.all(32.w(context)),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 150.w(context),
            height: 150.h(context),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.primaryContainer,
              shape: BoxShape.circle,
            ),
            child: Icon(
              page.icon,
              size: 72.sp(context),
              color: Theme.of(context).colorScheme.primary,
            ),
          ),
          SizedBox(height: 48.h(context)),
          Text(
            page.title,
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
            textAlign: TextAlign.center,
          ),
          SizedBox(height: 16.h(context)),
          Text(
            page.description,
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: Theme.of(context).textTheme.bodySmall?.color,
                ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class IntroPage {
  final IconData icon;
  final String title;
  final String description;

  IntroPage({
    required this.icon,
    required this.title,
    required this.description,
  });
}
