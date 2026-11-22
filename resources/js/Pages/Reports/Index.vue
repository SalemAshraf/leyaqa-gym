<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Kpi from '@/Components/Kpi.vue';
import TrendChart from '@/Components/TrendChart.vue';
import BarList from '@/Components/BarList.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    filters: Object,
    kpis: Object,
    revenueByDay: Array,
    byMethod: Array,
    byPlan: Array,
    latestPayments: Array,
    checkinsByDay: Array,
    checkinsByHour: Array,
    topAttendees: Array,
    subMovement: Object,
    statuses: Array,
    currency: String,
});
const { t, money, num } = useI18n();

const from = ref(props.filters.from);
const to = ref(props.filters.to);

const apply = (f, tt) => router.get(route('reports'), { from: f ?? from.value, to: tt ?? to.value }, { preserveState: true, preserveScroll: true });

const iso = (d) => d.toISOString().slice(0, 10);
const preset = (key) => {
    const today = new Date();
    const end = iso(today);
    let start = end;
    if (key === '7d') start = iso(new Date(today.getTime() - 6 * 86400000));
    if (key === '30d') start = iso(new Date(today.getTime() - 29 * 86400000));
    if (key === 'month') start = iso(new Date(today.getFullYear(), today.getMonth(), 1));
    if (key === 'last_month') {
        const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const last = new Date(today.getFullYear(), today.getMonth(), 0);
        return apply(iso(first), iso(last));
    }
    apply(start, end);
};
const isPreset = (key) => {
    const today = new Date();
    const end = iso(today);
    if (props.filters.to !== end) return false;
    if (key === 'today') return props.filters.from === end;
    if (key === '7d') return props.filters.from === iso(new Date(today.getTime() - 6 * 86400000));
    if (key === '30d') return props.filters.from === iso(new Date(today.getTime() - 29 * 86400000));
    if (key === 'month') return props.filters.from === iso(new Date(today.getFullYear(), today.getMonth(), 1));
    return false;
};

const methodLabel = (m) => ({ cash: t('method_cash'), card: t('method_card'), wallet: t('method_wallet'), bank_transfer: t('method_bank'), other: t('method_other') }[m] ?? m);
const statusesLabeled = props.statuses.map((r) => ({ ...r, label: t('s_' + r.label) }));
</script>

