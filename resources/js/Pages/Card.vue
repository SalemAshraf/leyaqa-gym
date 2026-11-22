<script setup>
import Badge from '@/Components/Badge.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({ member: Object, gym: Object, subscription: Object, token: String });

const ar = computed(() => (props.gym.locale || 'ar') === 'ar');
const dir = computed(() => (ar.value ? 'rtl' : 'ltr'));
const labels = computed(() => ar.value
    ? { badge: 'بطاقة العضو', show: 'ورّي البطاقة دي عند الباب', noSub: 'لا يوجد اشتراك حالي', days: 'باقي {n} يوم', sessions: 'باقي {n} حصة', s: { active: 'ساري', expired: 'منتهي', frozen: 'مجمّد', cancelled: 'ملغي', upcoming: 'لم يبدأ' } }
    : { badge: 'Member card', show: 'Show this card at the door', noSub: 'No current subscription', days: '{n} days left', sessions: '{n} sessions left', s: { active: 'Active', expired: 'Expired', frozen: 'Frozen', cancelled: 'Cancelled', upcoming: 'Not started' } });

const tones = { active: 'green', expired: 'slate', frozen: 'blue', cancelled: 'rose', upcoming: 'amber' };
const qr = ref('');
onMounted(async () => {
    qr.value = await QRCode.toDataURL(props.token, { width: 560, margin: 2, color: { dark: '#101828', light: '#ffffff' } });
});
</script>

<template>
    <Head :title="labels.badge">
        <meta name="theme-color" content="#0a0a0a">
    </Head>
    <div :dir="dir" class="flex min-h-screen items-center justify-center bg-ink-900 p-6">
        <div class="w-full max-w-sm rounded-3xl bg-white p-8 text-center shadow-2xl">
            <img src="/icon.svg" class="mx-auto h-12 w-12 rounded-2xl" alt="" />
            <p class="mt-3 text-sm font-medium text-slate-400">{{ gym.name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-ink-900">{{ member.name }}</h1>

            <div class="mt-4 flex items-center justify-center gap-2">
                <Badge v-if="subscription" :tone="tones[subscription.status]">{{ labels.s[subscription.status] }}</Badge>
                <span v-if="subscription && subscription.days_left !== null" class="text-xs text-slate-400">{{ labels.days.replace('{n}', subscription.days_left) }}</span>
                <span v-else-if="subscription && subscription.sessions_remaining !== null" class="text-xs text-slate-400">{{ labels.sessions.replace('{n}', subscription.sessions_remaining) }}</span>
                <Badge v-else tone="rose">{{ labels.noSub }}</Badge>
            </div>

            <div class="mt-6 rounded-2xl border-2 border-slate-100 p-4">
                <img v-if="qr" :src="qr" :alt="labels.badge" class="mx-auto w-full max-w-[260px]" />
            </div>
            <p class="mt-4 text-sm text-slate-400">{{ labels.show }}</p>
        </div>
    </div>
</template>
