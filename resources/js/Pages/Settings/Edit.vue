<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ gym: Object, currencies: Array });
const { t } = useI18n();

const form = useForm({
    name: props.gym.name, phone: props.gym.phone ?? '', country: props.gym.country,
    currency: props.gym.currency, timezone: props.gym.timezone,
});

const countries = { EG: 'مصر', SA: 'السعودية', AE: 'الإمارات', KW: 'الكويت', QA: 'قطر', BH: 'البحرين', OM: 'عُمان' };

function submit() {
    form.put(route('settings.update'));
}
</script>

<template>
    <Head :title="t('settings')" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-2xl space-y-6">
            <h2 class="text-xl font-bold text-ink-900">{{ t('gym_settings') }}</h2>

            <form @submit.prevent="submit" class="card space-y-4">
                <Field :label="t('gym_name')" :error="form.errors.name"><input v-model="form.name" class="input" /></Field>
                <Field :label="t('phone')" :error="form.errors.phone"><input v-model="form.phone" dir="ltr" class="input" /></Field>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Field :label="t('country')" :error="form.errors.country">
                        <select v-model="form.country" class="input">
                            <option v-for="(label, code) in countries" :key="code" :value="code">{{ label }}</option>
                        </select>
                    </Field>
                    <Field :label="t('currency')" :error="form.errors.currency">
                        <select v-model="form.currency" class="input">
                            <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </Field>
                    <Field :label="t('timezone')" :error="form.errors.timezone"><input v-model="form.timezone" dir="ltr" class="input" /></Field>
                </div>
                <button type="submit" :disabled="form.processing" class="btn-primary">{{ t('save') }}</button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
