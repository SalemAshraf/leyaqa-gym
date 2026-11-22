<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pager from '@/Components/Pager.vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ payments: Object, currency: String });
const { t, money, date } = useI18n();

const methodLabel = (m) => ({ cash: t('method_cash'), card: t('method_card'), wallet: t('method_wallet'), bank_transfer: t('method_bank'), other: t('method_other') }[m] ?? m);

function remove(id) {
    if (confirm(t('confirm_delete_payment'))) {
        router.delete(route('payments.destroy', id));
    }
}
</script>

<template>
    <Head :title="t('payments')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-ink-900">{{ t('payments') }}</h2>

            <div class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('paid_at') }}</th>
                            <th class="th">{{ t('member') }}</th>
                            <th class="th">{{ t('amount') }}</th>
                            <th class="th">{{ t('method') }}</th>
                            <th class="th">{{ t('note') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in payments.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="td" :data-label="t('paid_at')"><bdi dir="ltr">{{ p.paid_at }}</bdi></td>
                            <td class="td font-semibold text-ink-900" :data-label="t('member')">{{ p.member }}</td>
                            <td class="td" :data-label="t('amount')"><bdi class="font-semibold text-brand-700">{{ money(p.amount, currency) }}</bdi></td>
                            <td class="td" :data-label="t('method')">{{ methodLabel(p.method) }}</td>
                            <td class="td text-slate-500" :data-label="t('note')">{{ p.note ?? '—' }}</td>
                            <td class="td" data-label="">
                                <button v-if="p.can_delete" @click="remove(p.id)" class="font-semibold text-rose-600 hover:text-rose-500">{{ t('delete') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!payments.data.length"><td colspan="6" data-label="" class="px-4 py-10 text-center text-sm text-slate-400">{{ t('no_payments') }}</td></tr>
                    </tbody>
                </table>
            </div>

            <Pager :links="payments.links" />
        </div>
    </AuthenticatedLayout>
</template>
