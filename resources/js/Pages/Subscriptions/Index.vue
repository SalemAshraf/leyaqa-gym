<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pager from '@/Components/Pager.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/lib/i18n';

const props = defineProps({ subscriptions: Object, currency: String });
const { t, money } = useI18n();
</script>

<template>
    <Head :title="t('subscriptions')" />
    <AuthenticatedLayout>
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-ink-900">{{ t('subscriptions') }}</h2>
            <p class="text-sm text-slate-500">{{ t('subs_hint') }}</p>

            <div class="card !p-0 overflow-x-auto">
                <table class="stack min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="th">{{ t('member') }}</th>
                            <th class="th">{{ t('plan') }}</th>
                            <th class="th">{{ t('from') }}</th>
                            <th class="th">{{ t('to') }}</th>
                            <th class="th">{{ t('status') }}</th>
                            <th class="th">{{ t('balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in subscriptions.data" :key="s.id" class="hover:bg-slate-50">
                            <td class="td" :data-label="t('member')">
                                <Link :href="route('members.show', s.member.id)" class="font-semibold text-ink-900 hover:text-brand-700">{{ s.member.name }}</Link>
                            </td>
                            <td class="td" :data-label="t('plan')">{{ s.plan }}</td>
                            <td class="td" :data-label="t('from')">{{ s.starts_at }}</td>
                            <td class="td" :data-label="t('to')">{{ s.ends_at ?? t('sessions_left').replace('{n}', s.sessions_remaining ?? 0) }}</td>
                            <td class="td" :data-label="t('status')"><StatusBadge :value="s.status" /></td>
                            <td class="td" :data-label="t('balance')">
                                <bdi v-if="s.balance > 0" class="font-semibold text-rose-600">{{ money(s.balance, currency) }}</bdi>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="!subscriptions.data.length"><td colspan="6" data-label="" class="px-4 py-10 text-center text-sm text-slate-400">{{ t('no_subs') }}</td></tr>
                    </tbody>
                </table>
            </div>

            <Pager :links="subscriptions.links" />
        </div>
    </AuthenticatedLayout>
</template>
