<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();

const form = useForm({
    gym_name: '', country: 'EG', name: '', email: '', password: '', password_confirmation: '',
});

const countries = [
    ['EG', 'مصر'], ['SA', 'السعودية'], ['AE', 'الإمارات'], ['KW', 'الكويت'],
    ['QA', 'قطر'], ['BH', 'البحرين'], ['OM', 'عُمان'],
];

const submit = () => {
    form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
};
</script>

<template>
    <GuestLayout :title="t('create_gym')">
        <Head :title="t('create_gym')" />

        <form @submit.prevent="submit" class="space-y-4">
            <Field :label="t('gym_name')" :error="form.errors.gym_name">
                <input id="gym_name" type="text" class="input" v-model="form.gym_name" required autofocus />
            </Field>
            <Field :label="t('country')" :error="form.errors.country">
                <select id="country" v-model="form.country" class="input">
                    <option v-for="[code, label] in countries" :key="code" :value="code">{{ label }}</option>
                </select>
            </Field>
            <Field :label="t('your_name')" :error="form.errors.name">
                <input id="name" type="text" class="input" v-model="form.name" required autocomplete="name" />
            </Field>
            <Field :label="t('email')" :error="form.errors.email">
                <input id="email" type="email" class="input" dir="ltr" v-model="form.email" required autocomplete="username" />
            </Field>
            <Field :label="t('password')" :error="form.errors.password">
                <input id="password" type="password" class="input" dir="ltr" v-model="form.password" required autocomplete="new-password" />
            </Field>
            <Field :label="t('password_confirm')" :error="form.errors.password_confirmation">
                <input id="password_confirmation" type="password" class="input" dir="ltr" v-model="form.password_confirmation" required autocomplete="new-password" />
            </Field>
            <div class="flex items-center justify-between pt-1">
                <Link :href="route('login')" class="text-sm font-medium text-brand-700 hover:text-brand-600">{{ t('have_account') }}</Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('register') }}</button>
            </div>
        </form>
    </GuestLayout>
</template>
