<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useI18n } from '@/lib/i18n';

const page = usePage();
const { t, locale } = useI18n();
watchEffect(() => {
    const l = page.props.locale || 'ar';
    document.documentElement.lang = l;
    document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
});
const user = computed(() => page.props.auth.user);
const gym = computed(() => page.props.gym);
const open = ref(false);
const collapsed = ref(typeof localStorage !== 'undefined' && localStorage.getItem('nav_collapsed') === '1');
const toggleNav = () => { collapsed.value = !collapsed.value; localStorage.setItem('nav_collapsed', collapsed.value ? '1' : '0'); };
const can = (p) => (user.value?.permissions || []).includes(p);
const groups = computed(() => {
    const g = [];
    const daily = [];
    if (can('reports.view')) daily.push({ r: 'dashboard', k: 'dashboard', icon: 'dashboard' });
    if (can('checkins.manage')) daily.push({ r: 'checkins.index', k: 'checkins', icon: 'checkins', match: 'checkins.*' });
    if (can('members.manage')) daily.push({ r: 'members.index', k: 'members', icon: 'members', match: 'members.*' });
    if (daily.length) g.push({ k: 'g_daily', items: daily });
    const money = [];
    if (can('subscriptions.manage')) money.push({ r: 'subscriptions.index', k: 'subscriptions', icon: 'subscriptions', match: 'subscriptions.*' });
    if (can('payments.manage')) money.push({ r: 'payments.index', k: 'payments', icon: 'payments', match: 'payments.*' });
    if (can('plans.manage')) money.push({ r: 'plans.index', k: 'plans', icon: 'plans', match: 'plans.*' });
    if (money.length) g.push({ k: 'g_money', items: money });
    const admin = [];
    if (can('staff.manage')) admin.push({ r: 'staff.index', k: 'staff', icon: 'staff', match: 'staff.*' });
    if (can('gym.manage')) admin.push({ r: 'settings.edit', k: 'settings', icon: 'settings', match: 'settings.*' });
    if (admin.length) g.push({ k: 'g_admin', items: admin });
    return g;
});
const active = (n) => route().current(n.match || n.r);
const q = ref('');
const goSearch = () => router.get(route('members.index'), { search: q.value || undefined });
const flash = computed(() => page.props.flash?.success);
const initials = computed(() => (user.value.name || '?').trim().split(/\s+/).slice(0, 2).map((x) => x[0]).join('').toUpperCase());
const toggleLocale = () => router.post(route('locale'), { locale: locale.value === 'ar' ? 'en' : 'ar' }, { preserveScroll: true, preserveState: true });
</script>

<template>
    <div class="min-h-screen bg-canvas lg:flex">
        <aside class="fixed inset-y-0 z-30 flex w-72 flex-col border-e border-slate-200 bg-white shadow-xl transition-[transform,width] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none"
            :class="[open ? 'translate-x-0' : 'ltr:-translate-x-full rtl:translate-x-full ltr:lg:translate-x-0 rtl:lg:translate-x-0', collapsed ? 'lg:w-[76px]' : 'lg:w-64']" style="inset-inline-start: 0">
            <div class="flex h-16 shrink-0 items-center justify-between gap-2 px-4 lg:h-[72px]" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3 text-lg font-bold text-ink-900" :title="t('app')">
                    <img src="/icon.svg" alt="" class="h-10 w-10 shrink-0 rounded-xl shadow" /><span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ gym?.name || t('app') }}</span>
                </Link>
                <button class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="close" @click="open = false"><Icon name="close" :size="20" /></button>
            </div>
            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-4" :class="collapsed ? 'lg:px-2' : ''">
                <div v-for="(g, gi) in groups" :key="g.k" :class="gi && collapsed ? 'lg:mt-2 lg:border-t lg:border-slate-100 lg:pt-2' : ''">
                    <p class="mb-1 mt-4 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400" :class="collapsed ? 'lg:hidden' : ''">{{ t(g.k) }}</p>
                    <Link v-for="n in g.items" :key="n.r" :href="route(n.r)" @click="open = false" :title="t(n.k)"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                        :class="[collapsed ? 'lg:justify-center lg:px-0' : '', active(n) ? 'bg-brand-300 text-ink-900 shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-ink-900']">
                        <Icon :name="n.icon" :size="20" class="shrink-0" :class="active(n) ? 'text-ink-900' : 'text-slate-400 group-hover:text-ink-800'" /><span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ t(n.k) }}</span>
                    </Link>
                </div>
            </nav>
            <button class="mx-3 mb-1 hidden items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-ink-900 lg:flex" :class="collapsed ? 'lg:justify-center lg:px-0' : ''" :aria-expanded="!collapsed" :aria-label="collapsed ? t('nav_expand') : t('nav_collapse')" :title="collapsed ? t('nav_expand') : t('nav_collapse')" @click="toggleNav">
                <Icon name="menu" :size="20" class="shrink-0" /><span :class="collapsed ? 'lg:hidden' : ''">{{ t('nav_collapse') }}</span>
            </button>
            <Link :href="route('profile.edit')" class="m-3 flex items-center gap-3 rounded-xl bg-slate-50 p-3 hover:bg-slate-100" :class="collapsed ? 'lg:m-2 lg:justify-center lg:p-2' : ''" :title="user.name" @click="open = false">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink-900 text-sm font-bold text-brand-300">{{ initials }}</span>
                <span class="min-w-0" :class="collapsed ? 'lg:hidden' : ''"><span class="block truncate text-sm font-semibold text-ink-900">{{ user.name }}</span><span class="block text-xs text-slate-500">{{ t('r_' + (user.role || 'owner')) }}</span></span>
            </Link>
        </aside>
        <div v-if="open" class="fixed inset-0 z-20 bg-ink-900/40 backdrop-blur-[1px] lg:hidden" @click="open = false" />
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-canvas/90 px-4 backdrop-blur lg:h-[72px] lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <button class="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="menu" @click="open = !open"><Icon name="menu" :size="20" /></button>
                    <p class="hidden truncate text-sm font-semibold text-ink-800 md:block">{{ gym?.name }}</p>
                    <form v-if="can('members.manage')" class="hidden max-w-md flex-1 md:block" @submit.prevent="goSearch">
                        <input v-model="q" type="search" class="input !rounded-xl !bg-white" :placeholder="t('search_ph')" :aria-label="t('search')" />
                    </form>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn-secondary btn-sm gap-1.5" @click="toggleLocale"><Icon name="globe" :size="15" />{{ t('language') }}</button>
                    <Link :href="route('logout')" method="post" as="button" class="btn-secondary btn-sm gap-1.5" :aria-label="t('logout')"><Icon name="logout" :size="15" class="rtl:-scale-x-100" /><span class="hidden xl:inline">{{ t('logout') }}</span></Link>
                </div>
            </header>
            <p v-if="flash" class="mx-4 mt-4 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm text-brand-800 lg:mx-8">{{ flash }}</p>
            <div v-if="$page.props.errors && Object.keys($page.props.errors).length" class="mx-4 mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-800 lg:mx-8">
                <div v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</div>
            </div>
            <main class="mx-auto w-full max-w-[1500px] flex-1 p-4 lg:p-8"><slot /></main>
        </div>
    </div>
</template>
