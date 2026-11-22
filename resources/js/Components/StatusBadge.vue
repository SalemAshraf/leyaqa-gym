<script setup>
import Badge from './Badge.vue';
import { useI18n } from '@/lib/i18n';
defineProps({ value: String });
const { t } = useI18n();
const tones = { active: 'green', expired: 'slate', frozen: 'blue', cancelled: 'rose', upcoming: 'amber' };
</script>
<template><Badge :tone="tones[value] || 'slate'">{{ t('s_' + value) }}</Badge></template>