<template>
    <Head :title="t('reports')" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('reports') }}</h2>
                <form @submit.prevent="apply()" class="flex flex-wrap items-center gap-2">
                    <input v-model="from" type="date" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400" :aria-label="t('from')" />
                    <span class="text-sm text-slate-400">{{ t('to') }}</span>
                    <input v-model="to" type="date" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400" :aria-label="t('to')" />
                    <button type="submit" class="btn-primary">{{ t('rep_apply') }}</button>
                </form>
            </div>
            <div class="flex flex-wrap gap-2">
                <button v-for="p in ['today', '7d', '30d', 'month', 'last_month']" :key="p" @click="preset(p)" type="button"
                    class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition"
                    :class="isPreset(p) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300'">
                    {{ t('rep_preset_' + p) }}
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <Kpi :label="t('rep_revenue_range')" :value="money(kpis.revenue, currency)" />
                <Kpi :label="t('rep_payments_count')" :value="num(kpis.payments_count)" />
                <Kpi :label="t('rep_avg_day')" :value="money(kpis.avg_day, currency)" />
                <Kpi :label="t('rep_checkins_allowed')" :value="num(kpis.checkins_allowed)" tone="good" />
                <Kpi :label="t('rep_checkins_denied')" :value="num(kpis.checkins_denied)" :tone="kpis.checkins_denied > 0 ? 'bad' : 'slate'" />
                <Kpi :label="t('rep_unique_members')" :value="num(kpis.unique_members)" />
                <Kpi :label="t('rep_new_members_range')" :value="num(kpis.new_members)" />
                <Kpi :label="t('rep_subs_new')" :value="num(kpis.new_subscriptions)" />
            </div>

            <div class="card">
                <div class="mb-4 flex items-baseline justify-between">
                    <h3 class="font-bold text-ink-900">{{ t('rep_daily_revenue') }}</h3>
                    <span class="text-lg font-bold text-brand-700">{{ money(kpis.revenue, currency) }}</span>
                </div>
                <TrendChart :rows="revenueByDay" value-key="revenue" :format="(v) => money(v, currency)" />
                <div class="mt-4 max-h-72 overflow-y-auto rounded-lg border border-slate-100">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-start font-semibold">{{ t('rep_day') }}</th>
                                <th class="px-4 py-2 text-start font-semibold">{{ t('rep_payments_count') }}</th>
                                <th class="px-4 py-2 text-end font-semibold">{{ t('rep_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="r in [...revenueByDay].reverse()" :key="r.day" :class="{ 'text-slate-400': r.count === 0 }">
                                <td class="px-4 py-2"><bdi dir="ltr">{{ r.day }}</bdi></td>
                                <td class="px-4 py-2">{{ num(r.count) }}</td>
                                <td class="px-4 py-2 text-end font-semibold"><bdi>{{ money(r.revenue, currency) }}</bdi></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_by_method') }}</h3>
                    <BarList v-if="byMethod.length" :rows="byMethod.map((r) => ({ ...r, label: methodLabel(r.label) }))" :format="(v) => money(v, currency)" />
                    <p v-else class="py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                    <ul v-if="byMethod.length" class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        <li v-for="r in byMethod" :key="r.label" class="flex justify-between"><span>{{ methodLabel(r.label) }}</span><span>{{ num(r.count) }} {{ t('rep_payments_unit') }}</span></li>
                    </ul>
                </div>
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_by_plan') }}</h3>
                    <BarList v-if="byPlan.length" :rows="byPlan" :format="(v) => money(v, currency)" />
                    <p v-else class="py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                </div>
            </div>

            <div class="card !p-0 overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('rep_latest_payments') }}</div>
                <p v-if="!latestPayments.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-5 py-2 text-start font-semibold">{{ t('member') }}</th>
                                <th class="px-5 py-2 text-start font-semibold">{{ t('plan') }}</th>
                                <th class="px-5 py-2 text-start font-semibold">{{ t('method') }}</th>
                                <th class="px-5 py-2 text-start font-semibold">{{ t('date') }}</th>
                                <th class="px-5 py-2 text-end font-semibold">{{ t('rep_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in latestPayments" :key="p.id">
                                <td class="px-5 py-2.5">
                                    <Link v-if="p.member_id" :href="route('members.show', p.member_id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ p.member }}</Link>
                                    <span v-else class="font-semibold text-ink-900">{{ p.member }}</span>
                                </td>
                                <td class="px-5 py-2.5 text-slate-600">{{ p.plan }}</td>
                                <td class="px-5 py-2.5 text-slate-600">{{ methodLabel(p.method) }}</td>
                                <td class="px-5 py-2.5 text-slate-500"><bdi dir="ltr">{{ p.paid_at }}</bdi></td>
                                <td class="px-5 py-2.5 text-end font-semibold"><bdi>{{ money(p.amount, currency) }}</bdi></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="mb-4 flex items-baseline justify-between">
                    <h3 class="font-bold text-ink-900">{{ t('rep_checkins_day') }}</h3>
                    <span class="text-sm text-slate-500">{{ t('rep_unique_members') }}: <b class="text-ink-900">{{ num(kpis.unique_members) }}</b></span>
                </div>
                <TrendChart :rows="checkinsByDay" value-key="count" :format="num" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="card lg:col-span-2">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_checkins_hour') }}</h3>
                    <TrendChart :rows="checkinsByHour" value-key="count" :format="num" />
                </div>
                <div class="card !p-0 overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('rep_top_attendees') }}</div>
                    <p v-if="!topAttendees.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="row in topAttendees" :key="row.member_id" class="flex items-center justify-between gap-2 px-5 py-2.5 text-sm">
                            <Link :href="route('members.show', row.member_id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ row.member }}</Link>
                            <span class="shrink-0 text-xs text-slate-500">{{ num(row.visits) }} {{ t('rep_visits') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="grid grid-cols-2 gap-4 lg:col-span-2 lg:grid-cols-2">
                    <Kpi :label="t('rep_subs_new')" :value="num(subMovement.new)" />
                    <Kpi :label="t('rep_subs_expired')" :value="num(subMovement.expired)" :tone="subMovement.expired > 0 ? 'bad' : 'slate'" />
                </div>
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_statuses_now') }}</h3>
                    <BarList :rows="statusesLabeled" :format="num" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
