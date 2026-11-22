<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Kpi from '@/Components/Kpi.vue';
import TrendChart from '@/Components/TrendChart.vue';
import BarList from '@/Components/BarList.vue';
import Badge from '@/Components/Badge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';
import { waLink } from '@/lib/wa';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    expiringSoon: Array,
    debtors: Array,
    revenueTrend: Array,
    checkinsByHour: Array,
    planMix: Array,
    recentCheckIns: Array,
    currency: String,
    setup: Object,
});

const { t, money, num, date } = useI18n();
const gymCountry = usePage().props.gym?.country;
const expiryWa = (row) => waLink(row.phone, gymCountry, t('wa_expiry')
    .replace('{name}', row.member)
    .replace('{when}', row.days_left === 0 ? t('ends_today').toLowerCase() : t('days_left').replace('{n}', row.days_left)));
</script>

<template>
    <Head :title="t('dashboard')" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div v-if="setup" class="card">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-bold text-ink-900">{{ t('setup_title') }}</h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold text-slate-400">{{ num(setup.done) }} / {{ num(setup.total) }}</span>
                        <button @click="router.post(route('setup.dismiss'), {}, { preserveScroll: true })" class="text-xs font-semibold text-slate-400 hover:text-slate-600">{{ t('setup_dismiss') }}</button>
                    </div>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-brand-500 transition-all" :style="{ width: (setup.done / setup.total * 100) + '%' }"></div>
                </div>
                <ul class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="step in setup.steps" :key="step.key">
                        <Link :href="route(step.route)" class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="step.done ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-ink-900 hover:border-brand-200'">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full text-[11px]"
                                :class="step.done ? 'bg-emerald-500 text-white' : 'border border-slate-300 text-slate-300'">✓</span>
                            {{ t('setup_' + step.key) }}
                        </Link>
                    </li>
                </ul>
            </div>
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <Kpi :label="t('kpi_members')" :value="num(stats.members)" />
                <Kpi :label="t('kpi_subs')" :value="num(stats.active_subscriptions)" />
                <Kpi :label="t('kpi_in_today')" :value="num(stats.checkins_today)" tone="good" />
                <Kpi :label="t('kpi_denied')" :value="num(stats.denied_today)" :tone="stats.denied_today > 0 ? 'bad' : 'slate'" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="card lg:col-span-2">
                    <div class="mb-4 flex items-baseline justify-between">
                        <h3 class="font-bold text-ink-900">{{ t('revenue_trend') }}</h3>
                        <span class="text-lg font-bold text-brand-700">{{ money(stats.revenue_month, currency) }}</span>
                    </div>
                    <TrendChart :rows="revenueTrend" value-key="revenue" :format="(v) => money(v, currency)" />
                </div>
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('plan_mix') }}</h3>
                    <BarList v-if="planMix.length" :rows="planMix" :format="num" />
                    <p v-else class="py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                    <div class="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-500">
                        {{ t('kpi_outstanding') }}: <bdi class="font-bold text-rose-600">{{ money(stats.outstanding_total, currency) }}</bdi>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="mb-4 font-bold text-ink-900">{{ t('attendance_today') }}</h3>
                <TrendChart :rows="checkinsByHour" value-key="count" :format="num" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card !p-0 overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('expiring_soon') }}</div>
                    <p v-if="!expiringSoon.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="row in expiringSoon" :key="row.id" class="flex items-center justify-between gap-2 px-5 py-3 text-sm">
                            <div class="min-w-0">
                                <Link :href="route('members.show', row.member_id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ row.member }}</Link>
                                <span class="truncate text-slate-500"> · {{ row.plan }}</span>
                            </div>
                            <span class="flex shrink-0 items-center gap-2">
                                <a :href="expiryWa(row)" target="_blank" rel="noopener" class="text-xs font-semibold text-[#128C7E] hover:underline">{{ t('wa_remind') }}</a>
                                <Badge tone="amber">{{ row.days_left === 0 ? t('ends_today') : t('days_left').replace('{n}', num(row.days_left)) }}</Badge>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="card !p-0 overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('top_debtors') }}</div>
                    <p v-if="!debtors.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="row in debtors" :key="row.member_id" class="flex items-center justify-between gap-2 px-5 py-3 text-sm">
                            <Link :href="route('members.show', row.member_id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ row.name }}</Link>
                            <Badge tone="rose">{{ money(row.balance, currency) }}</Badge>
                        </li>
                    </ul>
                </div>

                <div class="card !p-0 overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('recent_checkins') }}</div>
                    <p v-if="!recentCheckIns.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="(c, i) in recentCheckIns" :key="i" class="flex items-center justify-between gap-2 px-5 py-3 text-sm">
                            <Link :href="route('members.show', c.member_id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ c.member }}</Link>
                            <span class="flex items-center gap-2">
                                <bdi class="text-xs text-slate-400" dir="ltr">{{ c.checked_in_at }}</bdi>
                                <Badge :tone="c.result === 'allowed' ? 'green' : 'rose'">{{ c.result === 'allowed' ? t('entered') : t('denied') }}</Badge>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
