<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ payments: Object, currency: String });

const methodLabel = (m) => ({ cash: 'كاش', card: 'بطاقة', wallet: 'محفظة', bank_transfer: 'تحويل', other: 'أخرى' }[m] ?? m);

function remove(id) {
    if (confirm('حذف الدفعة؟ لا يمكن التراجع.')) {
        router.delete(route('payments.destroy', id));
    }
}
</script>

<template>
    <Head title="المدفوعات" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">المدفوعات</h2></template>
        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                                <th class="px-4 py-3 text-start font-medium">العضو</th>
                                <th class="px-4 py-3 text-start font-medium">المبلغ</th>
                                <th class="px-4 py-3 text-start font-medium">الطريقة</th>
                                <th class="px-4 py-3 text-start font-medium">ملاحظة</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in payments.data" :key="p.id">
                                <td class="px-4 py-3" dir="ltr">{{ p.paid_at }}</td>
                                <td class="px-4 py-3 font-medium">{{ p.member }}</td>
                                <td class="px-4 py-3">{{ p.amount.toLocaleString() }} {{ currency }}</td>
                                <td class="px-4 py-3">{{ methodLabel(p.method) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ p.note ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <button v-if="p.can_delete" @click="remove(p.id)" class="text-red-600">حذف</button>
                                </td>
                            </tr>
                            <tr v-if="!payments.data.length"><td colspan="6" class="px-4 py-8 text-center text-gray-500">لا يوجد مدفوعات بعد</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex gap-1">
                    <Link v-for="link in payments.links" :key="link.label" :href="link.url ?? '#'" v-html="link.label"
                        class="rounded px-3 py-1 text-sm" :class="link.active ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600'" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
