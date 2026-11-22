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
    <div class="min-h-screen bg-[#070D1A] text-white" :dir="isAr ? 'rtl' : 'ltr'">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-[#070D1A]/85 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <img src="/img/brand/gamed-lockup-dark.svg" :alt="t('brand')" class="h-9" />
                </div>
                <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-300 md:flex">
                    <a href="#features" class="hover:text-white">{{ t('nav_features') }}</a>
                    <a href="#pricing" class="hover:text-white">{{ t('nav_pricing') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    <Link :href="route('login')" class="btn-sm inline-flex items-center justify-center rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">{{ t('login') }}</Link>
                    <Link :href="route('register')" class="btn-sm inline-flex items-center justify-center rounded-lg bg-brand-400 px-4 py-2 text-sm font-bold text-[#0B1220] shadow-[0_0_20px_rgba(52,211,153,0.35)] transition hover:bg-brand-300">{{ trialDays ? t('start_trial') : t('start_now') }}</Link>
                </div>
            </div>
        </header>

        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute -top-40 start-1/4 h-[480px] w-[480px] rounded-full bg-brand-500/20 blur-[140px]"></div>
            <div class="pointer-events-none absolute -bottom-52 end-0 h-[420px] w-[420px] rounded-full bg-brand-400/10 blur-[120px]"></div>
            <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-20 pt-16 lg:grid-cols-2 lg:pb-28 lg:pt-24">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-400/30 bg-brand-400/10 px-3 py-1 text-xs font-bold text-brand-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-400"></span>{{ t('hero_badge') }}
                    </span>
                    <h1 class="mt-7 text-4xl font-black leading-[1.35] tracking-tight text-white lg:mt-8 lg:text-6xl lg:leading-[1.25]">
                        {{ t('hero_title_a') }}
                        <span class="bg-gradient-to-l from-brand-300 to-brand-500 bg-clip-text text-transparent">{{ t('hero_title_b') }}</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-[1.9] text-slate-300 lg:mt-7">{{ t('hero_sub') }}</p>
                    <div class="mt-9 flex flex-wrap items-center gap-4 lg:mt-10">
                        <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-400 px-7 py-3.5 text-base font-extrabold text-[#0B1220] shadow-[0_8px_30px_rgba(52,211,153,0.4)] transition hover:-translate-y-0.5 hover:bg-brand-300">{{ trialDays ? t('start_trial_days').replace('{d}', num(trialDays)) : t('start_register') }}</Link>
                        <a v-if="salesUrl" :href="salesUrl" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-7 py-3.5 text-base font-bold text-white transition hover:bg-white/10">{{ t('contact_sales_wa') }}</a>
                    </div>
                    <p v-if="trialDays" class="mt-4 text-xs text-slate-400">{{ t('trial_note') }}</p>
                    <div class="mt-11 flex flex-wrap gap-2.5 lg:mt-12">
                        <span v-for="chip in ['qr_checkin', 'classes_wl', 'bilingual', 'wa_reminders']" :key="chip" class="rounded-full border border-white/10 bg-white/5 px-3.5 py-2 text-xs font-semibold text-slate-300">{{ t('feat_' + chip) }}</span>
                    </div>
                </div>
                <div class="relative">
                    <div class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gradient-to-tl from-brand-500/25 via-transparent to-brand-300/10 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-2xl border border-white/15 shadow-[0_30px_80px_rgba(0,0,0,0.6)] ring-1 ring-white/10">
                        <img src="/img/landing-dashboard.png" :alt="t('mock_dashboard')" class="block w-full" loading="lazy" />
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="bg-canvas py-20 text-ink-900">
            <div class="mx-auto max-w-6xl px-4">
                <h2 class="text-center text-3xl font-black lg:text-4xl">{{ t('features_title') }}</h2>
                <p class="mx-auto mt-3 max-w-xl text-center text-slate-500">{{ t('features_sub') }}</p>
                <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="f in features" :key="f.k" class="card transition hover:-translate-y-1 hover:border-brand-300 hover:shadow-lg">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-md"><Icon :name="f.icon" :size="22" /></div>
                        <h3 class="mt-3 font-extrabold">{{ t('feat_' + f.k) }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ t('feat_' + f.k + '_d') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#070D1A] py-20">
            <div class="pointer-events-none absolute -start-32 top-0 h-[400px] w-[400px] rounded-full bg-brand-500/15 blur-[130px]"></div>
            <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-400/30 bg-brand-400/10 px-3 py-1 text-xs font-bold text-brand-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-400"></span>{{ t('la_badge') }}
                    </span>
                    <h2 class="mt-6 text-3xl font-black leading-snug text-white lg:text-4xl">{{ t('la_title') }}</h2>
                    <p class="mt-4 max-w-lg leading-[1.9] text-slate-300">{{ t('la_sub') }}</p>
                    <ul class="mt-8 space-y-3">
                        <li v-for="b in ['la_b1', 'la_b2', 'la_b3', 'la_b4']" :key="b" class="flex items-start gap-3 text-sm text-slate-200">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-400/20 text-brand-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            </span>
                            {{ t(b) }}
                        </li>
                    </ul>
                </div>
                <div class="relative">
                    <div class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-gradient-to-tl from-brand-500/20 via-transparent to-brand-300/10 blur-2xl"></div>
                    <div class="relative space-y-4 rounded-2xl border border-white/15 bg-white/5 p-5 shadow-[0_30px_80px_rgba(0,0,0,0.6)] ring-1 ring-white/10 backdrop-blur">
                        <div v-for="qa in [['la_q1', 'la_a1'], ['la_q2', 'la_a2'], ['la_q3', 'la_a3']]" :key="qa[0]" class="space-y-2">
                            <div class="ms-auto w-fit max-w-[85%] rounded-2xl rounded-tr-sm bg-brand-400 px-4 py-2.5 text-sm font-bold text-[#0B1220] rtl:rounded-tr-2xl rtl:rounded-tl-sm">{{ t(qa[0]) }}</div>
                            <div class="w-fit max-w-[85%] rounded-2xl rounded-tl-sm border border-white/10 bg-white/10 px-4 py-2.5 text-sm text-slate-100 rtl:rounded-tl-2xl rtl:rounded-tr-sm">{{ t(qa[1]) }}</div>
                        </div>
                        <p class="pt-1 text-center text-[11px] text-slate-400">{{ t('la_demo') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#0B1220] py-20">
            <div class="mx-auto max-w-6xl px-4">
                <h2 class="text-center text-3xl font-black lg:text-4xl">{{ t('how_title') }}</h2>
                <div class="mt-12 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div v-for="n in 3" :key="n" class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center transition hover:border-brand-400/40">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-300 to-brand-500 text-xl font-black text-[#0B1220] shadow-[0_0_24px_rgba(52,211,153,0.35)]">{{ num(n) }}</div>
                        <h3 class="mt-4 font-extrabold text-white">{{ t('how_' + n) }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">{{ t('how_' + n + '_d') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="bg-canvas py-20 text-ink-900">
            <div class="mx-auto max-w-4xl px-4">
                <h2 class="text-center text-3xl font-black lg:text-4xl">{{ t('pricing_title') }}</h2>
                <p class="mx-auto mt-3 max-w-xl text-center text-slate-500">{{ trialDays ? t('pricing_sub').replace('{d}', num(trialDays)) : t('pricing_sub_plain') }}</p>
                <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="p in platformPlans" :key="p.name_en" class="card flex flex-col items-center border-2 border-transparent text-center transition hover:border-brand-400 hover:shadow-lg">
                        <div class="text-lg font-extrabold">{{ isAr ? p.name_ar : p.name_en }}</div>
                        <div class="mt-2 text-4xl font-black text-brand-600">{{ price(p) }}</div>
                        <div class="text-sm text-slate-400">/ {{ t('interval_' + p.interval) }}</div>
                        <Link :href="route('register')" class="btn-primary mt-6 w-full">{{ trialDays ? t('start_trial') : t('start_now') }}</Link>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-[#070D1A] py-20">
            <div class="pointer-events-none absolute start-1/2 top-0 h-72 w-[640px] -translate-x-1/2 rounded-full bg-brand-500/15 blur-[120px]"></div>
            <div class="relative mx-auto max-w-3xl px-4 text-center">
                <img src="/img/brand/gamed-icon.svg" :alt="t('brand')" class="mx-auto h-16 w-16 rounded-2xl shadow-[0_0_40px_rgba(52,211,153,0.35)]" />
                <h2 class="mt-6 text-3xl font-black leading-snug lg:text-4xl">{{ t('cta_title') }}</h2>
                <p class="mx-auto mt-3 max-w-lg text-slate-300">{{ t('cta_sub') }}</p>
                <Link :href="route('register')" class="mt-8 inline-flex items-center justify-center rounded-xl bg-brand-400 px-8 py-3.5 text-base font-extrabold text-[#0B1220] shadow-[0_8px_30px_rgba(52,211,153,0.4)] transition hover:-translate-y-0.5 hover:bg-brand-300">{{ trialDays ? t('start_trial') : t('start_now') }}</Link>
            </div>
        </section>

        <footer class="border-t border-white/10 bg-[#070D1A] py-8 text-center text-sm text-slate-400">
            {{ t('brand') }} · {{ t('footer_note') }}
        </footer>
    </div>
</template>
