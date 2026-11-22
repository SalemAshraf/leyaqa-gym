<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import Field from '@/Components/Field.vue';
import Pager from '@/Components/Pager.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ leads: Object, filters: Object });
const { t } = useI18n();

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', phone: '', source: '', notes: '', status: 'new' });
const status = ref(props.filters?.status || '');
const search = ref(props.filters?.search || '');

const tones = { new: 'brand', contacted: 'amber', converted: 'green', lost: 'slate' };

function applyFilters() {
    router.get(route('leads.index'), { status: status.value || undefined, search: search.value || undefined }, { preserveState: true });
}

function edit(lead) {
    editingId.value = lead.id;
    showForm.value = true;
    form.name = lead.name; form.phone = lead.phone; form.source = lead.source ?? '';
    form.notes = lead.notes ?? ''; form.status = lead.status;
}

function submit() {
    if (editingId.value) {
        form.put(route('leads.update', editingId.value), { onSuccess: reset, preserveScroll: true });
    } else {
        form.post(route('leads.store'), { onSuccess: reset, preserveScroll: true });
    }
}

function reset() {
    showForm.value = false; editingId.value = null; form.reset(); form.status = 'new';
}

function convert(lead) {
    router.post(route('leads.convert', lead.id), {}, { preserveScroll: true });
}

function remove(lead) {
    if (confirm(t('confirm_delete'))) {
        router.delete(route('leads.destroy', lead.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('leads')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('leads') }}</h2>
                <button @click="showForm = !showForm; editingId = null" class="btn-primary btn-sm !py-2"><span class="text-base leading-none">+</span>{{ t('new_lead') }}</button>
            </div>

            <div class="card flex flex-wrap items-center gap-2 !py-3">
                <input v-model="search" @keyup.enter="applyFilters" :placeholder="t('search_leads')" class="input !w-56 !py-2 text-sm" />
                <select v-model="status" @change="applyFilters" class="input !w-40 !py-2 text-sm">
                    <option value="">{{ t('all_statuses') }}</option>
                    <option value="new">{{ t('lead_new') }}</option>
                    <option value="contacted">{{ t('lead_contacted') }}</option>
                    <option value="converted">{{ t('lead_converted') }}</option>
                    <option value="lost">{{ t('lead_lost') }}</option>
                </select>
                <button @click="applyFilters" class="btn-secondary btn-sm">{{ t('search') }}</button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="card">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field :label="t('lead_name') + ' *'" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                    <Field :label="t('lead_phone') + ' *'" :error="form.errors.phone"><input v-model="form.phone" class="input" dir="ltr" /></Field>
                    <Field :label="t('lead_source')" :error="form.errors.source"><input v-model="form.source" class="input" :placeholder="t('lead_source_hint')" /></Field>
                    <Field v-if="editingId" :label="t('status')" :error="form.errors.status">
                        <select v-model="form.status" class="input" :disabled="form.status === 'converted'">
                            <option value="new">{{ t('lead_new') }}</option>
                            <option value="contacted">{{ t('lead_contacted') }}</option>
                            <option value="lost">{{ t('lead_lost') }}</option>
                            <option v-if="form.status === 'converted'" value="converted">{{ t('lead_converted') }}</option>
                        </select>
                    </Field>
                    <Field :label="t('lead_notes')" :error="form.errors.notes" class="sm:col-span-2"><textarea v-model="form.notes" rows="2" class="input"></textarea></Field>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
                    <button type="button" @click="reset" class="btn-secondary">{{ t('cancel') }}</button>
                </div>
            </form>

            <p v-if="!leads.data.length" class="card text-center text-sm text-slate-400">{{ t('no_leads') }}</p>

            <div v-else class="card !p-0 overflow-x-auto">
                <table class="table stack">
                    <thead>
                        <tr>
                            <th>{{ t('lead_name') }}</th>
                            <th>{{ t('lead_phone') }}</th>
                            <th>{{ t('lead_source') }}</th>
                            <th>{{ t('status') }}</th>
                            <th>{{ t('created_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in leads.data" :key="l.id">
                            <td class="font-semibold text-ink-900" :data-label="t('lead_name')">{{ l.name }}</td>
                            <td :data-label="t('lead_phone')"><bdi dir="ltr">{{ l.phone }}</bdi></td>
                            <td class="text-slate-500" :data-label="t('lead_source')">{{ l.source || '—' }}</td>
                            <td :data-label="t('status')"><Badge :tone="tones[l.status]">{{ t('lead_' + l.status) }}</Badge></td>
                            <td class="text-slate-400 text-xs" :data-label="t('created_at')">{{ l.created_at }}</td>
                            <td class="whitespace-nowrap text-end" data-label="">
                                <button v-if="l.status !== 'converted'" @click="convert(l)" class="text-sm font-semibold text-emerald-700 hover:text-emerald-600">{{ t('convert_to_member') }}</button>
                                <Link v-else :href="route('members.show', l.member_id)" class="text-sm font-semibold text-brand-700 hover:text-brand-600">{{ t('view_member') }}</Link>
                                <button @click="edit(l)" class="ms-3 text-sm font-semibold text-brand-700 hover:text-brand-600">{{ t('edit') }}</button>
                                <button v-if="l.status !== 'converted'" @click="remove(l)" class="ms-3 text-sm font-semibold text-rose-600 hover:text-rose-500">{{ t('delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pager :links="leads.links" />
        </div>
    </AuthenticatedLayout>
</template>
