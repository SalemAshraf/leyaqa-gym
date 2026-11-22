<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    gym_name: '',
    country: 'EG',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const countries = [
    ['EG', 'مصر'], ['SA', 'السعودية'], ['AE', 'الإمارات'], ['KW', 'الكويت'],
    ['QA', 'قطر'], ['BH', 'البحرين'], ['OM', 'عُمان'],
];

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل جيم جديد" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="gym_name" value="اسم الجيم أو الاستوديو" />
                <TextInput id="gym_name" type="text" class="mt-1 block w-full" v-model="form.gym_name" required autofocus />
                <InputError class="mt-2" :message="form.errors.gym_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="country" value="الدولة" />
                <select id="country" v-model="form.country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option v-for="[code, label] in countries" :key="code" :value="code">{{ label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.country" />
            </div>

            <div class="mt-4">
                <InputLabel for="name" value="اسمك" />
                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="البريد الإلكتروني" />
                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="كلمة السر" />
                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="تأكيد كلمة السر" />
                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link :href="route('login')" class="rounded-md text-sm text-gray-600 underline hover:text-gray-900">
                    عندك حساب بالفعل؟
                </Link>
                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    إنشاء الحساب
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
