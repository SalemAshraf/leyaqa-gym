<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();
const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm'), { onFinish: () => form.reset() });
};
</script>

<template>
    <GuestLayout :title="t('confirm_password')">
        <Head :title="t('confirm_password')" />

        <p class="mb-4 text-sm text-slate-500">{{ t('confirm_intro') }}</p>
        <form @submit.prevent="submit" class="space-y-4">
            <Field :label="t('password')" :error="form.errors.password">
                <input id="password" type="password" class="input" dir="ltr" v-model="form.password" required autofocus autocomplete="current-password" />
            </Field>
            <button type="submit" class="btn-primary w-full" :disabled="form.processing">{{ t('confirm') }}</button>
        </form>
    </GuestLayout>
</template>
