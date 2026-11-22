# لياقة (Leyaqa) — Gym & Studio Management SaaS

نظام إدارة جيم واستوديوهات للسوق المصري والخليجي: أعضاء، باقات، اشتراكات، تجميد، مدفوعات جزئية، حضور بالـ QR، ولوحة متابعة للريسبشن. واجهة عربية RTL.

الاسم مؤقت لحد ما يتحدد اسم تجاري نهائي.

## الحالة
نسخة أولى قابلة للتشغيل محليًا. غير منشور على أي سيرفر.

## التقنيات
- Laravel 13 + PHP ^8.3 (الاختبارات بتشتغل على PHP 8.3.17 CLI كبيئة تطوير — مش توصية إصدار إنتاج)
- Inertia + Vue 3 + Tailwind (Breeze scaffolding معدّل لعربي RTL)
- spatie/laravel-permission للأدوار والصلاحيات
- قاعدة بيانات: الاختبارات خضراء على SQLite وعلى MySQL 8.0.40 (الميغريشنز وكل الاختبارات الـ 52).

## التشغيل محليًا
```bash
composer install
npm install
cp .env.example .env   # DB_CONNECTION=sqlite افتراضي
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build          # أو npm run dev
php artisan serve
```

بيانات تجريبية محلية فقط (local env):
```bash
php artisan db:seed --class=DemoSeeder   # demo@example.com / password
```

## الاختبارات
```bash
php artisan test       # 50 اختبار Pest — عزل المستأجرين، دورة الاشتراك، المدفوعات، الحضور، الصلاحيات
```

## الاستضافة
غير محددة بعد. المتطلبات: PHP 8.3+، MySQL 8 أو SQLite، Node للبناء (أو بناء مسبق للأصول).

## الوثائق
- docs/market-study.md — دراسة سوق مصر والخليج بالمصادر
- docs/plan.md — خطة المنتج والمراحل
- docs/database.md — تصميم قاعدة البيانات

### المدفوعات

المدفوعات حالياً سجلات يدوية يدخلها الموظف (كاش أو تحصيل خارجي) وليست معالجة دفع إلكترونية. التكامل مع بوابات الدفع (Paymob، Stripe، HyperPay) مؤجل لمرحلة لاحقة.
