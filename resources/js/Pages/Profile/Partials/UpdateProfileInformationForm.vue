<script setup>
import Field from '@/Components/Field.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

defineProps({ mustVerifyEmail: Boolean, status: String });
const { t } = useI18n();

const user = usePage().props.auth.user;
const form = useForm({ name: user.name, email: user.email });
</script>

<template>
    <section>
        <header>
            <h3 class="font-bold text-ink-900">{{ t('profile_info') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ t('profile_info_hint') }}</p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-4">
            <Field :label="t('member_name')" :error="form.errors.name">
                <input id="name" type="text" class="input" v-model="form.name" required autofocus autocomplete="name" />
            </Field>
            <Field :label="t('email')" :error="form.errors.email">
                <input id="email" type="email" class="input" dir="ltr" v-model="form.email" required autocomplete="username" />
            </Field>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm text-slate-600">
                    {{ t('email_unverified') }}
                    <Link :href="route('verification.send')" method="post" as="button" class="font-semibold text-brand-700 hover:text-brand-600">
                        {{ t('resend_verify') }}
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-brand-700">{{ t('verify_sent') }}</div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('save') }}</button>
                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-brand-700">{{ t('saved') }}</p>
            </div>
        </form>
    </section>
</template>
