<?php
/**
 * ملف إعدادات البيئة - نموذج
 * Environment Configuration Template
 * 
 * ⚠️ تحذير: انسخ هذا الملف إلى env.php وأضف بياناتك الحقيقية
 * WARNING: Copy this file to env.php and add your real credentials
 * 
 * ❌ لا ترفع ملف env.php إلى Git أبداً!
 * ❌ NEVER commit env.php to Git!
 */

// إعدادات قاعدة البيانات
putenv('DB_HOST=localhost');
putenv('DB_NAME=cyberctf');
putenv('DB_USER=root');
putenv('DB_PASS=');

// إعدادات SMTP للبريد الإلكتروني
putenv('SMTP_HOST=smtp.gmail.com');
putenv('SMTP_PORT=587');
putenv('SMTP_USERNAME=your-email@gmail.com');
putenv('SMTP_PASSWORD=your-app-password');
putenv('SMTP_FROM_EMAIL=your-email@gmail.com');
putenv('SMTP_FROM_NAME=AlwaniCTF');

// مفاتيح الأمان
putenv('APP_SECRET_KEY=' . bin2hex(random_bytes(32)));
