<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Field from '@/Components/Field.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';
import { waLink } from '@/lib/wa';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({ member: Object, subscriptions: Array, checkIns: Array, plans: Array, currency: String, branches: Array });
const { t, money, num } = useI18n();

const editing = ref(false);
const editForm = useForm({
    name: props.member.name, phone: props.member.phone, email: props.member.email ?? '',
    gender: props.member.gender ?? '', birthdate: props.member.birthdate ?? '',
    notes: props.member.notes ?? '', status: props.member.status, branch_id: props.member.branch_id ?? '',
});

const paymentForm = useForm({ subscription_id: '', amount: '', method: 'cash', note: '' });
const subForm = useForm({ plan_id: '', starts_at: new Date().toISOString().slice(0, 10), price: '' });
const showSubForm = ref(false);

function submitSubscription() {
    subForm.transform((data) => ({ ...data, member_id: props.member.id, price: data.price || undefined }))
        .post(route('subscriptions.store'), { onSuccess: () => { showSubForm.value = false; subForm.reset(); } });
}
const payFor = ref(null);

function saveEdit() {
    editForm.put(route('members.update', props.member.id), { onSuccess: () => (editing.value = false) });
}

function openPayment(sub) {
    payFor.value = sub.id;
    paymentForm.subscription_id = sub.id;
    paymentForm.amount = sub.balance > 0 ? sub.balance : '';
}

function submitPayment() {
    paymentForm.post(route('payments.store'), { onSuccess: () => { payFor.value = null; paymentForm.reset(); } });
}

function act(action, subId, data = {}) {
    router.post(route(`subscriptions.${action}`, subId), data);
}

const freezeDays = ref({});

const gym = computed(() => usePage().props.gym);
const cardUrl = computed(() => route('card', props.member.qr_token));
const portalCopied = ref(false);
async function copyPortal() {
    await navigator.clipboard.writeText(props.member.portal_url);
    portalCopied.value = true;
    setTimeout(() => (portalCopied.value = false), 2000);
}
const waReminder = computed(() => waLink(
    props.member.phone,
    gym.value?.country,
    t('wa_payment').replace('{name}', props.member.name).replace('{balance}', money(props.member.balance, props.currency))
));
</script>

