<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);
const gym = computed(() => page.props.gym);
const can = (permission) => user.value?.permissions?.includes(permission);
const flash = computed(() => page.props.flash);

const links = computed(() => [
    { href: 'dashboard', label: 'الرئيسية', show: can('reports.view') },
    { href: 'members.index', label: 'الأعضاء', show: can('members.manage') },
    { href: 'plans.index', label: 'الباقات', show: can('plans.manage') },
    { href: 'subscriptions.index', label: 'الاشتراكات', show: can('subscriptions.manage') },
    { href: 'payments.index', label: 'المدفوعات', show: can('payments.manage') },
    { href: 'checkins.index', label: 'تسجيل الحضور', show: can('checkins.manage') },
    { href: 'staff.index', label: 'الفريق', show: can('staff.manage') },
    { href: 'settings.edit', label: 'الإعدادات', show: can('gym.manage') },
].filter((l) => l.show));
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-xl font-bold text-emerald-700">قوّة</span>
                            <span v-if="gym" class="text-sm text-gray-500">{{ gym.name }}</span>
                        </div>
                        <div class="hidden space-x-6 space-x-reverse sm:-ms-10 sm:me-10 sm:flex">
                            <Link
                                v-for="link in links"
                                :key="link.href"
                                :href="route(link.href)"
                                class="inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium"
                                :class="route().current(link.href + '*')
                                    ? 'border-emerald-600 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            >
                                {{ link.label }}
                            </Link>
                        </div>
                    </div>

                    <div class="hidden items-center gap-4 sm:flex">
                        <span class="text-sm text-gray-600">{{ user.name }}</span>
                        <Link :href="route('profile.edit')" class="text-sm text-gray-500 hover:text-gray-700">حسابي</Link>
                        <Link :href="route('logout')" method="post" as="button" class="text-sm text-gray-500 hover:text-gray-700">
                            خروج
                        </Link>
                    </div>

                    <div class="-ms-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="rounded-md p-2 text-gray-400 hover:bg-gray-100"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-show="showingNavigationDropdown" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="route(link.href)"
                        class="block border-r-4 py-2 pe-4 ps-3 text-base font-medium"
                        :class="route().current(link.href + '*')
                            ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                            : 'border-transparent text-gray-600 hover:bg-gray-50'"
                    >
                        {{ link.label }}
                    </Link>
                </div>
                <div class="border-t border-gray-200 pb-1 pt-4">
                    <div class="px-4 text-base font-medium text-gray-800">{{ user.name }}</div>
                    <div class="mt-3 space-y-1">
                        <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-gray-600">حسابي</Link>
                        <Link :href="route('logout')" method="post" as="button" class="block w-full px-4 py-2 text-start text-sm text-gray-600">خروج</Link>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <div v-if="flash?.success" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">{{ flash.success }}</div>
            </div>
            <div v-if="$page.props.errors && Object.keys($page.props.errors).length" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">
                    <div v-for="(error, key) in $page.props.errors" :key="key">{{ error }}</div>
                </div>
            </div>
            <slot />
        </main>
    </div>
</template>
