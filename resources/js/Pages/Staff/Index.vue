<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ staff: Array });

const showForm = ref(false);
const form = useForm({ name: '', email: '', phone: '', password: '', role: 'staff' });

function submit() {
    form.post(route('staff.store'), { onSuccess: () => { showForm.value = false; form.reset(); } });
}

function remove(id) {
    if (confirm('حذف هذا الموظف؟')) {
        router.delete(route('staff.destroy', id));
    }
}
</script>

<template>
    <Head title="الفريق" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">الفريق</h2>
                <button @click="showForm = !showForm" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">+ موظف</button>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <form v-if="showForm" @submit.prevent="submit" class="grid grid-cols-1 gap-4 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-2">
                    <input v-model="form.name" placeholder="الاسم *" class="rounded-md border-gray-300" />
                    <input v-model="form.email" dir="ltr" placeholder="البريد الإلكتروني *" class="rounded-md border-gray-300" />
                    <input v-model="form.phone" dir="ltr" placeholder="الموبايل" class="rounded-md border-gray-300" />
                    <input v-model="form.password" type="password" placeholder="كلمة سر مؤقتة *" class="rounded-md border-gray-300" />
                    <select v-model="form.role" class="rounded-md border-gray-300">
                        <option value="staff">موظف ريسبشن</option>
                        <option value="owner">مالك</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">حفظ</button>
                        <button type="button" @click="showForm = false" class="rounded-md bg-gray-100 px-4 py-2 text-sm">إلغاء</button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">الاسم</th>
                                <th class="px-4 py-3 text-start font-medium">البريد</th>
                                <th class="px-4 py-3 text-start font-medium">الدور</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="u in staff" :key="u.id">
                                <td class="px-4 py-3 font-medium">{{ u.name }} <span v-if="u.is_you" class="text-xs text-gray-400">(أنت)</span></td>
                                <td class="px-4 py-3" dir="ltr">{{ u.email }}</td>
                                <td class="px-4 py-3">{{ u.role === 'owner' ? 'مالك' : 'موظف' }}</td>
                                <td class="px-4 py-3">
                                    <button v-if="!u.is_you" @click="remove(u.id)" class="text-red-600">حذف</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
