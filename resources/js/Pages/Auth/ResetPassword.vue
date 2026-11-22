<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ email: String, token: String });
const { t } = useI18n();

const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });

const submit = () => {
    form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
};
</script>

<template>
    <GuestLayout :title="t('reset_password')">
        <Head :title="t('reset_password')" />

        <form @submit.prevent="submit" class="space-y-4">
            <Field :label="t('email')" :error="form.errors.email">
                <input id="email" type="email" class="input" dir="ltr" v-model="form.email" required autofocus autocomplete="username" />
            </Field>
            <Field :label="t('password')" :error="form.errors.password">
                <input id="password" type="password" class="input" dir="ltr" v-model="form.password" required autocomplete="new-password" />
            </Field>
            <Field :label="t('password_confirm')" :error="form.errors.password_confirmation">
                <input id="password_confirmation" type="password" class="input" dir="ltr" v-model="form.password_confirmation" required autocomplete="new-password" />
            </Field>
            <button type="submit" class="btn-primary w-full" :disabled="form.processing">{{ t('reset_password') }}</button>
        </form>
    </GuestLayout>
</template>
