<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

defineProps({ status: String });
const { t } = useI18n();

const form = useForm({ email: '' });

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout :title="t('forgot_password')">
        <Head :title="t('forgot_password')" />

        <p class="mb-4 text-sm text-slate-500">{{ t('forgot_intro') }}</p>
        <div v-if="status" class="mb-4 rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-700">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-4">
            <Field :label="t('email')" :error="form.errors.email">
                <input id="email" type="email" class="input" dir="ltr" v-model="form.email" required autofocus autocomplete="username" />
            </Field>
            <div class="flex items-center justify-between">
                <Link :href="route('login')" class="text-sm font-medium text-slate-500 hover:text-ink-900">{{ t('back_login') }}</Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('send_reset') }}</button>
            </div>
        </form>
    </GuestLayout>
</template>
