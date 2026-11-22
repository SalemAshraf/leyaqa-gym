<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import Field from '@/Components/Field.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ staff: Array, branches: Array });
const { t } = useI18n();

const showForm = ref(false);
const form = useForm({ name: '', email: '', phone: '', password: '', role: 'staff', branch_id: '' });

function submit() {
    form.post(route('staff.store'), { onSuccess: () => { showForm.value = false; form.reset(); } });
}

function setBranch(u, branchId) {
    router.put(route('staff.update', u.id), { branch_id: branchId || null }, { preserveScroll: true });
}

function remove(id) {
    if (confirm(t('confirm_delete_staff'))) {
        router.delete(route('staff.destroy', id));
    }
}
</script>

<template>
    <Head :title="t('staff')" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('staff') }}</h2>
                <button @click="showForm = !showForm" class="btn-primary btn-sm !py-2"><span class="text-base leading-none">+</span>{{ t('new_staff') }}</button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="card">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field :label="t('member_name') + ' *'" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                    <Field :label="t('email') + ' *'" :error="form.errors.email"><input v-model="form.email" dir="ltr" class="input" /></Field>
                    <Field :label="t('phone')" :error="form.errors.phone"><input v-model="form.phone" dir="ltr" class="input" /></Field>
                    <Field :label="t('password_temp') + ' *'" :error="form.errors.password"><input v-model="form.password" type="password" class="input" /></Field>
                    <Field :label="t('role')" :error="form.errors.role">
                        <select v-model="form.role" class="input">
                            <option value="staff">{{ t('role_staff') }}</option>
                            <option value="owner">{{ t('role_owner') }}</option>
                        </select>
                    </Field>
                    <Field v-if="branches?.length" :label="t('branch')" :error="form.errors.branch_id">
                        <select v-model="form.branch_id" class="input">
                            <option value="">{{ t('all_branches') }}</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </Field>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
                    <button type="button" @click="showForm = false" class="btn-secondary">{{ t('cancel') }}</button>
                </div>
            </form>

            <div class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('member_name') }}</th>
                            <th class="th">{{ t('email') }}</th>
                            <th class="th">{{ t('role') }}</th>
                            <th v-if="branches?.length" class="th">{{ t('branch') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in staff" :key="u.id" class="hover:bg-slate-50">
                            <td class="td font-semibold text-ink-900" :data-label="t('member_name')">
                                {{ u.name }} <span v-if="u.is_you" class="text-xs text-slate-400">({{ t('you') }})</span>
                            </td>
                            <td class="td" :data-label="t('email')"><bdi dir="ltr">{{ u.email }}</bdi></td>
                            <td class="td" :data-label="t('role')">
                                <Badge :tone="u.role === 'owner' ? 'brand' : 'slate'">{{ u.role === 'owner' ? t('role_owner') : t('role_staff') }}</Badge>
                            </td>
                            <td v-if="branches?.length" class="td" :data-label="t('branch')">
                                <select v-if="u.role === 'staff'" :value="u.branch_id ?? ''" @change="setBranch(u, $event.target.value)" class="input !w-auto !py-1.5 text-sm">
                                    <option value="">{{ t('all_branches') }}</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="td" data-label="">
                                <button v-if="!u.is_you" @click="remove(u.id)" class="font-semibold text-rose-600 hover:text-rose-500">{{ t('delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
