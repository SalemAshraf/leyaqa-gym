<script setup>
import Field from '@/Components/Field.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();
const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({ current_password: '', password: '', password_confirmation: '' });

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h3 class="font-bold text-ink-900">{{ t('update_password') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ t('update_password_hint') }}</p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-4">
            <Field :label="t('current_password')" :error="form.errors.current_password">
                <input id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password" class="input" dir="ltr" autocomplete="current-password" />
            </Field>
            <Field :label="t('new_password')" :error="form.errors.password">
                <input id="password" ref="passwordInput" v-model="form.password" type="password" class="input" dir="ltr" autocomplete="new-password" />
            </Field>
            <Field :label="t('password_confirm')" :error="form.errors.password_confirmation">
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="input" dir="ltr" autocomplete="new-password" />
            </Field>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('save') }}</button>
                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-brand-700">{{ t('saved') }}</p>
            </div>
        </form>
    </section>
</template>
