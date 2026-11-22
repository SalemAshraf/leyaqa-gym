<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({ today: Array });
const page = usePage();
const result = computed(() => page.props.flash?.checkin);

const form = useForm({ qr_token: '', member_id: '' });
const input = ref('');

const reasonLabel = (r) => ({
    expired: 'الاشتراك منتهي', frozen: 'الاشتراك مجمّد', cancelled: 'الاشتراك ملغي',
    no_sessions_left: 'الحصص خلصت', no_subscription: 'لا يوجد اشتراك', upcoming: 'الاشتراك لم يبدأ',
}[r] ?? r);

function submit() {
    const value = input.value.trim();
    if (!value) return;
    if (/^\d+$/.test(value)) {
        form.member_id = value; form.qr_token = '';
    } else {
        form.qr_token = value; form.member_id = '';
    }
    form.post(route('checkins.store'), {
        preserveScroll: true,
        onSuccess: () => { input.value = ''; },
        onError: () => { input.value = ''; },
    });
}
</script>

<template>
    <Head title="تسجيل الحضور" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800">تسجيل الحضور</h2></template>
        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="rounded-lg bg-white p-6 shadow-sm">
                    <label class="block text-sm font-medium text-gray-700">امسح QR أو اكتب رقم العضو</label>
                    <input
                        v-model="input" autofocus dir="ltr"
                        class="mt-2 w-full rounded-md border-gray-300 text-center text-2xl tracking-widest"
                        placeholder="QR / #"
                        @keyup.enter="submit"
                    />
                    <button type="submit" class="mt-4 w-full rounded-md bg-emerald-600 py-3 text-lg font-medium text-white">تسجيل دخول</button>
                </form>

                <div v-if="result" class="rounded-lg p-5 text-center shadow-sm" :class="result.allowed ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'">
                    <div class="text-2xl font-bold">{{ result.allowed ? 'أهلًا ' + result.member : 'ممنوع الدخول' }}</div>
                    <div v-if="!result.allowed" class="mt-1">{{ reasonLabel(result.reason) }}</div>
                    <div v-if="result.allowed && result.balance > 0" class="mt-1 text-sm">عليه مستحقات: {{ result.balance.toLocaleString() }}</div>
                    <div v-if="result.allowed && result.days_left !== null && result.days_left <= 7" class="mt-1 text-sm">
                        اشتراكه ينتهي بعد {{ result.days_left }} يوم
                    </div>
                    <div v-if="result.allowed && result.sessions_remaining !== null" class="mt-1 text-sm">
                        باقي {{ result.sessions_remaining }} حصة
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                    <div class="border-b px-5 py-3 font-semibold text-gray-800">حضور اليوم ({{ today.length }})</div>
                    <div v-if="!today.length" class="px-5 py-6 text-sm text-gray-500">لا يوجد حضور اليوم بعد</div>
                    <ul v-else class="divide-y text-sm">
                        <li v-for="c in today" :key="c.id" class="flex items-center justify-between px-5 py-2">
                            <span class="font-medium">{{ c.member }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-gray-500" dir="ltr">{{ c.checked_in_at.split(' ')[1] }}</span>
                                <span :class="c.result === 'allowed' ? 'text-emerald-700' : 'text-red-600'">
                                    {{ c.result === 'allowed' ? 'دخل' : 'اترفض' }}
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
