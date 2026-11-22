<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import Pager from '@/Components/Pager.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

defineProps({ gyms: Object });
const { t, num } = useI18n();
const tones = { trialing: 'brand', active: 'green', past_due: 'amber', suspended: 'rose' };
</script>

<template>
    <Head :title="t('platform_admin')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-ink-900">{{ t('platform_admin') }}</h2>
            <div class="card !p-0 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ t('gym_name') }}</th><th>{{ t('status') }}</th><th>{{ t('plan') }}</th>
                            <th>{{ t('trial') }}</th><th>{{ t('renews') }}</th><th>{{ t('members') }}</th><th>{{ t('joined') }}</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="g in gyms.data" :key="g.id">
                            <td class="font-semibold text-ink-900">{{ g.name }} <span class="text-xs text-slate-400">{{ g.country }}</span></td>
                            <td><Badge :tone="tones[g.status]">{{ t('pstatus_' + g.status) }}</Badge></td>
                            <td class="text-slate-500">{{ g.plan_ar || '—' }}</td>
                            <td>{{ g.status === 'trialing' ? num(g.trial_days_left) + ' ' + t('days') : '—' }}</td>
                            <td class="text-slate-500">{{ g.renews_at || '—' }}</td>
                            <td>{{ num(g.members_count) }}</td>
                            <td class="text-slate-400 text-xs">{{ g.created_at }}</td>
                            <td><Link :href="route('platform.gyms.show', g.id)" class="text-sm font-semibold text-brand-700 hover:text-brand-600">{{ t('manage') }}</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pager :links="gyms.links" />
        </div>
    </AuthenticatedLayout>
</template>