<template>
    <Head :title="member.name" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ member.name }}</h2>
                <span class="flex flex-wrap items-center gap-2">
                    <a :href="cardUrl" target="_blank" class="btn-secondary btn-sm">{{ t('open_card') }}</a>
                    <button type="button" class="btn-secondary btn-sm" @click="copyPortal">{{ portalCopied ? t('portal_copied') : t('copy_portal') }}</button>
                    <a v-if="member.balance > 0" :href="waReminder" target="_blank" rel="noopener" class="btn-secondary btn-sm !text-[#128C7E]">{{ t('wa_remind') }}</a>
                    <Badge v-if="member.balance > 0" tone="rose" class="!px-3 !py-1.5 !text-sm">
                        {{ t('has_balance') }}: <bdi>{{ money(member.balance, currency) }}</bdi>
                    </Badge>
                </span>
            </div>

            <div class="card">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-ink-900">{{ t('member_data') }}</h3>
                    <button @click="editing = !editing" class="text-sm font-semibold text-brand-700 hover:text-brand-600">{{ t('edit') }}</button>
                </div>
                <dl v-if="!editing" class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div><dt class="text-xs text-slate-400">{{ t('phone') }}</dt><dd class="mt-0.5 font-medium text-ink-900"><bdi dir="ltr">{{ member.phone }}</bdi></dd></div>
                    <div><dt class="text-xs text-slate-400">{{ t('email') }}</dt><dd class="mt-0.5 font-medium text-ink-900"><bdi dir="ltr">{{ member.email ?? '—' }}</bdi></dd></div>
                    <div><dt class="text-xs text-slate-400">{{ t('joined_at') }}</dt><dd class="mt-0.5 font-medium text-ink-900">{{ member.joined_at }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ t('status') }}</dt><dd class="mt-0.5"><Badge :tone="member.status === 'active' ? 'green' : 'slate'">{{ member.status === 'active' ? t('member_active') : t('member_inactive') }}</Badge></dd></div>
                    <div v-if="member.branch"><dt class="text-xs text-slate-400">{{ t('branch') }}</dt><dd class="mt-0.5 text-ink-900">{{ member.branch }}</dd></div>
                    <div v-if="member.notes" class="col-span-full"><dt class="text-xs text-slate-400">{{ t('notes') }}</dt><dd class="mt-0.5 text-ink-900">{{ member.notes }}</dd></div>
                </dl>
                <form v-else @submit.prevent="saveEdit" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <input v-model="editForm.name" class="input" :placeholder="t('member_name')" />
                    <input v-model="editForm.phone" class="input" dir="ltr" :placeholder="t('phone')" />
                    <input v-model="editForm.email" class="input" dir="ltr" :placeholder="t('email')" />
                    <select v-model="editForm.gender" class="input">
                        <option value="">{{ t('gender') }}</option><option value="male">{{ t('male') }}</option><option value="female">{{ t('female') }}</option>
                    </select>
                    <input v-model="editForm.birthdate" type="date" class="input" />
                    <select v-model="editForm.status" class="input">
                        <option value="active">{{ t('member_active') }}</option><option value="inactive">{{ t('member_inactive') }}</option>
                    </select>
                    <input v-model="editForm.notes" class="input sm:col-span-3" :placeholder="t('notes')" />
                    <select v-if="branches?.length" v-model="editForm.branch_id" class="input">
                        <option value="">{{ t('no_branch') }}</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <div class="flex gap-2 sm:col-span-3">
                        <button type="submit" class="btn-primary">{{ t('save') }}</button>
                        <button type="button" @click="editing = false" class="btn-secondary">{{ t('cancel') }}</button>
                    </div>
                </form>
                <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-400">
                    {{ t('qr_token') }}: <bdi dir="ltr">{{ member.qr_token }}</bdi>
                </div>
            </div>

            <div class="card !p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <span class="font-bold text-ink-900">{{ t('subscriptions') }}</span>
                    <button @click="showSubForm = !showSubForm" class="btn-primary btn-sm"><span class="text-base leading-none">+</span>{{ t('new_sub') }}</button>
                </div>
                <form v-if="showSubForm" @submit.prevent="submitSubscription" class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <select v-model="subForm.plan_id" class="input !w-auto text-sm" required>
                        <option value="" disabled>{{ t('choose_plan') }}</option>
                        <option v-for="p in plans" :key="p.id" :value="p.id">
                            {{ p.name }} — {{ p.type === 'duration' ? num(p.duration_days) + ' ' + t('days_unit') : num(p.sessions_count) + ' ' + t('sessions_unit') }} — {{ money(p.price, currency) }}
                        </option>
                    </select>
                    <input v-model="subForm.starts_at" type="date" class="input !w-auto text-sm" required />
                    <input v-model="subForm.price" type="number" step="0.01" min="0" :placeholder="t('custom_price')" class="input !w-40 text-sm" />
                    <button type="submit" :disabled="subForm.processing" class="btn-primary btn-sm">{{ t('save') }}</button>
                </form>
                <p v-if="!subscriptions.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_subs') }}</p>
                <div v-for="s in subscriptions" :key="s.id" class="border-b border-slate-100 px-5 py-4 last:border-0">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-ink-900">{{ s.plan }}</span>
                            <StatusBadge :value="s.status" class="ms-2" />
                            <div class="mt-1 text-sm text-slate-500">
                                {{ s.starts_at }} ← {{ s.ends_at ?? t('no_end_date') }}
                                <span v-if="s.sessions_remaining !== null"> · {{ t('sessions_left').replace('{n}', num(s.sessions_remaining)) }}</span>
                            </div>
                        </div>
                        <div class="text-sm">
                            <bdi class="text-slate-500">{{ money(s.paid, currency) }} / {{ money(s.price, currency) }}</bdi>
                            <bdi v-if="s.balance > 0" class="ms-2 font-semibold text-rose-600">{{ t('remaining') }} {{ money(s.balance, currency) }}</bdi>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-sm">
                        <button v-if="s.balance > 0 && s.status !== 'cancelled'" @click="openPayment(s)" class="btn-secondary btn-sm !text-brand-700">{{ t('record_payment') }}</button>
                        <template v-if="s.status === 'active'">
                            <input v-model.number="freezeDays[s.id]" type="number" min="1" :max="s.freeze_days_allowed - s.freeze_days_used" class="input !w-20 !py-1.5 text-sm" :placeholder="t('days_unit')" />
                            <button @click="act('freeze', s.id, { days: freezeDays[s.id] })" :disabled="!freezeDays[s.id]" class="btn-secondary btn-sm !text-sky-700 disabled:opacity-40">
                                {{ t('freeze_action').replace('{n}', num(s.freeze_days_allowed - s.freeze_days_used)) }}
                            </button>
                            <button @click="act('renew', s.id)" class="btn-secondary btn-sm">{{ t('renew') }}</button>
                            <button @click="act('cancel', s.id)" class="btn-danger btn-sm">{{ t('cancel_sub') }}</button>
                        </template>
                        <button v-if="s.status === 'frozen'" @click="act('unfreeze', s.id)" class="btn-primary btn-sm !bg-sky-600 hover:!bg-sky-700">{{ t('unfreeze') }}</button>
                        <button v-if="s.status === 'expired'" @click="act('renew', s.id)" class="btn-primary btn-sm">{{ t('renew_sub') }}</button>
                    </div>
                    <form v-if="payFor === s.id" @submit.prevent="submitPayment" class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 p-3">
                        <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" class="input !w-32 !py-1.5 text-sm" :placeholder="t('amount')" />
                        <select v-model="paymentForm.method" class="input !w-auto !py-1.5 text-sm">
                            <option value="cash">{{ t('method_cash') }}</option><option value="card">{{ t('method_card') }}</option>
                            <option value="wallet">{{ t('method_wallet') }}</option><option value="bank_transfer">{{ t('method_bank') }}</option><option value="other">{{ t('method_other') }}</option>
                        </select>
                        <input v-model="paymentForm.note" class="input !w-auto !py-1.5 text-sm" :placeholder="t('note')" />
                        <button type="submit" class="btn-primary btn-sm">{{ t('save_payment') }}</button>
                    </form>
                </div>
            </div>

            <div class="card !p-0 overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3 font-bold text-ink-900">{{ t('recent_checkins') }}</div>
                <p v-if="!checkIns.length" class="px-5 py-6 text-sm text-slate-400">{{ t('no_data') }}</p>
                <ul v-else class="divide-y divide-slate-100 text-sm">
                    <li v-for="(c, i) in checkIns" :key="i" class="flex items-center justify-between px-5 py-3">
                        <bdi dir="ltr" class="text-slate-500">{{ c.checked_in_at }}</bdi>
                        <Badge :tone="c.result === 'allowed' ? 'green' : 'rose'">{{ c.result === 'allowed' ? t('entered') : t('denied') }}</Badge>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
