import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const dict = {
    ar: {
        app: 'قوّة', dashboard: 'الرئيسية', members: 'الأعضاء', plans: 'الباقات', subscriptions: 'الاشتراكات',
        payments: 'المدفوعات', checkins: 'تسجيل الحضور', staff: 'الفريق', settings: 'الإعدادات', profile: 'حسابي', logout: 'خروج',
        g_daily: 'الشغل اليومي', g_money: 'الاشتراكات والفلوس', g_admin: 'الإدارة',
        language: 'English', save: 'حفظ', cancel: 'إلغاء', delete: 'حذف', edit: 'تعديل', add: 'إضافة', search: 'بحث',
        confirm_delete: 'متأكد من الحذف؟', back: 'رجوع', empty: 'مفيش بيانات لسه', saved: 'اتحفظ',
        nav_collapse: 'اطوِ القائمة', nav_expand: 'وسّع القائمة', search_ph: 'ابحث بالاسم أو الموبايل…',
        r_owner: 'المالك', r_reception: 'ريسبشن',
        // dashboard
        kpi_members: 'الأعضاء النشطون', kpi_subs: 'اشتراكات سارية', kpi_in_today: 'حضور اليوم', kpi_denied: 'مرفوض اليوم',
        kpi_revenue: 'إيراد هذا الشهر', kpi_outstanding: 'مستحقات لم تُدفع', revenue_trend: 'الإيراد آخر ٦ شهور',
        attendance_today: 'حضور اليوم بالساعة', plan_mix: 'الاشتراكات السارية حسب الباقة',
        expiring_soon: 'اشتراكات تنتهي خلال ٧ أيام', top_debtors: 'أعضاء عليهم مستحقات', recent_checkins: 'آخر تسجيلات الحضور',
        ends_today: 'ينتهي اليوم', days_left: 'باقي {n} يوم', no_data: 'مفيش بيانات لسه', open_member: 'افتح العضو',
        // members
        new_member: 'عضو جديد', member_name: 'الاسم', phone: 'رقم الموبايل', email: 'البريد الإلكتروني', gender: 'النوع',
        male: 'ذكر', female: 'أنثى', birthdate: 'تاريخ الميلاد', notes: 'ملاحظات', joined_at: 'انضم',
        current_sub: 'الاشتراك الحالي', status: 'الحالة', balance: 'مستحقات', no_members: 'مفيش أعضاء بعد',
        member_active: 'نشط', member_inactive: 'غير نشط', member_data: 'البيانات', qr_link: 'رمز بطاقة العضو (QR)',
        sessions_left: 'باقي {n} حصة', no_sub: 'لا يوجد',
        // subscription statuses
        s_active: 'ساري', s_expired: 'منتهي', s_frozen: 'مجمّد', s_cancelled: 'ملغي', s_upcoming: 'لم يبدأ',
        // member show / subscriptions
        new_subscription: 'اشتراك جديد', pick_plan: 'اختار الباقة', starts_at: 'تاريخ البداية', custom_price: 'سعر خاص (اختياري)',
        no_subs: 'لا يوجد اشتراكات', until: 'إلى', no_end: 'بدون تاريخ انتهاء', record_payment: 'تسجيل دفعة',
        amount: 'المبلغ', method: 'طريقة الدفع', m_cash: 'كاش', m_card: 'بطاقة', m_wallet: 'محفظة', m_bank_transfer: 'تحويل', m_other: 'أخرى',
        note: 'ملاحظة', save_payment: 'حفظ الدفعة', freeze: 'تجميد', freeze_days: 'أيام', freeze_available: 'متاح {n}',
        unfreeze: 'إلغاء التجميد', renew: 'تجديد', renew_sub: 'تجديد الاشتراك', cancel_sub: 'إلغاء',
        paid_of: '{paid} / {price}', remaining: 'متبقي', last_checkins: 'آخر الحضور', entered: 'دخل', denied: 'اترفض',
        // plans
        new_plan: 'باقة جديدة', plan_name: 'اسم الباقة', plan_type: 'النوع', type_duration: 'بمدة', type_sessions: 'بعدد حصص',
        duration_days: 'المدة (أيام)', sessions_count: 'عدد الحصص', price: 'السعر', freeze_days_allowed: 'أيام تجميد مسموحة',
        plan_active: 'مفعّلة', plan_inactive: 'موقوفة', activate: 'تفعيل', deactivate: 'إيقاف', no_plans: 'مفيش باقات بعد',
        subs_count: 'اشتراك', days_unit: 'يوم', sessions_unit: 'حصة',
        // subscriptions page
        member: 'العضو', plan: 'الباقة', period: 'الفترة', filter_status: 'كل الحالات', no_subscriptions: 'مفيش اشتراكات مطابقة',
        // payments
        date: 'التاريخ', delete_payment: 'حذف الدفعة', owner_only: 'للمالك فقط', no_payments: 'مفيش مدفوعات مسجلة',
        total_page: 'إجمالي الصفحة',
        // check-ins
        checkin_hint: 'امسح QR العضو أو اكتب رقمه/موبايله', checkin_input: 'رمز QR أو رقم العضو أو موبايله', checkin_btn: 'تسجيل',
        today_log: 'سجل اليوم', allowed: 'مسموح', result_denied: 'مرفوض', debt_warning: 'عليه مستحقات', checkin_time: 'الوقت',
        // staff
        new_staff: 'موظف جديد', staff_name: 'الاسم', password: 'كلمة المرور', role: 'الصلاحية', role_reception: 'ريسبشن',
        staff_hint: 'الريسبشن يقدر يدير الأعضاء والاشتراكات والمدفوعات والحضور، من غير الباقات والإعدادات.',
        delete_staff: 'حذف الموظف', no_staff: 'مفيش موظفين غيرك', cant_delete_self: 'مينفعش تحذف نفسك',
        // settings
        gym_settings: 'إعدادات الجيم', gym_name: 'اسم الجيم', country: 'الدولة', currency: 'العملة', timezone: 'المنطقة الزمنية',
        settings_saved: 'تم حفظ الإعدادات',
        // auth
        login: 'تسجيل الدخول', login_sub: 'ادخل على حساب الجيم', remember: 'فاكرني', forgot: 'نسيت كلمة المرور؟',
        login_btn: 'دخول', no_account: 'لسه ماعندكش جيم؟', create_account: 'سجّل جيمك',
        register: 'تسجيل جيم جديد', register_sub: 'هتبقى مالك حساب جيم جديد، وبياناته منفصلة عن أي جيم تاني.',
        your_name: 'اسمك', confirm_password: 'تأكيد كلمة المرور', register_btn: 'إنشاء الحساب', have_account: 'عندك حساب؟',
        register_note: 'التسجيل بينشئ جيم جديد فقط. لو بتشتغل في جيم موجود، اطلب من المالك يضيفك من صفحة الفريق.',
        au_side_h: 'كل شغل الجيم في مكان واحد', au_side_p: 'أعضاء واشتراكات وتجميد ودفعات جزئية وحضور بمسح QR — من الريسبشن لحد التقارير.',
        forgot_t: 'نسيت كلمة المرور', forgot_s: 'اكتب إيميلك وهنبعتلك رابط تعيين كلمة مرور جديدة (لو إرسال البريد متظبط على السيرفر).',
        forgot_btn: 'ابعت الرابط', back_login: 'ارجع لتسجيل الدخول',
        reset_t: 'كلمة مرور جديدة', reset_s: 'اختار كلمة مرور جديدة لحسابك.', reset_btn: 'احفظ كلمة المرور',
        confirm_t: 'تأكيد كلمة المرور', confirm_s: 'منطقة محمية. أكّد كلمة مرورك قبل المتابعة.', confirm_btn: 'تأكيد',
        verify_t: 'تأكيد البريد', verify_s: 'اضغط على رابط التأكيد اللي اتبعت على إيميلك. لو موصلش، اطلب رابط جديد.',
        verify_sent: 'اتبعت رابط تأكيد جديد.', verify_btn: 'إعادة إرسال رابط التأكيد',
        // profile
        profile_info: 'بيانات الحساب', profile_info_hint: 'عدّل اسمك وبريدك.', change_password: 'تغيير كلمة المرور',
        change_password_hint: 'اختار كلمة مرور طويلة وصعبة التخمين.', current_password: 'كلمة المرور الحالية', new_password: 'كلمة المرور الجديدة',
        delete_account: 'حذف الحساب', delete_account_hint: 'حذف الحساب نهائي ومش بيتراجع.', confirm_delete_account: 'تحذف حسابك نهائيًا؟',
        email_unverified: 'بريدك الإلكتروني لسه مش متأكد.', resend_verification: 'إعادة إرسال رابط التأكيد',
        // validation / flash
        payment_deleted: 'اتحذفت الدفعة', member_saved: 'اتحفظ العضو', sub_created: 'اتعمل الاشتراك',
        open_card: 'بطاقة العضو', wa_remind: 'تذكير واتساب',
        wa_payment: 'أهلًا {name}، تذكير من الجيم: عندك مستحقات بقيمة {balance}. متاح الدفع عند الريسبشن. شكرًا!',
        wa_expiry: 'أهلًا {name}، اشتراكك في الجيم بينتهي {when}. جدد قبل الانتهاء عشان تفضل داخلك مفتوح. شكرًا!',
                reports: 'التقارير', rep_revenue_prev: 'إيراد الشهر الماضي', rep_members_new: 'أعضاء جدد هذا الشهر', rep_checkins_month: 'حضور هذا الشهر',
        rep_revenue_year: 'الإيراد آخر ١٢ شهر', rep_checkins_30: 'الحضور آخر ٣٠ يوم', rep_by_method: 'مدفوعات الشهر حسب الطريقة',
        rep_new_members: 'أعضاء جدد حسب الشهر', rep_statuses: 'الاشتراكات حسب الحالة',
        // increment-1 additions
        choose_plan: 'اختار الباقة', close: 'إغلاق', confirm: 'تأكيد',
        confirm_delete_payment: 'حذف الدفعة؟ لا يمكن التراجع.', confirm_delete_staff: 'حذف هذا الموظف؟',
        confirm_intro: 'منطقة محمية. أكّد كلمة مرورك قبل المتابعة.', create_gym: 'سجّل جيمك',
        delete_confirm_title: 'تحذف حسابك نهائيًا؟', delete_confirm_body: 'كل بياناتك هتتحذف نهائي. اكتب كلمة مرورك للتأكيد.',
        forgot_intro: 'اكتب إيميلك وهنبعتلك رابط تعيين كلمة مرور جديدة.', forgot_password: 'نسيت كلمة المرور؟',
        freeze_action: 'تجميد (متاح {n})', from: 'من', has_balance: 'عليه مستحقات',
        method_cash: 'كاش', method_card: 'بطاقة', method_wallet: 'محفظة', method_bank: 'تحويل', method_other: 'أخرى',
        new_sub: 'اشتراك جديد', no_checkins: 'لا يوجد حضور اليوم بعد', no_end_date: 'بدون تاريخ انتهاء',
        paid_at: 'التاريخ', password_confirm: 'تأكيد كلمة المرور', password_temp: 'كلمة سر مؤقتة',
        qr_token: 'رمز بطاقة العضو (QR)', record_checkin: 'تسجيل دخول', remember_me: 'فاكرني',
        resend_verify: 'إعادة إرسال رابط التأكيد', reset_password: 'كلمة مرور جديدة', role_owner: 'مالك', role_staff: 'ريسبشن',
        scan_hint: 'امسح QR أو اكتب رقم العضو', send_reset: 'ابعت الرابط', subs_hint: 'إنشاء اشتراك جديد يتم من صفحة العضو.',
        to: 'إلى', todays_checkins: 'حضور اليوم ({n})', update_password: 'تغيير كلمة المرور',
        update_password_hint: 'اختار كلمة مرور طويلة وصعبة التخمين.', verify_email: 'تأكيد البريد',
        verify_intro: 'اضغط على رابط التأكيد اللي اتبعت على إيميلك. لو موصلش، اطلب رابط جديد.',
        welcome_member: 'أهلًا {name}', you: 'أنت',
        reason_expired: 'الاشتراك منتهي', reason_frozen: 'الاشتراك مجمّد', reason_cancelled: 'الاشتراك ملغي',
        reason_no_sessions: 'الحصص خلصت', reason_no_sub: 'لا يوجد اشتراك', reason_upcoming: 'الاشتراك لم يبدأ',
    },
    en: {
        app: 'Quwa', dashboard: 'Dashboard', members: 'Members', plans: 'Plans', subscriptions: 'Subscriptions',
        payments: 'Payments', checkins: 'Check-in', staff: 'Team', settings: 'Settings', profile: 'My account', logout: 'Log out',
        g_daily: 'Daily work', g_money: 'Subscriptions & money', g_admin: 'Administration',
        language: 'عربي', save: 'Save', cancel: 'Cancel', delete: 'Delete', edit: 'Edit', add: 'Add', search: 'Search',
        confirm_delete: 'Sure you want to delete?', back: 'Back', empty: 'No data yet', saved: 'Saved',
        nav_collapse: 'Collapse menu', nav_expand: 'Expand menu', search_ph: 'Search name or phone…',
        r_owner: 'Owner', r_reception: 'Reception',
        kpi_members: 'Active members', kpi_subs: 'Active subscriptions', kpi_in_today: 'Check-ins today', kpi_denied: 'Denied today',
        kpi_revenue: 'Revenue this month', kpi_outstanding: 'Outstanding balances', revenue_trend: 'Revenue, last 6 months',
        attendance_today: "Today's check-ins by hour", plan_mix: 'Active subscriptions by plan',
        expiring_soon: 'Expiring within 7 days', top_debtors: 'Members with balances', recent_checkins: 'Latest check-ins',
        ends_today: 'Ends today', days_left: '{n} days left', no_data: 'No data yet', open_member: 'Open member',
        new_member: 'New member', member_name: 'Name', phone: 'Mobile number', email: 'Email', gender: 'Gender',
        male: 'Male', female: 'Female', birthdate: 'Birthdate', notes: 'Notes', joined_at: 'Joined',
        current_sub: 'Current subscription', status: 'Status', balance: 'Balance', no_members: 'No members yet',
        member_active: 'Active', member_inactive: 'Inactive', member_data: 'Details', qr_link: 'Member card code (QR)',
        sessions_left: '{n} sessions left', no_sub: 'None',
        s_active: 'Active', s_expired: 'Expired', s_frozen: 'Frozen', s_cancelled: 'Cancelled', s_upcoming: 'Not started',
        new_subscription: 'New subscription', pick_plan: 'Choose a plan', starts_at: 'Start date', custom_price: 'Custom price (optional)',
        no_subs: 'No subscriptions', until: 'to', no_end: 'No end date', record_payment: 'Record payment',
        amount: 'Amount', method: 'Method', m_cash: 'Cash', m_card: 'Card', m_wallet: 'Wallet', m_bank_transfer: 'Bank transfer', m_other: 'Other',
        note: 'Note', save_payment: 'Save payment', freeze: 'Freeze', freeze_days: 'Days', freeze_available: '{n} left',
        unfreeze: 'Unfreeze', renew: 'Renew', renew_sub: 'Renew subscription', cancel_sub: 'Cancel',
        paid_of: '{paid} / {price}', remaining: 'left', last_checkins: 'Recent check-ins', entered: 'Allowed', denied: 'Denied',
        new_plan: 'New plan', plan_name: 'Plan name', plan_type: 'Type', type_duration: 'By duration', type_sessions: 'By sessions',
        duration_days: 'Duration (days)', sessions_count: 'Sessions', price: 'Price', freeze_days_allowed: 'Freeze days allowed',
        plan_active: 'Active', plan_inactive: 'Inactive', activate: 'Activate', deactivate: 'Deactivate', no_plans: 'No plans yet',
        subs_count: 'subscriptions', days_unit: 'days', sessions_unit: 'sessions',
        member: 'Member', plan: 'Plan', period: 'Period', filter_status: 'All statuses', no_subscriptions: 'No matching subscriptions',
        date: 'Date', delete_payment: 'Delete payment', owner_only: 'Owner only', no_payments: 'No payments recorded',
        total_page: 'Page total',
        checkin_hint: "Scan the member's QR or type their number/phone", checkin_input: 'QR code, member number or phone', checkin_btn: 'Check in',
        today_log: "Today's log", allowed: 'Allowed', result_denied: 'Denied', debt_warning: 'Has balance', checkin_time: 'Time',
        new_staff: 'New staff member', staff_name: 'Name', password: 'Password', role: 'Role', role_reception: 'Reception',
        staff_hint: 'Reception can manage members, subscriptions, payments and check-ins, but not plans or settings.',
        delete_staff: 'Delete staff member', no_staff: 'No staff besides you', cant_delete_self: 'You cannot delete yourself',
        gym_settings: 'Gym settings', gym_name: 'Gym name', country: 'Country', currency: 'Currency', timezone: 'Timezone',
        settings_saved: 'Settings saved',
        login: 'Log in', login_sub: 'Sign in to your gym account', remember: 'Remember me', forgot: 'Forgot your password?',
        login_btn: 'Log in', no_account: 'No gym yet?', create_account: 'Register your gym',
        register: 'Register a new gym', register_sub: 'You become the owner of a new gym. Its data is separate from every other gym.',
        your_name: 'Your name', confirm_password: 'Confirm password', register_btn: 'Create account', have_account: 'Have an account?',
        register_note: 'Registering only creates a new gym. To join an existing one, ask its owner to add you from the Team page.',
        au_side_h: 'All your gym work in one place', au_side_p: 'Members, subscriptions, freezes, partial payments and QR check-in, from the front desk to reports.',
        forgot_t: 'Forgot your password', forgot_s: 'Enter your email and we will send a reset link (if mail sending is configured on the server).',
        forgot_btn: 'Send the link', back_login: 'Back to log in',
        reset_t: 'New password', reset_s: 'Choose a new password for your account.', reset_btn: 'Save password',
        confirm_t: 'Confirm password', confirm_s: 'This is a secure area. Confirm your password before continuing.', confirm_btn: 'Confirm',
        verify_t: 'Verify your email', verify_s: 'Click the confirmation link sent to your email. If it did not arrive, request a new one.',
        verify_sent: 'A new confirmation link was sent.', verify_btn: 'Resend confirmation link',
        profile_info: 'Account details', profile_info_hint: 'Update your name and email address.', change_password: 'Change password',
        change_password_hint: 'Use a long password that is hard to guess.', current_password: 'Current password', new_password: 'New password',
        delete_account: 'Delete account', delete_account_hint: 'Deleting your account is permanent and cannot be undone.', confirm_delete_account: 'Delete your account permanently?',
        email_unverified: 'Your email address is not verified.', resend_verification: 'Resend verification email',
        payment_deleted: 'Payment deleted', member_saved: 'Member saved', sub_created: 'Subscription created',
        open_card: 'Member card', wa_remind: 'WhatsApp reminder',
        wa_payment: 'Hi {name}, a reminder from the gym: you have an outstanding balance of {balance}. You can pay at the front desk. Thank you!',
        wa_expiry: 'Hi {name}, your gym subscription ends {when}. Renew before it ends so your access stays open. Thank you!',
                reports: 'Reports', rep_revenue_prev: 'Revenue last month', rep_members_new: 'New members this month', rep_checkins_month: 'Check-ins this month',
        rep_revenue_year: 'Revenue, last 12 months', rep_checkins_30: 'Check-ins, last 30 days', rep_by_method: "This month's payments by method",
        rep_new_members: 'New members by month', rep_statuses: 'Subscriptions by status',
        // increment-1 additions
        choose_plan: 'Choose a plan', close: 'Close', confirm: 'Confirm',
        confirm_delete_payment: 'Delete this payment? This cannot be undone.', confirm_delete_staff: 'Delete this staff member?',
        confirm_intro: 'This is a secure area. Confirm your password before continuing.', create_gym: 'Register your gym',
        delete_confirm_title: 'Delete your account permanently?', delete_confirm_body: 'All your data will be permanently deleted. Enter your password to confirm.',
        forgot_intro: 'Enter your email and we will send you a password reset link.', forgot_password: 'Forgot your password?',
        freeze_action: 'Freeze ({n} left)', from: 'From', has_balance: 'Has balance',
        method_cash: 'Cash', method_card: 'Card', method_wallet: 'Wallet', method_bank: 'Bank transfer', method_other: 'Other',
        new_sub: 'New subscription', no_checkins: 'No check-ins yet today', no_end_date: 'No end date',
        paid_at: 'Date', password_confirm: 'Confirm password', password_temp: 'Temporary password',
        qr_token: 'Member card code (QR)', record_checkin: 'Record check-in', remember_me: 'Remember me',
        resend_verify: 'Resend verification link', reset_password: 'New password', role_owner: 'Owner', role_staff: 'Reception',
        scan_hint: 'Scan a QR code or type the member number', send_reset: 'Send the link', subs_hint: 'New subscriptions are created from the member page.',
        to: 'To', todays_checkins: "Today's check-ins ({n})", update_password: 'Change password',
        update_password_hint: 'Use a long, hard-to-guess password.', verify_email: 'Verify your email',
        verify_intro: 'Click the verification link we sent to your email. If it did not arrive, request a new one.',
        welcome_member: 'Welcome, {name}', you: 'you',
        reason_expired: 'Subscription expired', reason_frozen: 'Subscription frozen', reason_cancelled: 'Subscription cancelled',
        reason_no_sessions: 'No sessions left', reason_no_sub: 'No subscription', reason_upcoming: 'Subscription has not started',
    },
};

export function useI18n() {
    const page = usePage();
    const locale = computed(() => page.props.locale || 'ar');
    const t = (key) => dict[locale.value]?.[key] ?? dict.en[key] ?? key;
    const money = (n, cur) => {
        const c = cur || page.props.gym?.currency || 'EGP';
        const ar = { EGP: 'ج.م', SAR: 'ر.س', AED: 'د.إ', KWD: 'د.ك', QAR: 'ر.ق', BHD: 'د.ب', OMR: 'ر.ع', USD: '$' };
        return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-EG' : 'en-US', { maximumFractionDigits: 2 }).format(Number(n || 0)) + ' ' + (locale.value === 'ar' ? ar[c] || c : c);
    };
    const num = (n) => new Intl.NumberFormat(locale.value === 'ar' ? 'ar-EG' : 'en-US').format(Number(n || 0));
    const date = (d, withTime = false) => {
        if (!d) return '—';
        const opts = withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' };
        return new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar-EG' : 'en-GB', opts).format(new Date(d));
    };
    return { t, locale, money, num, date };
}
