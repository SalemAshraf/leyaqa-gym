<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ member: Object, subscriptions: Array, checkIns: Array, plans: Array, currency: String });

const editing = ref(false);
const editForm = useForm({
    name: props.member.name, phone: props.member.phone, email: props.member.email ?? '',
    gender: props.member.gender ?? '', birthdate: props.member.birthdate ?? '',
    notes: props.member.notes ?? '', status: props.member.status,
});

const paymentForm = useForm({ subscription_id: '', amount: '', method: 'cash', note: '' });
const subForm = useForm({ plan_id: '', starts_at: new Date().toISOString().slice(0, 10), price: '' });
const showSubForm = ref(false);

function submitSubscription() {
    subForm.transform((data) => ({ ...data, member_id: props.member.id, price: data.price || undefined }))
        .post(route('subscriptions.store'), { onSuccess: () => { showSubForm.value = false; subForm.reset(); } });
}
const payFor = ref(null);

function saveEdit() {
    editForm.put(route('members.update', props.member.id), { onSuccess: () => (editing.value = false) });
}

function openPayment(sub) {
    payFor.value = sub.id;
    paymentForm.subscription_id = sub.id;
    paymentForm.amount = sub.balance > 0 ? sub.balance : '';
}

function submitPayment() {
    paymentForm.post(route('payments.store'), { onSuccess: () => { payFor.value = null; paymentForm.reset(); } });
}

function act(action, subId, data = {}) {
    router.post(route(`subscriptions.${action}`, subId), data);
}

const statusLabel = (s) => ({ active: 'ساري', expired: 'منتهي', frozen: 'مجمّد', cancelled: 'ملغي', upcoming: 'لم يبدأ' }[s] ?? s);
const freezeDays = ref({});
</script>

