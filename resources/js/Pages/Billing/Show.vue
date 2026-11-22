<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';
import { waLink } from '@/lib/wa';

const props = defineProps({ billing: Object, plans: Array, invoices: Array });
const { t, locale, num } = useI18n();
const gym = computed(() => usePage().props.gym);

const tones = { trialing: 'brand', active: 'green', past_due: 'amber', suspended: 'rose' };
const invTones = { pending: 'amber', paid: 'green', void: 'slate' };

const salesMsg = computed(() => t('sales_msg').replace('{gym}', gym.value?.name || ''));
const salesUrl = computed(() => props.billing.sales_whatsapp ? waLink(props.billing.sales_whatsapp, null, salesMsg.value) : null);

const price = (p) => p.price_cents == null ? t('contact_sales') : `${num(p.price_cents / 100)} ${p.currency}`;

function subscribe(plan) {
    router.post(route('billing.subscribe'), { plan_id: plan.id }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('billing')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-ink-900">{{ t('billing') }}</h2>

            <div class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 font-bold text-ink-900">
                        {{ t('platform_status') }}: <Badge :tone="tones[billing.status]">{{ t('pstatus_' + billing.status) }}</Badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        <template v-if="billing.status === 'pending'">{{ t('pending_note') }}</template>
                        <template v-else-if="billing.status === 'trialing'">{{ t('trial_left').replace('{d}', num(billing.trial_days_left)).replace('{date}', billing.trial_ends_at) }}</template>
                        <template v-else-if="billing.renews_at">{{ t('renews_at').replace('{date}', billing.renews_at) }}<template v-if="billing.plan"> · {{ locale === 'ar' ? billing.plan.name_ar : billing.plan.name_en }}</template></template>
                    </p>
                </div>
                <a v-if="salesUrl" :href="salesUrl" target="_blank" rel="noopener" class="btn-secondary btn-sm">{{ t('contact_sales_wa') }}</a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="p in plans" :key="p.id" class="card flex flex-col">
                    <div class="text-lg font-bold text-ink-900">{{ locale === 'ar' ? p.name_ar : p.name_en }}</div>
                    <div class="mt-1 text-2xl font-extrabold text-brand-700">{{ price(p) }} <span class="text-sm font-medium text-slate-400">/ {{ t('interval_' + p.interval) }}</span></div>
                    <button @click="subscribe(p)" class="btn-primary mt-4">{{ t('request_plan') }}</button>
                </div>
            </div>
            <p class="text-xs text-slate-400">{{ t('billing_note') }}</p>

            <div v-if="invoices.length" class="card !p-0 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>#</th><th>{{ t('plan') }}</th><th>{{ t('amount') }}</th><th>{{ t('period') }}</th><th>{{ t('status') }}</th></tr></thead>
                    <tbody>
                        <tr v-for="i in invoices" :key="i.id">
                            <td class="text-slate-400">{{ i.id }}</td>
                            <td>{{ locale === 'ar' ? i.plan_ar : i.plan_en }}</td>
                            <td>{{ i.amount_cents == null ? '—' : num(i.amount_cents / 100) + ' ' + i.currency }}</td>
                            <td class="text-slate-500" dir="ltr">{{ i.period }}</td>
                            <td><Badge :tone="invTones[i.status]">{{ t('inv_' + i.status) }}</Badge></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
