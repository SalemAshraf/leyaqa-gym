<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import Field from '@/Components/Field.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ plans: Array, currency: String });
const { t, money, num } = useI18n();

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '', type: 'duration', duration_days: 30, sessions_count: null,
    price: '', freeze_days_allowed: 0, is_active: true,
});

function edit(plan) {
    editingId.value = plan.id;
    showForm.value = true;
    form.name = plan.name; form.type = plan.type;
    form.duration_days = plan.duration_days; form.sessions_count = plan.sessions_count;
    form.price = plan.price; form.freeze_days_allowed = plan.freeze_days_allowed;
    form.is_active = !!plan.is_active;
}

function submit() {
    if (editingId.value) {
        form.put(route('plans.update', editingId.value), { onSuccess: reset });
    } else {
        form.post(route('plans.store'), { onSuccess: reset });
    }
}

function reset() {
    showForm.value = false; editingId.value = null; form.reset(); form.type = 'duration';
}
</script>

<template>
    <Head :title="t('plans')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('plans') }}</h2>
                <button @click="showForm = !showForm; editingId = null" class="btn-primary btn-sm !py-2"><span class="text-base leading-none">+</span>{{ t('new_plan') }}</button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="card">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Field :label="t('plan_name') + ' *'" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                    <Field :label="t('plan_type')" :error="form.errors.type">
                        <select v-model="form.type" class="input">
                            <option value="duration">{{ t('type_duration') }}</option>
                            <option value="sessions">{{ t('type_sessions') }}</option>
                        </select>
                    </Field>
                    <Field :label="t('price') + ' *'" :error="form.errors.price"><input v-model.number="form.price" type="number" step="0.01" min="0" class="input" /></Field>
                    <Field v-if="form.type === 'duration'" :label="t('duration_days') + ' *'" :error="form.errors.duration_days"><input v-model.number="form.duration_days" type="number" min="1" class="input" /></Field>
                    <Field v-else :label="t('sessions_count') + ' *'" :error="form.errors.sessions_count"><input v-model.number="form.sessions_count" type="number" min="1" class="input" /></Field>
                    <Field :label="t('freeze_days_allowed')" :error="form.errors.freeze_days_allowed"><input v-model.number="form.freeze_days_allowed" type="number" min="0" class="input" /></Field>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" /> {{ t('plan_active') }}
                    </label>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
                    <button type="button" @click="reset" class="btn-secondary">{{ t('cancel') }}</button>
                </div>
            </form>

            <div class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('plan_name') }}</th>
                            <th class="th">{{ t('plan_type') }}</th>
                            <th class="th">{{ t('price') }}</th>
                            <th class="th">{{ t('freeze_days_allowed') }}</th>
                            <th class="th">{{ t('subscriptions') }}</th>
                            <th class="th">{{ t('status') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in plans" :key="p.id" class="hover:bg-slate-50">
                            <td class="td font-semibold text-ink-900" :data-label="t('plan_name')">{{ p.name }}</td>
                            <td class="td" :data-label="t('plan_type')">{{ p.type === 'duration' ? num(p.duration_days) + ' ' + t('days_unit') : num(p.sessions_count) + ' ' + t('sessions_unit') }}</td>
                            <td class="td" :data-label="t('price')"><bdi>{{ money(p.price, currency) }}</bdi></td>
                            <td class="td" :data-label="t('freeze_days_allowed')">{{ num(p.freeze_days_allowed) }} {{ t('days_unit') }}</td>
                            <td class="td" :data-label="t('subscriptions')">{{ num(p.subscriptions_count) }}</td>
                            <td class="td" :data-label="t('status')">
                                <Badge :tone="p.is_active ? 'green' : 'slate'">{{ p.is_active ? t('plan_active') : t('plan_inactive') }}</Badge>
                            </td>
                            <td class="td" data-label=""><button @click="edit(p)" class="font-semibold text-brand-700 hover:text-brand-600">{{ t('edit') }}</button></td>
                        </tr>
                        <tr v-if="!plans.length"><td colspan="7" data-label="" class="px-4 py-10 text-center text-sm text-slate-400">{{ t('no_plans') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
