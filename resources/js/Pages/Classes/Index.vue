<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import Field from '@/Components/Field.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ classes: Array, date: String, weekday: Number });
const { t, num } = useI18n();

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', coach: '', weekday: props.weekday, start_time: '18:00', duration_minutes: 60, capacity: 20, is_active: true });

function edit(c) {
    editingId.value = c.id;
    showForm.value = true;
    form.name = c.name; form.coach = c.coach ?? ''; form.weekday = c.weekday;
    form.start_time = c.start_time; form.duration_minutes = c.duration_minutes;
    form.capacity = c.capacity; form.is_active = !!c.is_active;
}

function submitClass() {
    if (editingId.value) {
        form.put(route('classes.update', editingId.value), { onSuccess: reset, preserveScroll: true });
    } else {
        form.post(route('classes.store'), { onSuccess: reset, preserveScroll: true });
    }
}

function reset() {
    showForm.value = false; editingId.value = null;
    form.reset(); form.weekday = props.weekday; form.start_time = '18:00'; form.duration_minutes = 60; form.capacity = 20; form.is_active = true;
}

function pickDate(e) {
    router.get(route('classes.index'), { date: e.target.value }, { preserveState: true });
}

function removeClass(c) {
    if (confirm(t('confirm_delete'))) {
        router.delete(route('classes.destroy', c.id), { preserveScroll: true });
    }
}

// booking flow
const bookingFor = ref(null);
const query = ref('');
const results = ref([]);
const searching = ref(false);
let debounce = null;

function openBooking(c) {
    bookingFor.value = bookingFor.value === c.id ? null : c.id;
    query.value = ''; results.value = [];
}

function searchMembers() {
    clearTimeout(debounce);
    debounce = setTimeout(async () => {
        searching.value = true;
        const res = await fetch(route('members.search') + '?q=' + encodeURIComponent(query.value), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        results.value = await res.json();
        searching.value = false;
    }, 250);
}

function book(c, member) {
    router.post(route('classes.bookings.store', c.id), { member_id: member.id, class_date: props.date }, { preserveScroll: true });
    results.value = results.value.filter((m) => m.id !== member.id);
}

function cancelBooking(id) {
    router.delete(route('bookings.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('classes')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-ink-900">{{ t('classes') }}</h2>
                <div class="flex items-center gap-2">
                    <input type="date" :value="date" @change="pickDate" class="input !w-auto" />
                    <button @click="showForm = !showForm; editingId = null" class="btn-primary btn-sm !py-2"><span class="text-base leading-none">+</span>{{ t('new_class') }}</button>
                </div>
            </div>

            <form v-if="showForm" @submit.prevent="submitClass" class="card">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Field :label="t('class_name') + ' *'" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                    <Field :label="t('coach')" :error="form.errors.coach"><input v-model="form.coach" class="input" /></Field>
                    <Field :label="t('weekday') + ' *'" :error="form.errors.weekday">
                        <select v-model.number="form.weekday" class="input">
                            <option v-for="d in 7" :key="d - 1" :value="d - 1">{{ t('wd_' + (d - 1)) }}</option>
                        </select>
                    </Field>
                    <Field :label="t('start_time') + ' *'" :error="form.errors.start_time"><input v-model="form.start_time" type="time" class="input" dir="ltr" /></Field>
                    <Field :label="t('duration_min') + ' *'" :error="form.errors.duration_minutes"><input v-model.number="form.duration_minutes" type="number" min="15" max="480" class="input" /></Field>
                    <Field :label="t('capacity') + ' *'" :error="form.errors.capacity"><input v-model.number="form.capacity" type="number" min="1" max="500" class="input" /></Field>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" /> {{ t('plan_active') }}
                    </label>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
                    <button type="button" @click="reset" class="btn-secondary">{{ t('cancel') }}</button>
                </div>
            </form>

            <p v-if="!classes.length" class="card text-center text-sm text-slate-400">{{ t('no_classes') }}</p>

            <div v-for="c in classes" :key="c.id" class="card !p-0 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3" :class="{ 'opacity-60': !c.is_active }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 font-bold text-brand-700">{{ c.start_time }}</div>
                        <div>
                            <div class="font-bold text-ink-900">
                                {{ c.name }}
                                <span v-if="c.coach" class="text-sm font-medium text-slate-400">· {{ c.coach }}</span>
                                <Badge v-if="!c.is_active" tone="slate" class="ms-1">{{ t('class_inactive') }}</Badge>
                            </div>
                            <div class="text-xs text-slate-400">{{ t('wd_' + c.weekday) }} · {{ num(c.duration_minutes) }} {{ t('duration_min').replace(/\(.*\)/, '').trim() }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge :tone="!c.runs_this_day ? 'slate' : c.booked >= c.capacity ? 'rose' : 'green'">
                            <template v-if="!c.runs_this_day">{{ t('not_today') }}</template>
                            <template v-else>{{ t('booked_of').replace('{b}', num(c.booked)).replace('{c}', num(c.capacity)) }}<template v-if="c.booked >= c.capacity"> · {{ t('full') }}</template></template>
                        </Badge>
                        <button v-if="c.runs_this_day && c.is_active" @click="openBooking(c)" class="btn-secondary btn-sm">{{ t('book_member') }}</button>
                        <button @click="edit(c)" class="text-sm font-semibold text-brand-700 hover:text-brand-600">{{ t('edit') }}</button>
                        <button v-if="!c.booked" @click="removeClass(c)" class="text-sm font-semibold text-rose-600 hover:text-rose-500">{{ t('delete') }}</button>
                    </div>
                </div>

                <div v-if="bookingFor === c.id" class="border-t border-slate-100 bg-slate-50 px-5 py-3">
                    <input v-model="query" @input="searchMembers" :placeholder="t('member_search_book')" class="input !py-2 text-sm" />
                    <ul v-if="results.length" class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                        <li v-for="m in results" :key="m.id" class="flex items-center justify-between px-4 py-2 text-sm">
                            <span class="font-medium text-ink-900">{{ m.name }} <bdi class="text-slate-400" dir="ltr">{{ m.phone }}</bdi></span>
                            <button @click="book(c, m)" class="btn-primary btn-sm">{{ t('add') }}</button>
                        </li>
                    </ul>
                </div>

                <ul v-if="c.runs_this_day && c.bookings.length" class="divide-y divide-slate-100 border-t border-slate-100">
                    <li v-for="b in c.bookings" :key="b.id" class="flex items-center justify-between px-5 py-2.5 text-sm">
                        <span class="font-medium text-ink-900">{{ b.member }} <bdi class="text-slate-400" dir="ltr">{{ b.phone }}</bdi></span>
                        <button @click="cancelBooking(b.id)" class="text-xs font-semibold text-rose-600 hover:text-rose-500">{{ t('booking_cancel') }}</button>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
