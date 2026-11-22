<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

defineProps({ canResetPassword: Boolean, status: String });
const { t } = useI18n();

const form = useForm({ email: '', password: '', remember: false });

const submit = () => {
    form.post(route('login'), { onFinish: () => form.reset('password') });
};
</script>

<template>
    <GuestLayout :title="t('login')">
        <Head :title="t('login')" />

        <div v-if="status" class="mb-4 rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-700">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-4">
            <Field :label="t('email')" :error="form.errors.email">
                <input id="email" type="email" class="input" dir="ltr" v-model="form.email" required autofocus autocomplete="username" />
            </Field>
            <Field :label="t('password')" :error="form.errors.password">
                <input id="password" type="password" class="input" dir="ltr" v-model="form.password" required autocomplete="current-password" />
            </Field>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" v-model="form.remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                {{ t('remember_me') }}
            </label>
            <div class="flex items-center justify-between pt-1">
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-medium text-brand-700 hover:text-brand-600">
                    {{ t('forgot_password') }}
                </Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('login') }}</button>
            </div>
        </form>

        <template #footer>
            {{ t('no_account') }}
            <Link :href="route('register')" class="font-semibold text-brand-700 hover:text-brand-600">{{ t('create_gym') }}</Link>
        </template>
    </GuestLayout>
</template>
