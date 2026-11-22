<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ plans: Array, currency: String });

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
    <Head title="الباقات" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">الباقات</h2>
                <button @click="showForm = !showForm; editingId = null" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">+ باقة جديدة</button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <form v-if="showForm" @submit.prevent="submit" class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <input v-model="form.name" placeholder="اسم الباقة *" class="rounded-md border-gray-300" />
                        <select v-model="form.type" class="rounded-md border-gray-300">
                            <option value="duration">بمدة (أيام)</option>
                            <option value="sessions">بعدد حصص</option>
                        </select>
                        <input v-model.number="form.price" type="number" step="0.01" min="0" :placeholder="`السعر (${currency}) *`" class="rounded-md border-gray-300" />
                        <input v-if="form.type === 'duration'" v-model.number="form.duration_days" type="number" min="1" placeholder="المدة بالأيام *" class="rounded-md border-gray-300" />
                        <input v-else v-model.number="form.sessions_count" type="number" min="1" placeholder="عدد الحصص *" class="rounded-md border-gray-300" />
                        <input v-model.number="form.freeze_days_allowed" type="number" min="0" placeholder="أيام التجميد المسموحة" class="rounded-md border-gray-300" />
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" class="rounded" /> مفعّلة
                        </label>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">حفظ</button>
                        <button type="button" @click="reset" class="rounded-md bg-gray-100 px-4 py-2 text-sm">إلغاء</button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">الباقة</th>
                                <th class="px-4 py-3 text-start font-medium">النوع</th>
                                <th class="px-4 py-3 text-start font-medium">السعر</th>
                                <th class="px-4 py-3 text-start font-medium">تجميد مسموح</th>
                                <th class="px-4 py-3 text-start font-medium">اشتراكات</th>
                                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in plans" :key="p.id">
                                <td class="px-4 py-3 font-medium">{{ p.name }}</td>
                                <td class="px-4 py-3">{{ p.type === 'duration' ? `${p.duration_days} يوم` : `${p.sessions_count} حصة` }}</td>
                                <td class="px-4 py-3">{{ Number(p.price).toLocaleString() }} {{ currency }}</td>
                                <td class="px-4 py-3">{{ p.freeze_days_allowed }} يوم</td>
                                <td class="px-4 py-3">{{ p.subscriptions_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs" :class="p.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'">
                                        {{ p.is_active ? 'مفعّلة' : 'موقوفة' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3"><button @click="edit(p)" class="text-emerald-700">تعديل</button></td>
                            </tr>
                            <tr v-if="!plans.length"><td colspan="7" class="px-4 py-8 text-center text-gray-500">لا يوجد باقات بعد</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
