<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ rid: String, blocked: String, app: String, redirectHost: String, user: Object, gym: String });
const { t } = useI18n();
const busy = ref(false);
const send = (decision) => { busy.value = true; router.post(route('oauth.decide'), { rid: props.rid, decision }, { onFinish: () => (busy.value = false) }); };
</script>

<template>
    <GuestLayout>
        <Head :title="t('oa_title')" />
        <h2 class="text-lg font-bold text-ink-900">{{ t('oa_title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ t('oa_sub') }}</p>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('oa_app') }}</dt><dd class="font-semibold">{{ app }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('oa_from') }}</dt><dd dir="ltr" class="font-mono text-xs">{{ redirectHost }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('oa_as') }}</dt><dd class="text-end">{{ user.name }}<br /><span dir="ltr" class="text-xs text-slate-500">{{ user.email }}</span></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('oa_gym') }}</dt><dd class="font-semibold">{{ gym }}</dd></div>
        </dl>
        <template v-if="blocked">
            <p class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ t('oa_blocked_' + blocked) }}</p>
            <Link v-if="blocked === 'ai_off'" :href="route('settings.edit')" class="btn-secondary mt-3 w-full justify-center">{{ t('oa_settings') }}</Link>
        </template>
        <template v-else>
            <p class="mt-5 text-sm text-slate-700">{{ t('oa_reads') }}</p>
            <p class="mt-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ t('oa_egress') }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ t('oa_manage') }}</p>
            <div class="mt-5 flex gap-2">
                <button type="button" class="btn-primary flex-1 justify-center" :disabled="busy" @click="send('allow')">{{ t('oa_allow') }}</button>
                <button type="button" class="btn-secondary flex-1 justify-center" :disabled="busy" @click="send('deny')">{{ t('oa_deny') }}</button>
            </div>
        </template>
    </GuestLayout>
</template>
