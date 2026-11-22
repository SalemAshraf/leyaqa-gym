<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ gym: Object, currencies: Array });

const form = useForm({
    name: props.gym.name, phone: props.gym.phone ?? '', country: props.gym.country,
    currency: props.gym.currency, timezone: props.gym.timezone,
});

const countries = { EG: 'مصر', SA: 'السعودية', AE: 'الإمارات', KW: 'الكويت', QA: 'قطر', BH: 'البحرين', OM: 'عُمان' };

function submit() {
    form.put(route('settings.update'));
}
</script>

<template>
    <Head title="الإعدادات" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">إعدادات الجيم</h2></template>
        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <div>
                        <label class="block text-sm text-gray-600">اسم الجيم</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-300" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">التليفون</label>
                        <input v-model="form.phone" dir="ltr" class="mt-1 w-full rounded-md border-gray-300" />
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600">الدولة</label>
                            <select v-model="form.country" class="mt-1 w-full rounded-md border-gray-300">
                                <option v-for="(label, code) in countries" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">العملة</label>
                            <select v-model="form.currency" class="mt-1 w-full rounded-md border-gray-300">
                                <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">المنطقة الزمنية</label>
                            <input v-model="form.timezone" dir="ltr" class="mt-1 w-full rounded-md border-gray-300" />
                        </div>
                    </div>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">حفظ</button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