<template>
    <Head :title="member.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">{{ member.name }}</h2>
                <span v-if="member.balance > 0" class="rounded-full bg-red-100 px-3 py-1 text-sm text-red-800">
                    عليه {{ member.balance.toLocaleString() }} {{ currency }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">البيانات</h3>
                        <button @click="editing = !editing" class="text-sm text-emerald-700">تعديل</button>
                    </div>
                    <dl v-if="!editing" class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div><dt class="text-gray-500">الموبايل</dt><dd dir="ltr">{{ member.phone }}</dd></div>
                        <div><dt class="text-gray-500">البريد</dt><dd dir="ltr">{{ member.email ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">انضم</dt><dd>{{ member.joined_at }}</dd></div>
                        <div><dt class="text-gray-500">الحالة</dt><dd>{{ member.status === 'active' ? 'نشط' : 'غير نشط' }}</dd></div>
                        <div v-if="member.notes" class="col-span-full"><dt class="text-gray-500">ملاحظات</dt><dd>{{ member.notes }}</dd></div>
                    </dl>
                    <form v-else @submit.prevent="saveEdit" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <input v-model="editForm.name" class="rounded-md border-gray-300" placeholder="الاسم" />
                        <input v-model="editForm.phone" class="rounded-md border-gray-300" dir="ltr" placeholder="الموبايل" />
                        <input v-model="editForm.email" class="rounded-md border-gray-300" dir="ltr" placeholder="البريد" />
                        <select v-model="editForm.gender" class="rounded-md border-gray-300">
                            <option value="">النوع</option><option value="male">ذكر</option><option value="female">أنثى</option>
                        </select>
                        <input v-model="editForm.birthdate" type="date" class="rounded-md border-gray-300" />
                        <select v-model="editForm.status" class="rounded-md border-gray-300">
                            <option value="active">نشط</option><option value="inactive">غير نشط</option>
                        </select>
                        <input v-model="editForm.notes" class="rounded-md border-gray-300 sm:col-span-3" placeholder="ملاحظات" />
                        <div class="flex gap-2 sm:col-span-3">
                            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm text-white">حفظ</button>
                            <button type="button" @click="editing = false" class="rounded-md bg-gray-100 px-4 py-2 text-sm">إلغاء</button>
                        </div>
                    </form>
                    <div class="mt-4 border-t pt-3 text-xs text-gray-500">
                        رابط بطاقة العضو (QR token): <span dir="ltr">{{ member.qr_token }}</span>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b px-5 py-3">
                        <span class="font-semibold text-gray-800">الاشتراكات</span>
                        <button @click="showSubForm = !showSubForm" class="rounded-md bg-emerald-600 px-3 py-1 text-sm text-white">+ اشتراك جديد</button>
                    </div>
                    <form v-if="showSubForm" @submit.prevent="submitSubscription" class="flex flex-wrap items-center gap-2 border-b bg-gray-50 px-5 py-3">
                        <select v-model="subForm.plan_id" class="rounded border-gray-300 text-sm" required>
                            <option value="" disabled>اختار الباقة</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">
                                {{ p.name }} — {{ p.type === 'duration' ? p.duration_days + ' يوم' : p.sessions_count + ' حصة' }} — {{ Number(p.price).toLocaleString() }}
                            </option>
                        </select>
                        <input v-model="subForm.starts_at" type="date" class="rounded border-gray-300 text-sm" required />
                        <input v-model="subForm.price" type="number" step="0.01" min="0" placeholder="سعر خاص (اختياري)" class="w-36 rounded border-gray-300 text-sm" />
                        <button type="submit" :disabled="subForm.processing" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">حفظ</button>
                    </form>
                    <div v-if="!subscriptions.length" class="px-5 py-6 text-sm text-gray-500">لا يوجد اشتراكات</div>
                    <div v-for="s in subscriptions" :key="s.id" class="border-b px-5 py-4 last:border-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-medium text-gray-900">{{ s.plan }}</span>
                                <span class="me-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs">{{ statusLabel(s.status) }}</span>
                                <div class="mt-1 text-sm text-gray-500">
                                    {{ s.starts_at }} ← {{ s.ends_at ?? 'بدون تاريخ انتهاء' }}
                                    <span v-if="s.sessions_remaining !== null"> · باقي {{ s.sessions_remaining }} حصة</span>
                                </div>
                            </div>
                            <div class="text-sm">
                                <span class="text-gray-500">{{ s.paid.toLocaleString() }} / {{ s.price.toLocaleString() }} {{ currency }}</span>
                                <span v-if="s.balance > 0" class="ms-2 text-red-600">متبقي {{ s.balance.toLocaleString() }}</span>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-sm">
                            <button v-if="s.balance > 0 && s.status !== 'cancelled'" @click="openPayment(s)" class="rounded bg-emerald-50 px-3 py-1 text-emerald-700">تسجيل دفعة</button>
                            <template v-if="s.status === 'active'">
                                <input v-model.number="freezeDays[s.id]" type="number" min="1" :max="s.freeze_days_allowed - s.freeze_days_used" class="w-20 rounded border-gray-300 py-1 text-sm" placeholder="أيام" />
                                <button @click="act('freeze', s.id, { days: freezeDays[s.id] })" :disabled="!freezeDays[s.id]" class="rounded bg-sky-50 px-3 py-1 text-sky-700 disabled:opacity-40">
                                    تجميد (متاح {{ s.freeze_days_allowed - s.freeze_days_used }})
                                </button>
                                <button @click="act('renew', s.id)" class="rounded bg-gray-100 px-3 py-1 text-gray-700">تجديد</button>
                                <button @click="act('cancel', s.id)" class="rounded bg-red-50 px-3 py-1 text-red-700">إلغاء</button>
                            </template>
                            <button v-if="s.status === 'frozen'" @click="act('unfreeze', s.id)" class="rounded bg-sky-600 px-3 py-1 text-white">إلغاء التجميد</button>
                            <button v-if="s.status === 'expired'" @click="act('renew', s.id)" class="rounded bg-emerald-600 px-3 py-1 text-white">تجديد الاشتراك</button>
                        </div>
                        <form v-if="payFor === s.id" @submit.prevent="submitPayment" class="mt-3 flex flex-wrap items-center gap-2 rounded bg-gray-50 p-3">
                            <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" class="w-32 rounded border-gray-300 py-1 text-sm" placeholder="المبلغ" />
                            <select v-model="paymentForm.method" class="rounded border-gray-300 py-1 text-sm">
                                <option value="cash">كاش</option><option value="card">بطاقة</option>
                                <option value="wallet">محفظة</option><option value="bank_transfer">تحويل</option><option value="other">أخرى</option>
                            </select>
                            <input v-model="paymentForm.note" class="rounded border-gray-300 py-1 text-sm" placeholder="ملاحظة" />
                            <button type="submit" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">حفظ الدفعة</button>
                        </form>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                    <div class="border-b px-5 py-3 font-semibold text-gray-800">آخر الحضور</div>
                    <div v-if="!checkIns.length" class="px-5 py-6 text-sm text-gray-500">لا يوجد</div>
                    <ul v-else class="divide-y text-sm">
                        <li v-for="(c, i) in checkIns" :key="i" class="flex justify-between px-5 py-2">
                            <span dir="ltr">{{ c.checked_in_at }}</span>
                            <span :class="c.result === 'allowed' ? 'text-emerald-700' : 'text-red-600'">
                                {{ c.result === 'allowed' ? 'دخل' : 'اترفض' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
