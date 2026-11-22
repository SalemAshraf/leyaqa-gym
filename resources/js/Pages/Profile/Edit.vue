<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

defineProps({ mustVerifyEmail: Boolean, status: String });
const { t } = useI18n();
</script>

<template>
    <Head :title="t('profile')" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <h2 class="text-xl font-bold text-ink-900">{{ t('profile') }}</h2>
            <div class="card">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>
            <div class="card">
                <UpdatePasswordForm />
            </div>
            <div class="card">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
