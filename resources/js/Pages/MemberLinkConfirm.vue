<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ gym_name: String, token: String });
const ar = computed(() => (usePage().props.locale || 'ar') === 'ar');
const busy = ref(false);
const L = computed(() => ar.value
    ? { title: 'تسجيل الدخول لحسابك', body: 'اضغط دخول لفتح حسابك. الرابط ده بينفع مرة واحدة بس، بعدها هتدخل بحسابك على الجهاز ده.', button: 'دخول' }
    : { title: 'Sign in to your account', body: 'Tap sign in to open your account. This link works once only; afterwards this device stays signed in.', button: 'Sign in' });

function enter() {
    if (busy.value) return;
    busy.value = true;
    router.post(`/my/${props.token}/exchange`, {}, { onFinish: () => { busy.value = false; } });
}
</script>

<template>
    <Head :title="L.title" />
    <div :dir="ar ? 'rtl' : 'ltr'" class="flex min-h-screen items-center justify-center bg-slate-50 px-6">
        <div class="card max-w-sm text-center">
            <h1 class="text-lg font-bold text-ink-900">{{ gym_name }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ L.body }}</p>
            <button @click="enter" :disabled="busy" class="btn-primary mt-4 w-full">{{ L.button }}</button>
        </div>
    </div>
</template>
