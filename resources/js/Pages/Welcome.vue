<script setup>
import Icon from '@/Components/Icon.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ canLogin: Boolean, canRegister: Boolean });
const authed = computed(() => !!usePage().props.auth.user);
const lang = ref('ar');

const copy = {
    ar: {
        dir: 'rtl', other: 'EN',
        badge: 'نظام إدارة الجيم والاستوديو',
        title: 'جيمك كله في مكان واحد',
        sub: 'أعضاء واشتراكات، حضور ببطاقة QR، مدفوعات ومستحقات، وتقارير يومية، من أي موبايل أو كمبيوتر.',
        cta_start: 'ابدأ مجانًا', cta_login: 'تسجيل الدخول', cta_dash: 'فتح لوحة التحكم',
        features: [
            ['users', 'الأعضاء', 'ملف كامل لكل عضو: بياناته، اشتراكاته، رصيده، وسجل حضوره.'],
            ['qr', 'حضور QR فوري', 'العضو يمسح بطاقته عند الباب، والنظام يسمح أو يرفض مع السبب في ثانية.'],
            ['card', 'مدفوعات ومستحقات', 'كاش، بطاقة، أو محفظة. كل جنيه متسجل، والمستحقات ظاهرة لحظيًا.'],
            ['chart', 'تقارير ذكية', 'إيراد الشهر، اتجاه الحضور بالساعة، والاشتراكات اللي قربت تخلص.'],
        ],
        built: 'مبني للجيمهات والاستوديوهات في مصر والخليج',
    },
    en: {
        dir: 'ltr', other: 'عربي',
        badge: 'Gym & studio management',
        title: 'Run your whole gym from one place',
        sub: 'Members and subscriptions, QR door check-ins, payments and balances, and daily reports. On any phone or computer.',
        cta_start: 'Start free', cta_login: 'Log in', cta_dash: 'Open dashboard',
        features: [
            ['users', 'Members', 'A full profile per member: details, subscriptions, balance, and check-in history.'],
            ['qr', 'Instant QR check-in', 'Members scan at the door; the system allows or denies with the reason, in a second.'],
            ['card', 'Payments & balances', 'Cash, card, or wallet. Every pound recorded, balances live.'],
            ['chart', 'Smart reports', 'Monthly revenue, hourly attendance, and subscriptions about to expire.'],
        ],
        built: 'Built for gyms and studios in Egypt and the Gulf',
    },
};
const c = computed(() => copy[lang.value]);
</script>

<template>
    <Head :title="c.badge" />
    <div :dir="c.dir" class="min-h-screen bg-canvas text-ink-900">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-2.5">
                <img src="/icon.svg" class="h-9 w-9 rounded-xl" alt="" />
                <span class="text-sm font-bold text-slate-500">{{ c.badge }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="lang = lang === 'ar' ? 'en' : 'ar'" class="btn-secondary btn-sm">{{ c.other }}</button>
                <Link v-if="authed" :href="route('dashboard')" class="btn-primary btn-sm">{{ c.cta_dash }}</Link>
                <template v-else>
                    <Link :href="route('login')" class="btn-secondary btn-sm">{{ c.cta_login }}</Link>
                    <Link v-if="canRegister" :href="route('register')" class="btn-primary btn-sm">{{ c.cta_start }}</Link>
                </template>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6">
            <section class="py-14 text-center sm:py-20">
                <div class="mx-auto inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-1.5 text-sm font-semibold text-brand-700">
                    <Icon name="chart" class="h-4 w-4" />
                    {{ c.built }}
                </div>
                <h1 class="mt-6 text-4xl font-black leading-tight sm:text-5xl">{{ c.title }}</h1>
                <p class="mx-auto mt-4 max-w-xl text-lg text-slate-500">{{ c.sub }}</p>
                <div class="mt-8 flex justify-center gap-3">
                    <Link v-if="!authed && canRegister" :href="route('register')" class="btn-primary !px-8 !py-3 text-base">{{ c.cta_start }}</Link>
                    <Link v-if="authed" :href="route('dashboard')" class="btn-primary !px-8 !py-3 text-base">{{ c.cta_dash }}</Link>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 pb-20 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="[icon, title, body] in c.features" :key="title" class="card text-start">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <Icon :name="icon" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-4 font-bold">{{ title }}</h3>
                    <p class="mt-1.5 text-sm leading-6 text-slate-500">{{ body }}</p>
                </div>
            </section>
        </main>
    </div>
</template>
