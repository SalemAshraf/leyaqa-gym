<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pager from '@/Components/Pager.vue';
import Badge from '@/Components/Badge.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ invoices: Object, status: String });
const { t, money } = useI18n();
const currency = usePage().props.gym?.currency;

const tones = { due: 'amber', collected: 'green', cancelled: 'slate' };
const statusLabel = (s) => t('ri_' + s);
const methods = ['cash', 'card', 'wallet', 'bank_transfer', 'other'];
const methodLabel = (m) => t('m_' + m);

const openId = ref(null);
const collectForm = ref({ amount: 0, method: 'cash' });
function openCollect(i) {
    openId.value = i.id;
    collectForm.value = { amount: i.amount, method: 'cash' };
}
function collect(id) {
    router.post(route('renewal-invoices.collect', id), collectForm.value, { preserveScroll: true });
    openId.value = null;
}
function cancel(id) {
    router.post(route('renewal-invoices.cancel', id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('renewal_invoices')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('renewal_invoices') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <button v-for="s in ['', 'due', 'collected', 'cancelled']" :key="s" @click="filter(s)"
                        class="btn-sm" :class="status === s || (!status && !s) ? 'btn-primary' : 'btn-secondary'">
                        {{ s ? statusLabel(s) : t('ri_all') }}
                    </button>
                </div>
            </div>

            <div v-if="!invoices.data.length" class="card text-sm text-slate-500">{{ t('ri_empty') }}</div>

            <div v-else class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('member') }}</th>
                            <th class="th">{{ t('plan') }}</th>
                            <th class="th">{{ t('amount') }}</th>
                            <th class="th">{{ t('due_date') }}</th>
                            <th class="th">{{ t('status') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="i in invoices.data" :key="i.id" class="hover:bg-slate-50">
                            <td class="td font-semibold text-ink-900" :data-label="t('member')">{{ i.member }}</td>
                            <td class="td" :data-label="t('plan')">{{ i.plan }}</td>
                            <td class="td" :data-label="t('amount')"><bdi class="font-semibold text-brand-700">{{ money(i.amount, currency) }}</bdi></td>
                            <td class="td" :data-label="t('due_date')"><bdi dir="ltr">{{ i.due_date }}</bdi></td>
                            <td class="td" :data-label="t('status')"><Badge :tone="tones[i.status] || 'slate'">{{ statusLabel(i.status) }}</Badge></td>
                            <td class="td">
                                <span v-if="i.status === 'due' && openId !== i.id" class="flex flex-wrap gap-2">
                                    <button @click="openCollect(i)" class="btn-primary btn-sm">{{ t('ri_collect') }}</button>
                                    <button @click="cancel(i.id)" class="btn-secondary btn-sm">{{ t('ri_cancel') }}</button>
                                </span>
                                <span v-else-if="i.status === 'due'" class="flex flex-wrap items-center gap-2">
                                    <input v-model.number="collectForm.amount" type="number" min="0.01" step="0.01" class="input w-24" dir="ltr">
                                    <select v-model="collectForm.method" class="input w-28">
                                        <option v-for="m in methods" :key="m" :value="m">{{ methodLabel(m) }}</option>
                                    </select>
                                    <button @click="collect(i.id)" class="btn-primary btn-sm">{{ t('save') }}</button>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pager :links="invoices.links" />
        </div>
    </AuthenticatedLayout>
</template>
