<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ gym: Object, invoices: Array });
const { t, num } = useI18n();
const tones = { trialing: 'brand', active: 'green', past_due: 'amber', suspended: 'rose' };
const invTones = { pending: 'amber', paid: 'green', void: 'slate' };
const days = ref(14);
const amounts = ref({});

const markPaid = (i) => router.post(route('platform.invoices.paid', i.id), { amount_cents: i.amount_cents == null ? Math.round((amounts.value[i.id] || 0) * 100) : undefined }, { preserveScroll: true });
const extend = () => router.post(route('platform.gyms.extend-trial', props.gym.id), { days: days.value }, { preserveScroll: true });
const setStatus = (status) => router.post(route('platform.gyms.status', props.gym.id), { status }, { preserveScroll: true });
</script>

<template>
    <Head :title="gym.name" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-ink-900">{{ gym.name }}</h2>
                    <div class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                        <Badge :tone="tones[gym.status]">{{ t('pstatus_' + gym.status) }}</Badge>
                        <span v-if="gym.plan_ar">{{ gym.plan_ar }}</span>
                        <span v-if="gym.trial_ends_at">{{ t('trial_ends') }}: {{ gym.trial_ends_at }}</span>
                        <span v-if="gym.renews_at">{{ t('renews') }}: {{ gym.renews_at }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input v-model.number="days" type="number" min="1" max="365" class="input !w-20 !py-2 text-sm" />
                    <button @click="extend" class="btn-secondary btn-sm">{{ t('extend_trial') }}</button>
                    <button @click="setStatus('active')" class="btn-secondary btn-sm">{{ t('activate') }}</button>
                    <button @click="setStatus('past_due')" class="btn-secondary btn-sm">{{ t('mark_past_due') }}</button>
                    <button @click="setStatus('suspended')" class="btn-secondary btn-sm !text-rose-600">{{ t('suspend') }}</button>
                </div>
            </div>

            <div class="card !p-0 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>#</th><th>{{ t('plan') }}</th><th>{{ t('amount') }}</th><th>{{ t('period') }}</th><th>{{ t('status') }}</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="i in invoices" :key="i.id">
                            <td class="text-slate-400">{{ i.id }}</td>
                            <td>{{ i.plan_ar || '—' }}</td>
                            <td>{{ i.amount_cents == null ? '—' : num(i.amount_cents / 100) + ' ' + i.currency }}</td>
                            <td class="text-slate-500" dir="ltr">{{ i.period }}</td>
                            <td><Badge :tone="invTones[i.status]">{{ t('inv_' + i.status) }}</Badge></td>
                            <td>
                            <div v-if="i.status === 'pending'" class="flex items-center justify-end gap-2">
                                <input v-if="i.amount_cents == null" v-model.number="amounts[i.id]" type="number" min="0" step="0.01" :placeholder="t('collected_amount')" class="input !w-28 !py-1.5 text-sm" dir="ltr" />
                                <button @click="markPaid(i)" class="text-sm font-semibold text-emerald-700 hover:text-emerald-600">{{ t('confirm_paid') }}</button>
                            </div>
                        </td>
                        </tr>
                        <tr v-if="!invoices.length"><td colspan="6" class="text-center text-sm text-slate-400">{{ t('no_invoices') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
