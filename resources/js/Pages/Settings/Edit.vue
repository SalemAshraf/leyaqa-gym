<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Field from '@/Components/Field.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ gym: Object, currencies: Array, ai: Object });
const { t } = useI18n();

const form = useForm({
    name: props.gym.name, phone: props.gym.phone ?? '', country: props.gym.country,
    currency: props.gym.currency, timezone: props.gym.timezone,
});

const countries = { EG: 'مصر', SA: 'السعودية', AE: 'الإمارات', KW: 'الكويت', QA: 'قطر', BH: 'البحرين', OM: 'عُمان' };

function submit() {
    form.put(route('settings.update'));
}

function showSetupGuide() {
    router.post(route('setup.show'), {}, { preserveScroll: true });
}

function toggleAi() {
    router.patch(route('settings.ai'), { enabled: ! props.ai.enabled }, { preserveScroll: true });
}

function disconnect(family) {
    if (confirm(t('ai_disconnect_confirm'))) {
        router.delete(route('settings.ai.disconnect', family), { preserveScroll: true });
    }
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
            <div class="card space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="font-bold text-ink-900">{{ t('ai_title') }}</div>
                        <p class="text-sm text-slate-500">{{ t('ai_desc') }}</p>
                    </div>
                    <button @click="toggleAi" class="btn-sm whitespace-nowrap" :class="ai.enabled ? 'btn-primary' : 'btn-secondary'">
                        {{ ai.enabled ? t('ai_on') : t('ai_off') }}
                    </button>
                </div>
                <template v-if="ai.enabled">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                        <div class="font-semibold text-ink-900">{{ t('ai_how_title') }}</div>
                        <ol class="mt-1 list-decimal space-y-0.5 ps-5 text-slate-600">
                            <li>{{ t('ai_how_1') }}</li>
                            <li>{{ t('ai_how_2') }}</li>
                            <li>{{ t('ai_how_3') }}</li>
                        </ol>
                    </div>
                    <div v-if="ai.connections.length">
                        <div class="mb-2 text-sm font-bold text-ink-900">{{ t('ai_connections') }}</div>
                        <ul class="divide-y divide-slate-100">
                            <li v-for="c in ai.connections" :key="c.family" class="flex items-center justify-between gap-3 py-2 text-sm">
                                <div>
                                    <span class="font-semibold">{{ c.app }}</span>
                                    <span class="ms-2 text-xs text-slate-500">{{ t('ai_connected_at') }} {{ c.connected_at?.slice(0, 10) }}<template v-if="c.last_used_at"> · {{ t('ai_last_used') }} {{ c.last_used_at.slice(0, 10) }}</template></span>
                                </div>
                                <button @click="disconnect(c.family)" class="btn-secondary btn-sm whitespace-nowrap">{{ t('ai_disconnect') }}</button>
                            </li>
                        </ul>
                    </div>
                    <p v-else class="text-sm text-slate-500">{{ t('ai_no_connections') }}</p>
                </template>
            </div>

            <div class="card flex items-center justify-between gap-3">
                <div>
                    <div class="font-bold text-ink-900">{{ t('setup_guide') }}</div>
                    <p class="text-sm text-slate-500">{{ t('setup_guide_d') }}</p>
                </div>
                <button @click="showSetupGuide" class="btn-secondary btn-sm whitespace-nowrap">{{ t('setup_guide_show') }}</button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
