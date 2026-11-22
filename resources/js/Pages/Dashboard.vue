<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    expiringSoon: Array,
    debtors: Array,
    currency: String,
});

const cards = [
    { key: 'members', label: 'الأعضاء النشطون' },
    { key: 'active_subscriptions', label: 'اشتراكات سارية' },
    { key: 'checkins_today', label: 'حضور اليوم' },
    { key: 'denied_today', label: 'مرفوض اليوم' },
];
</script>

<template>
    <Head title="الرئيسية" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">الرئيسية</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div v-for="card in cards" :key="card.key" class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">{{ card.label }}</div>
                        <div class="mt-1 text-3xl font-bold text-gray-900">{{ stats[card.key] }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">إيراد هذا الشهر</div>
                        <div class="mt-1 text-3xl font-bold text-emerald-700">{{ stats.revenue_month.toLocaleString() }} {{ currency }}</div>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">مستحقات لم تُدفع</div>
                        <div class="mt-1 text-3xl font-bold text-red-600">{{ stats.outstanding_total.toLocaleString() }} {{ currency }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white shadow-sm">
                        <div class="border-b px-5 py-3 font-semibold text-gray-800">اشتراكات تنتهي خلال ٧ أيام</div>
                        <div v-if="!expiringSoon.length" class="px-5 py-6 text-sm text-gray-500">لا يوجد</div>
                        <ul v-else class="divide-y">
                            <li v-for="row in expiringSoon" :key="row.id" class="flex items-center justify-between px-5 py-3 text-sm">
                                <div>
                                    <Link :href="route('members.show', row.member_id)" class="font-medium text-gray-900">{{ row.member }}</Link>
                                    <span class="text-gray-500"> · {{ row.plan }}</span>
                                </div>
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-800">
                                    {{ row.days_left === 0 ? 'ينتهي اليوم' : `باقي ${row.days_left} يوم` }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-lg bg-white shadow-sm">
                        <div class="border-b px-5 py-3 font-semibold text-gray-800">أعضاء عليهم مستحقات</div>
                        <div v-if="!debtors.length" class="px-5 py-6 text-sm text-gray-500">لا يوجد</div>
                        <ul v-else class="divide-y">
                            <li v-for="row in debtors" :key="row.member_id" class="flex items-center justify-between px-5 py-3 text-sm">
                                <Link :href="route('members.show', row.member_id)" class="font-medium text-gray-900">{{ row.name }}</Link>
                                <span class="rounded-full bg-red-100 px-2 py-1 text-xs text-red-800">{{ row.balance.toLocaleString() }} {{ currency }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
