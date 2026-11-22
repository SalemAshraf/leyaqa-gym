<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ status: String });
const { t } = useI18n();

const form = useForm({});
const submit = () => { form.post(route('verification.send')); };
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout :title="t('verify_email')">
        <Head :title="t('verify_email')" />

        <p class="mb-4 text-sm text-slate-500">{{ t('verify_intro') }}</p>
        <div v-if="verificationLinkSent" class="mb-4 rounded-lg bg-brand-50 px-3 py-2 text-sm font-medium text-brand-700">{{ t('verify_sent') }}</div>

        <form @submit.prevent="submit" class="flex items-center justify-between">
            <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('resend_verify') }}</button>
            <Link :href="route('logout')" method="post" as="button" class="text-sm font-medium text-slate-500 hover:text-ink-900">{{ t('logout') }}</Link>
        </form>
    </GuestLayout>
</template>
