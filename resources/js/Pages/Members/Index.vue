<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ members: Object, filters: Object, currency: String });

const search = ref(props.filters?.search ?? '');
const showForm = ref(false);
const form = useForm({ name: '', phone: '', email: '', gender: '', birthdate: '', notes: '' });

function doSearch() {
    router.get(route('members.index'), { search: search.value }, { preserveState: true });
}

function submit() {
    form.post(route('members.store'), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
}

const statusLabel = (s) => ({ active: 'ساري', expired: 'منتهي', frozen: 'مجمّد', cancelled: 'ملغي', upcoming: 'لم يبدأ' }[s] ?? s);
const statusClass = (s) => ({
    active: 'bg-emerald-100 text-emerald-800',
    expired: 'bg-gray-200 text-gray-700',
    frozen: 'bg-sky-100 text-sky-800',
    cancelled: 'bg-red-100 text-red-800',
    upcoming: 'bg-amber-100 text-amber-800',
}[s] ?? 'bg-gray-100 text-gray-700');
</script>

<template>
    <Head title="الأعضاء" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">الأعضاء</h2>
                <button @click="showForm = !showForm" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    + عضو جديد
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <form v-if="showForm" @submit.prevent="submit" class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <input v-model="form.name" placeholder="الاسم *" class="rounded-md border-gray-300" />
                        <input v-model="form.phone" placeholder="رقم الموبايل *" class="rounded-md border-gray-300" dir="ltr" />
                        <input v-model="form.email" placeholder="البريد الإلكتروني" class="rounded-md border-gray-300" dir="ltr" />
                        <select v-model="form.gender" class="rounded-md border-gray-300">
                            <option value="">النوع</option>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                        <input v-model="form.birthdate" type="date" class="rounded-md border-gray-300" />
                        <input v-model="form.notes" placeholder="ملاحظات" class="rounded-md border-gray-300" />
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">حفظ</button>
                        <button type="button" @click="showForm = false" class="rounded-md bg-gray-100 px-4 py-2 text-sm">إلغاء</button>
                    </div>
                </form>

                <div class="flex gap-2">
                    <input v-model="search" @keyup.enter="doSearch" placeholder="ابحث بالاسم أو الموبايل" class="w-full rounded-md border-gray-300" />
                    <button @click="doSearch" class="rounded-md bg-white px-4 py-2 text-sm shadow-sm">بحث</button>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">الاسم</th>
                                <th class="px-4 py-3 text-start font-medium">الموبايل</th>
                                <th class="px-4 py-3 text-start font-medium">الاشتراك الحالي</th>
                                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                                <th class="px-4 py-3 text-start font-medium">مستحقات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="m in members.data" :key="m.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <Link :href="route('members.show', m.id)" class="font-medium text-emerald-700">{{ m.name }}</Link>
                                </td>
                                <td class="px-4 py-3" dir="ltr">{{ m.phone }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="m.current_subscription">
                                        {{ m.current_subscription.plan }}
                                        <span v-if="m.current_subscription.days_left !== null" class="text-gray-500">
                                            (باقي {{ m.current_subscription.days_left }} يوم)
                                        </span>
                                        <span v-else-if="m.current_subscription.sessions_remaining !== null" class="text-gray-500">
                                            (باقي {{ m.current_subscription.sessions_remaining }} حصة)
                                        </span>
                                    </span>
                                    <span v-else class="text-gray-400">لا يوجد</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="m.current_subscription" class="rounded-full px-2 py-1 text-xs" :class="statusClass(m.current_subscription.status)">
                                        {{ statusLabel(m.current_subscription.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="m.balance > 0" class="text-red-600">{{ m.balance.toLocaleString() }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="!members.data.length"><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا يوجد أعضاء بعد</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-1">
                    <Link v-for="link in members.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                        class="rounded px-3 py-1 text-sm" :class="link.active ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600'" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
