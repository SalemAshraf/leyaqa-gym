<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ subscriptions: Object, currency: String });

const statusLabel = (s) => ({ active: 'ساري', expired: 'منتهي', frozen: 'مجمّد', cancelled: 'ملغي', upcoming: 'لم يبدأ' }[s] ?? s);
const statusClass = (s) => ({
    active: 'bg-emerald-100 text-emerald-800', expired: 'bg-gray-200 text-gray-700',
    frozen: 'bg-sky-100 text-sky-800', cancelled: 'bg-red-100 text-red-800', upcoming: 'bg-amber-100 text-amber-800',
}[s] ?? 'bg-gray-100 text-gray-700');
</script>

<template>
    <Head title="الاشتراكات" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">الاشتراكات</h2></template>
        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-500">إنشاء اشتراك جديد يتم من صفحة العضو.</p>
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">العضو</th>
                                <th class="px-4 py-3 text-start font-medium">الباقة</th>
                                <th class="px-4 py-3 text-start font-medium">من</th>
                                <th class="px-4 py-3 text-start font-medium">إلى</th>
                                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                                <th class="px-4 py-3 text-start font-medium">متبقي الدفع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="s in subscriptions.data" :key="s.id">
                                <td class="px-4 py-3">
                                    <Link :href="route('members.show', s.member.id)" class="font-medium text-emerald-700">{{ s.member.name }}</Link>
                                </td>
                                <td class="px-4 py-3">{{ s.plan }}</td>
                                <td class="px-4 py-3">{{ s.starts_at }}</td>
                                <td class="px-4 py-3">{{ s.ends_at ?? `${s.sessions_remaining ?? 0} حصة متبقية` }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs" :class="statusClass(s.status)">{{ statusLabel(s.status) }}</span></td>
                                <td class="px-4 py-3">
                                    <span v-if="s.balance > 0" class="text-red-600">{{ s.balance.toLocaleString() }} {{ currency }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="!subscriptions.data.length"><td colspan="6" class="px-4 py-8 text-center text-gray-500">لا يوجد اشتراكات بعد</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex gap-1">
                    <Link v-for="link in subscriptions.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                        class="rounded px-3 py-1 text-sm" :class="link.active ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600'" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
