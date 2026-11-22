<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Field from '@/Components/Field.vue';
import Pager from '@/Components/Pager.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ members: Object, filters: Object, currency: String });
const { t, money, num } = useI18n();

const search = ref(props.filters?.search ?? '');
const showForm = ref(false);
const form = useForm({ name: '', phone: '', email: '', gender: '', birthdate: '', notes: '' });

function doSearch() {
    router.get(route('members.index'), { search: search.value || undefined }, { preserveState: true });
}

function submit() {
    form.post(route('members.store'), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
}
</script>

<template>
    <Head :title="t('members')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('members') }}</h2>
                <button @click="showForm = !showForm" class="btn-primary btn-sm !py-2"><span class="text-base leading-none">+</span>{{ t('new_member') }}</button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="card">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Field :label="t('member_name') + ' *'" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                    <Field :label="t('phone') + ' *'" :error="form.errors.phone"><input v-model="form.phone" dir="ltr" class="input" /></Field>
                    <Field :label="t('email')" :error="form.errors.email"><input v-model="form.email" dir="ltr" class="input" /></Field>
                    <Field :label="t('gender')" :error="form.errors.gender">
                        <select v-model="form.gender" class="input">
                            <option value="">—</option>
                            <option value="male">{{ t('male') }}</option>
                            <option value="female">{{ t('female') }}</option>
                        </select>
                    </Field>
                    <Field :label="t('birthdate')" :error="form.errors.birthdate"><input v-model="form.birthdate" type="date" class="input" /></Field>
                    <Field :label="t('notes')" :error="form.errors.notes"><input v-model="form.notes" class="input" /></Field>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
                    <button type="button" @click="showForm = false" class="btn-secondary">{{ t('cancel') }}</button>
                </div>
            </form>

            <form @submit.prevent="doSearch" class="flex gap-2">
                <input v-model="search" :placeholder="t('search_ph')" class="input" />
                <button type="submit" class="btn-secondary">{{ t('search') }}</button>
            </form>

            <div class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('member_name') }}</th>
                            <th class="th">{{ t('phone') }}</th>
                            <th class="th">{{ t('current_sub') }}</th>
                            <th class="th">{{ t('status') }}</th>
                            <th class="th">{{ t('balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in members.data" :key="m.id" class="hover:bg-slate-50">
                            <td class="td" :data-label="t('member_name')">
                                <Link :href="route('members.show', m.id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ m.name }}</Link>
                            </td>
                            <td class="td" :data-label="t('phone')"><bdi dir="ltr">{{ m.phone }}</bdi></td>
                            <td class="td" :data-label="t('current_sub')">
                                <span v-if="m.current_subscription">
                                    {{ m.current_subscription.plan }}
                                    <span v-if="m.current_subscription.days_left !== null" class="text-slate-400">({{ t('days_left').replace('{n}', num(m.current_subscription.days_left)) }})</span>
                                    <span v-else-if="m.current_subscription.sessions_remaining !== null" class="text-slate-400">({{ t('sessions_left').replace('{n}', num(m.current_subscription.sessions_remaining)) }})</span>
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="td" :data-label="t('status')">
                                <StatusBadge v-if="m.current_subscription" :value="m.current_subscription.status" />
                            </td>
                            <td class="td" :data-label="t('balance')">
                                <bdi v-if="m.balance > 0" class="font-semibold text-rose-600">{{ money(m.balance, currency) }}</bdi>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="!members.data.length"><td colspan="5" data-label="" class="px-4 py-10 text-center text-sm text-slate-400">{{ t('no_members') }}</td></tr>
                    </tbody>
                </table>
            </div>

            <Pager :links="members.links" />
        </div>
    </AuthenticatedLayout>
</template>
