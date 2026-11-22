# تصميم قاعدة البيانات

قاعدة واحدة متعددة المستأجرين: كل جدول مرتبط بجيم عليه `gym_id`، وعزل تلقائي عبر global scope.

## الجداول المنفذة

### gyms
id, name, phone, country (2 حروف), currency (3 حروف), timezone, locale, timestamps
العملة والمنطقة الزمنية بتتحدد من الدولة عند التسجيل.

### users
id, gym_id (nullable FK), name, email, phone, password, timestamps
الأدوار عبر spatie/laravel-permission: owner و staff. الصلاحيات:
members.manage, plans.manage, subscriptions.manage, payments.manage, checkins.manage, reports.view, gym.manage, staff.manage
(الموظف ياخد كل حاجة ما عدا plans/gym/staff)

### members
id, gym_id, name, phone, email?, gender?, birthdate?, notes?, status (active/inactive), joined_at, qr_token (uuid unique)
qr_token هو اللي بيتمسح عند الباب.

### plans
id, gym_id, name, type (duration|sessions), duration_days?, sessions_count?, price(10,2), freeze_days_allowed, is_active

### subscriptions
id, gym_id, member_id, plan_id (restrict delete), starts_at, ends_at?, status (active|frozen|expired|cancelled), sessions_remaining?, freeze_days_used, price(10,2) — لقطة سعر وقت الاشتراك, notes?
الحالة الفعلية بتتحسب: ends_at فات = expired حتى لو العمود active (مع أمر مجدول subscriptions:expire يومي للتنضيف).

### freezes
id, gym_id, subscription_id, starts_on, ends_on, days, created_by
التجميد بيزوّد ends_at بعدد الأيام ويستهلك freeze_days_used. إلغاء التجميد المبكر يرجّع الفرق.

### payments
id, gym_id, subscription_id, amount(10,2), method (cash|card|wallet|bank_transfer|other), paid_at, note?, created_by
رصيد الاشتراك = price - مجموع الدفعات. رصيد العضو = مجموع أرصدة اشتراكاته.

### check_ins
id, gym_id, member_id, checked_in_at, method (manual|qr), result (allowed|denied), denial_reason?
كل محاولة دخول بتتسجل، مقبولة أو مرفوضة.

## مرحلة الحصص الجماعية (مخطط، غير منفذ)
- class_templates: id, gym_id, name, coach_name, capacity, duration_minutes, is_active
- class_sessions: id, gym_id, class_template_id, starts_at, ends_at, status (scheduled|cancelled)
- class_bookings: id, gym_id, class_session_id, member_id, status (booked|attended|no_show|cancelled)
الحجز يتحقق من اشتراك ساري ومن السعة.

## قرارات تصميم واعية
- price منسوخ في الاشتراك: تغيير سعر الباقة لا يغيّر اشتراكات قديمة.
- ends_at شامل يوم البداية: 30 يوم من 1 ديسمبر تنتهي 30 ديسمبر.
- restrictOnDelete على plan_id: باقة عليها اشتراكات لا تُحذف.
- المديونية لا تمنع الدخول (قرار تجاري شائع محليًا: الرفض عند الباب بيخسر العميل)، لكنها تظهر للريسبشن.
