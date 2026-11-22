<script setup>
import Badge from '@/Components/Badge.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    member: Object,
    gym: Object,
    subscription: Object,
    balance: Number,
    upcoming: Array,
    days: Array,
    card_url: String,
});

const page = usePage();
const flash = computed(() => page.props.flash?.success);
const errors = computed(() => page.props.errors || {});

const ar = computed(() => (props.gym.locale || 'ar') === 'ar');
const manifestUrl = computed(() => route('portal.manifest'));
// Stored coach names often already include the title (كابتن أحمد) - don't double it.
const coachName = (n) => (n || '').replace(/^(الكابتن|كابتن|captain|coach)\s+/i, '');
const dir = computed(() => (ar.value ? 'rtl' : 'ltr'));

const L = computed(() => (ar.value
    ? {
        title: 'حساب العضو', sub: 'اشتراكك وحجوزاتك في مكان واحد',
        plan: 'الباقة', days: 'باقي {n} يوم', sessions: 'باقي {n} حصة', ends: 'ينتهي في {d}', noSub: 'لا يوجد اشتراك حالي',
        balance: 'المتبقي عليك', noBalance: 'لا مستحقات عليك',
        myCard: 'بطاقة الدخول',
        upcoming: 'حجوزاتك الجاية', noUpcoming: 'لا حجوزات قادمة',
        schedule: 'حصص الـ ١٤ يوم الجايين', book: 'احجز', full: 'مكتملة — احجز انتظار', waitlisted: 'على الانتظار', booked: 'محجوز', cancel: 'إلغاء',
        s: { active: 'ساري', expired: 'منتهي', frozen: 'مجمّد', cancelled: 'ملغي', upcoming: 'لم يبدأ' },
        weekdays: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
        coach: 'الكابتن {n}', minutes: '{n} دقيقة',
      }
    : {
        title: 'Member account', sub: 'Your subscription and bookings in one place',
        plan: 'Plan', days: '{n} days left', sessions: '{n} sessions left', ends: 'Ends {d}', noSub: 'No current subscription',
        balance: 'You owe', noBalance: 'Nothing due',
        myCard: 'Entry card',
        upcoming: 'Your upcoming bookings', noUpcoming: 'No upcoming bookings',
        schedule: 'Classes for the next 14 days', book: 'Book', full: 'Full — join waitlist', waitlisted: 'Waitlisted', booked: 'Booked', cancel: 'Cancel',
        s: { active: 'Active', expired: 'Expired', frozen: 'Frozen', cancelled: 'Cancelled', upcoming: 'Not started' },
        weekdays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        coach: 'Coach {n}', minutes: '{n} min',
      }));

const tones = { active: 'green', expired: 'slate', frozen: 'blue', cancelled: 'rose', upcoming: 'amber' };
const bookingTones = { booked: 'green', waitlisted: 'amber' };

const money = (n) => new Intl.NumberFormat(ar.value ? 'ar-EG' : 'en-US', { maximumFractionDigits: 2 }).format(n) + ' ' + (props.gym.currency || '');

function book(cls, date) {
    router.post(route('portal.bookings.store'), { class_id: cls.id, class_date: date }, { preserveScroll: true });
}
function cancelBooking(id) {
    router.delete(route('portal.bookings.destroy', { bookingId: id }), { preserveScroll: true });
}
</script>

<template>
    <Head :title="L.title">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" :content="gym.name">
        <link rel="manifest" :href="manifestUrl">
        <link rel="apple-touch-icon" href="/icon-192.png">
    </Head>
    <div :dir="dir" class="min-h-screen bg-slate-50 pb-16">
        <div class="bg-ink-900 px-5 pb-8 pt-6 text-white">
            <div class="mx-auto max-w-lg">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-400">{{ gym.name }}</p>
                    <a :href="card_url" class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">{{ L.myCard }}</a>
                </div>
                <h1 class="mt-3 text-2xl font-bold !text-white">{{ member.name }}</h1>
                <p class="mt-1 text-sm text-slate-400">{{ L.sub }}</p>
            </div>
        </div>

        <div class="mx-auto -mt-4 max-w-lg space-y-4 px-4">
            <p v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ flash }}</p>
            <p v-if="errors.member_id" class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ errors.member_id }}</p>

            <div class="card">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ L.plan }}</span>
                    <Badge v-if="subscription" :tone="tones[subscription.status]">{{ L.s[subscription.status] }}</Badge>
                </div>
                <template v-if="subscription">
                    <p class="mt-1 text-xl font-bold text-ink-900">{{ subscription.plan }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        <span v-if="subscription.days_left !== null">{{ L.days.replace('{n}', subscription.days_left) }}</span>
                        <span v-else-if="subscription.sessions_remaining !== null">{{ L.sessions.replace('{n}', subscription.sessions_remaining) }}</span>
                        <span v-if="subscription.ends_at"> · {{ L.ends.replace('{d}', subscription.ends_at) }}</span>
                    </p>
                </template>
                <p v-else class="mt-1 text-sm font-semibold text-rose-600">{{ L.noSub }}</p>
                <div class="mt-3 border-t border-slate-100 pt-3 text-sm">
                    <span v-if="balance > 0" class="font-semibold text-rose-600">{{ L.balance }}: {{ money(balance) }}</span>
                    <span v-else class="text-slate-400">{{ L.noBalance }}</span>
                </div>
            </div>

            <div class="card">
                <h2 class="font-bold text-ink-900">{{ L.upcoming }}</h2>
                <p v-if="!upcoming.length" class="mt-2 text-sm text-slate-400">{{ L.noUpcoming }}</p>
                <ul v-else class="mt-2 divide-y divide-slate-100">
                    <li v-for="b in upcoming" :key="b.id" class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-900">{{ b.class }}</p>
                            <p class="text-xs text-slate-400">{{ b.date }} · {{ b.start_time }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :tone="bookingTones[b.status]">{{ b.status === 'booked' ? L.booked : L.waitlisted }}</Badge>
                            <button class="text-xs font-semibold text-rose-500" @click="cancelBooking(b.id)">{{ L.cancel }}</button>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="card !p-0 overflow-hidden">
                <h2 class="border-b border-slate-100 px-5 py-4 font-bold text-ink-900">{{ L.schedule }}</h2>
                <div v-for="day in days" :key="day.date" class="border-b border-slate-100 last:border-0">
                    <p class="bg-slate-50 px-5 py-2 text-xs font-bold text-slate-500">{{ L.weekdays[day.weekday] }} · {{ day.date }}</p>
                    <div v-for="cls in day.classes" :key="cls.id" class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-900">{{ cls.name }}</p>
                            <p class="text-xs text-slate-400">
                                {{ cls.start_time }} · {{ L.minutes.replace('{n}', cls.duration_minutes) }}<span v-if="cls.coach"> · {{ L.coach.replace('{n}', coachName(cls.coach)) }}</span>
                            </p>
                        </div>
                        <div class="shrink-0">
                            <Badge v-if="cls.booking_status" :tone="bookingTones[cls.booking_status]">
                                {{ cls.booking_status === 'booked' ? L.booked : L.waitlisted }}
                            </Badge>
                            <button v-else class="btn-primary btn-sm" @click="book(cls, day.date)">
                                {{ cls.full ? L.full : L.book }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
