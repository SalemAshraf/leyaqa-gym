<script setup>
import Field from '@/Components/Field.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const { t } = useI18n();
const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <header>
            <h3 class="font-bold text-ink-900">{{ t('delete_account') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ t('delete_account_hint') }}</p>
        </header>

        <button @click="confirmUserDeletion" class="btn-danger mt-6">{{ t('delete_account') }}</button>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-ink-900">{{ t('delete_confirm_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ t('delete_confirm_body') }}</p>

                <div class="mt-6">
                    <Field :label="t('password')" :error="form.errors.password">
                        <input id="password" ref="passwordInput" v-model="form.password" type="password" class="input" dir="ltr" @keyup.enter="deleteUser" />
                    </Field>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="closeModal">{{ t('cancel') }}</button>
                    <button type="button" class="btn-danger" :disabled="form.processing" @click="deleteUser">{{ t('delete_account') }}</button>
                </div>
            </div>
        </Modal>
    </section>
</template>
