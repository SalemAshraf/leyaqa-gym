<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import { waLink } from '@/lib/wa';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ canRegister: Boolean, trialDays: Number, salesWhatsapp: String, platformPlans: Array });
const { t, locale, num } = useI18n();
const isAr = computed(() => locale.value === 'ar');

const features = [
    { icon: 'members', k: 'members' },
    { icon: 'qr', k: 'qr_checkin' },
    { icon: 'classes', k: 'classes_wl' },
    { icon: 'subscriptions', k: 'subs_freeze' },
    { icon: 'payments', k: 'payments' },
    { icon: 'leads', k: 'leads' },
    { icon: 'reports', k: 'reports' },
    { icon: 'globe', k: 'bilingual' },
    { icon: 'card', k: 'wa_reminders' },
];

const salesUrl = computed(() => props.salesWhatsapp ? waLink(props.salesWhatsapp, null, t('sales_msg_landing')) : null);
const price = (p) => p.price_cents == null ? t('contact_sales') : `${num(p.price_cents / 100)} ${p.currency}`;
</script>

<template>
    <Head :title="t('landing_title')" />
    <div class="min-h-screen bg-canvas text-ink-900" :dir="isAr ? 'rtl' : 'ltr'">
        <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <img src="/img/brand/gamed-lockup-light.svg" :alt="t('brand')" class="h-9" />
                </div>
                <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-500 md:flex">
                    <a href="#features" class="hover:text-ink-900">{{ t('nav_features') }}</a>
                    <a href="#pricing" class="hover:text-ink-900">{{ t('nav_pricing') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    <Link :href="route('login')" class="btn-secondary btn-sm">{{ t('login') }}</Link>
                    <Link :href="route('register')" class="btn-primary btn-sm">{{ trialDays ? t('start_trial') : t('start_now') }}</Link>
                </div>
            </div>
        </header>

        <section class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:py-24">
            <div>
                <h1 class="text-4xl font-extrabold leading-tight lg:text-5xl">{{ t('hero_title') }}</h1>
                <p class="mt-4 max-w-lg text-lg leading-relaxed text-slate-500">{{ t('hero_sub') }}</p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Link :href="route('register')" class="btn-primary !px-6 !py-3 text-base">{{ trialDays ? t('start_trial_days').replace('{d}', num(trialDays)) : t('start_register') }}</Link>
                    <a v-if="salesUrl" :href="salesUrl" target="_blank" rel="noopener" class="btn-secondary !px-6 !py-3 text-base">{{ t('contact_sales_wa') }}</a>
                </div>
                <p v-if="trialDays" class="mt-3 text-xs text-slate-400">{{ t('trial_note') }}</p>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-xl">
                <img src="/img/landing-dashboard.png" :alt="t('mock_dashboard')" class="block w-full" loading="lazy" />
            </div>
        </section>

        <section id="features" class="border-y border-slate-200 bg-white py-16">
            <div class="mx-auto max-w-6xl px-4">
                <h2 class="text-center text-3xl font-extrabold">{{ t('features_title') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-center text-slate-500">{{ t('features_sub') }}</p>
                <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="f in features" :key="f.k" class="card transition hover:shadow-md">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Icon :name="f.icon" :size="22" /></div>
                        <h3 class="mt-3 font-bold">{{ t('feat_' + f.k) }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ t('feat_' + f.k + '_d') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-16">
            <h2 class="text-center text-3xl font-extrabold">{{ t('how_title') }}</h2>
            <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div v-for="n in 3" :key="n" class="card text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-ink-900 font-bold text-brand-400">{{ num(n) }}</div>
                    <h3 class="mt-3 font-bold">{{ t('how_' + n) }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ t('how_' + n + '_d') }}</p>
                </div>
            </div>
        </section>

        <section id="pricing" class="border-t border-slate-200 bg-white py-16">
            <div class="mx-auto max-w-4xl px-4">
                <h2 class="text-center text-3xl font-extrabold">{{ t('pricing_title') }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-center text-slate-500">{{ trialDays ? t('pricing_sub').replace('{d}', num(trialDays)) : t('pricing_sub_plain') }}</p>
                <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="p in platformPlans" :key="p.name_en" class="card flex flex-col items-center text-center">
                        <div class="text-lg font-bold">{{ isAr ? p.name_ar : p.name_en }}</div>
                        <div class="mt-2 text-3xl font-extrabold text-brand-700">{{ price(p) }}</div>
                        <div class="text-sm text-slate-400">/ {{ t('interval_' + p.interval) }}</div>
                        <Link :href="route('register')" class="btn-primary mt-6 w-full">{{ trialDays ? t('start_trial') : t('start_now') }}</Link>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 py-8 text-center text-sm text-slate-400">
            {{ t('brand') }} · {{ t('footer_note') }}
        </footer>
    </div>
</template>
