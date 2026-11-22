<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ branches: Array });
const pageErrors = usePage().props.errors;
const { t, num } = useI18n();

const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name: '', address: '', phone: '' });

function startEdit(b) {
    editing.value = b.id;
    form.name = b.name;
    form.address = b.address ?? '';
    form.phone = b.phone ?? '';
    showForm.value = true;
}

function submit() {
    if (editing.value) {
        form.put(route('branches.update', editing.value), {
            onSuccess: () => { showForm.value = false; editing.value = null; form.reset(); },
        });
    } else {
        form.post(route('branches.store'), {
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    }
}

function remove(b) {
    if (confirm(t('confirm_delete_branch'))) {
        router.delete(route('branches.destroy', b.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('branches')" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('branches') }}</h2>
                <button @click="showForm = !showForm; editing = null; form.reset()" class="btn-primary btn-sm !py-2"><span class="text-base leading-none">+</span>{{ t('new_branch') }}</button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="card">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Field :label="t('branch_name') + ' *'" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                    <Field :label="t('address')" :error="form.errors.address"><input v-model="form.address" class="input" /></Field>
                    <Field :label="t('phone')" :error="form.errors.phone"><input v-model="form.phone" dir="ltr" class="input" /></Field>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
                    <button type="button" @click="showForm = false; editing = null" class="btn-secondary">{{ t('cancel') }}</button>
                </div>
            </form>

            <div class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('branch_name') }}</th>
                            <th class="th">{{ t('address') }}</th>
                            <th class="th">{{ t('phone') }}</th>
                            <th class="th">{{ t('members') }}</th>
                            <th class="th">{{ t('staff') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="b in branches" :key="b.id" class="hover:bg-slate-50">
                            <td class="td font-semibold text-ink-900" :data-label="t('branch_name')">{{ b.name }}</td>
                            <td class="td text-slate-500" :data-label="t('address')">{{ b.address || '—' }}</td>
                            <td class="td" :data-label="t('phone')"><bdi dir="ltr">{{ b.phone || '—' }}</bdi></td>
                            <td class="td" :data-label="t('members')">{{ num(b.members_count) }}</td>
                            <td class="td" :data-label="t('staff')">{{ num(b.staff_count) }}</td>
                            <td class="td" data-label="">
                                <div class="flex gap-3">
                                    <button @click="startEdit(b)" class="font-semibold text-brand-700 hover:text-brand-600">{{ t('edit') }}</button>
                                    <button @click="remove(b)" class="font-semibold text-rose-600 hover:text-rose-500">{{ t('delete') }}</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!branches.length"><td colspan="6" data-label="" class="px-4 py-10 text-center text-sm text-slate-400">{{ t('no_branches') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <p v-if="pageErrors?.branch" class="text-sm font-semibold text-rose-600">{{ t(pageErrors.branch) }}</p>
            <p class="text-sm text-slate-400">{{ t('branches_hint') }}</p>
        </div>
    </AuthenticatedLayout>
</template>
