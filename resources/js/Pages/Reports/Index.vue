<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Kpi from '@/Components/Kpi.vue';
import TrendChart from '@/Components/TrendChart.vue';
import BarList from '@/Components/BarList.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const props = defineProps({
    kpis: Object, revenueTrend: Array, checkinsPerDay: Array,
    byMethod: Array, newMembers: Array, statuses: Array, currency: String,
});
const { t, money, num } = useI18n();

const methodLabel = (m) => ({ cash: t('method_cash'), card: t('method_card'), wallet: t('method_wallet'), bank_transfer: t('method_bank'), other: t('method_other') }[m] ?? m);
const byMethodLabeled = props.byMethod.map((r) => ({ ...r, label: methodLabel(r.label) }));
const statusesLabeled = props.statuses.map((r) => ({ ...r, label: t('s_' + r.label) }));
</script>

<template>
    <Head :title="t('reports')" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <h2 class="text-xl font-bold text-ink-900">{{ t('reports') }}</h2>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <Kpi :label="t('kpi_revenue')" :value="money(kpis.revenue_month, currency)" />
                <Kpi :label="t('rep_revenue_prev')" :value="money(kpis.revenue_prev_month, currency)" />
                <Kpi :label="t('rep_members_new')" :value="num(kpis.members_new_month)" />
                <Kpi :label="t('rep_checkins_month')" :value="num(kpis.checkins_month)" tone="good" />
            </div>

            <div class="card">
                <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_revenue_year') }}</h3>
                <TrendChart :rows="revenueTrend" value-key="revenue" :format="(v) => money(v, currency)" />
            </div>

            <div class="card">
                <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_checkins_30') }}</h3>
                <TrendChart :rows="checkinsPerDay.map((r) => ({ month: r.day, count: r.count }))" value-key="count" :format="num" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_by_method') }}</h3>
                    <BarList v-if="byMethodLabeled.length" :rows="byMethodLabeled" :format="(v) => money(v, currency)" />
                    <p v-else class="py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                </div>
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_new_members') }}</h3>
                    <BarList :rows="newMembers.map((r) => ({ label: r.month, value: r.count }))" :format="num" />
                </div>
                <div class="card">
                    <h3 class="mb-4 font-bold text-ink-900">{{ t('rep_statuses') }}</h3>
                    <BarList :rows="statusesLabeled" :format="num" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
