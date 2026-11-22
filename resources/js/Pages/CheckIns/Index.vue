<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ today: Array });
const page = usePage();
const { t, num, money, date } = useI18n();
const result = computed(() => page.props.flash?.checkin);
const currency = computed(() => page.props.gym?.currency);

const form = useForm({ qr_token: '', member_id: '' });
const input = ref('');

const reasonLabel = (r) => ({
    expired: t('reason_expired'), frozen: t('reason_frozen'), cancelled: t('reason_cancelled'),
    no_sessions_left: t('reason_no_sessions'), no_subscription: t('reason_no_sub'), upcoming: t('reason_upcoming'),
}[r] ?? r);

function submit() {
    const value = input.value.trim();
    if (!value) return;
    if (/^\d+$/.test(value)) {
        form.member_id = value; form.qr_token = '';
    } else {
        form.qr_token = value; form.member_id = '';
    }
    form.post(route('checkins.store'), {
        preserveScroll: true,
        onSuccess: () => { input.value = ''; },
        onError: () => { input.value = ''; },
    });
}
</script>

<template>
    <Head :title="t('checkins')" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <form @submit.prevent="submit" class="card">
                <label class="label">{{ t('scan_hint') }}</label>
                <input
                    v-model="input" autofocus dir="ltr"
                    class="input mt-2 text-center text-2xl tracking-widest"
                    placeholder="QR / #"
                    @keyup.enter="submit"
                />
                <button type="submit" class="btn-primary mt-4 w-full !py-3 text-lg">{{ t('record_checkin') }}</button>
            </form>

            <div v-if="result" class="rounded-xl p-6 text-center shadow-sm" :class="result.allowed ? 'bg-brand-600 text-white' : 'bg-rose-600 text-white'">
                <div class="text-2xl font-bold">{{ result.allowed ? t('welcome_member').replace('{name}', result.member) : t('denied') }}</div>
                <div v-if="!result.allowed" class="mt-1">{{ reasonLabel(result.reason) }}</div>
                <div v-if="result.allowed && result.balance > 0" class="mt-1 text-sm">
                    {{ t('has_balance') }}: <bdi>{{ money(result.balance, currency) }}</bdi>
                </div>
                <div v-if="result.allowed && result.days_left !== null && result.days_left <= 7" class="mt-1 text-sm">
                    {{ t('days_left').replace('{n}', num(result.days_left)) }}
                </div>
                <div v-if="result.allowed && result.sessions_remaining !== null" class="mt-1 text-sm">
                    {{ t('sessions_left').replace('{n}', num(result.sessions_remaining)) }}
                </div>
            </div>

            <div class="card !p-0 overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('todays_checkins').replace('{n}', num(today.length)) }}</div>
                <p v-if="!today.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_checkins') }}</p>
                <ul v-else class="divide-y divide-slate-100">
                    <li v-for="c in today" :key="c.id" class="flex items-center justify-between gap-2 px-5 py-3 text-sm">
                        <span class="font-semibold text-ink-900">{{ c.member }}</span>
                        <span class="flex items-center gap-2">
                            <bdi class="text-xs text-slate-400" dir="ltr">{{ c.checked_in_at.split(' ')[1] }}</bdi>
                            <Badge :tone="c.result === 'allowed' ? 'green' : 'rose'">{{ c.result === 'allowed' ? t('entered') : t('denied') }}</Badge>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
