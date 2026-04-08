<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sidebarOpen = ref(false);
const alert = ref(page.props.alert ?? null);

const user = computed(() => page.props.auth.user);
const isManager = computed(() => ['admin', 'manager'].includes(user.value?.role));
const isAdmin = computed(() => user.value?.role === 'admin');

const closeSidebar = () => { sidebarOpen.value = false; };
const dismissAlert = () => { alert.value = null; };

watch(
    () => page.props.alert,
    (value) => {
        alert.value = value ?? null;
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex h-screen overflow-hidden" style="background: radial-gradient(ellipse at top right, #1e1b4b 0%, #0a0f1e 60%);">

        <!-- Mobile overlay -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-black/70 backdrop-blur-sm lg:hidden"
                @click="closeSidebar"
            ></div>
        </Transition>

        <!-- ========== SIDEBAR ========== -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed lg:static inset-y-0 left-0 z-30 w-64 flex flex-col bg-slate-950/90 backdrop-blur-xl border-r border-white/5 transition-transform duration-300 ease-in-out lg:translate-x-0 flex-shrink-0"
        >
            <!-- Brand -->
            <div class="flex items-center gap-3 h-16 px-5 border-b border-white/5 flex-shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-violet-500/25 flex-shrink-0">
                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-white tracking-wide">HR Portal</div>
                    <div class="text-[10px] text-slate-500 truncate">{{ user?.company?.name ?? '人資管理系統' }}</div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-5 px-3">

                <!-- Employee -->
                <div class="mb-5">
                    <p class="px-3 mb-2 text-[10px] font-bold text-slate-600 uppercase tracking-widest">{{ $t('nav.employee') ?? '個人' }}</p>

                    <Link
                        :href="route('dashboard')"
                        @click="closeSidebar"
                        :class="route().current('dashboard') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        {{ $t('nav.dashboard') }}
                    </Link>

                    <Link
                        :href="route('leave-requests.index')"
                        @click="closeSidebar"
                        :class="route().current('leave-requests.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ $t('nav.leave_requests') }}
                    </Link>
                </div>

                <!-- Management -->
                <div v-if="isManager" class="mb-5">
                    <p class="px-3 mb-2 text-[10px] font-bold text-slate-600 uppercase tracking-widest">{{ $t('nav.management') ?? '管理' }}</p>

                    <Link
                        :href="route('admin.users.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.users.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        {{ $t('nav.users') }}
                    </Link>

                    <Link
                        :href="route('admin.attendance.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.attendance.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $t('nav.attendance') }}
                    </Link>

                    <Link
                        :href="route('admin.leave-management.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.leave-management.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $t('nav.leave_approvals') ?? $t('nav.leave_requests') }}
                    </Link>

                    <Link
                        :href="route('admin.leave-policies.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.leave-policies.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        {{ $t('nav.leave_policies') }}
                    </Link>

                    <Link
                        :href="route('admin.punch-corrections.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.punch-corrections.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        補打卡審核
                    </Link>

                    <Link
                        :href="route('admin.payroll.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.payroll.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        薪資管理
                    </Link>

                    <Link
                        :href="route('admin.departments.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.departments.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                        部門管理
                    </Link>

                    <Link
                        :href="route('admin.settings.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.settings.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        打卡範圍設定
                    </Link>
                </div>

                <!-- System Admin -->
                <div v-if="isAdmin">
                    <p class="px-3 mb-2 text-[10px] font-bold text-slate-600 uppercase tracking-widest">系統</p>

                    <Link
                        :href="route('admin.companies.index')"
                        @click="closeSidebar"
                        :class="route().current('admin.companies.*') ? 'bg-violet-600/20 text-violet-300 border-violet-500/70' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 mb-0.5 border-l-2"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        企業管理
                    </Link>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="flex-shrink-0 border-t border-white/5 p-3 space-y-1">
                <!-- Language -->
                <div class="flex gap-1 mb-2 px-1">
                    <a :href="route('locale', 'en')"
                        :class="$page.props.locale === 'en' ? 'bg-violet-600/20 text-violet-300' : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'"
                        class="flex-1 text-center text-[11px] font-semibold py-1.5 rounded-md transition-colors cursor-pointer">EN</a>
                    <a :href="route('locale', 'zh_TW')"
                        :class="$page.props.locale === 'zh_TW' ? 'bg-violet-600/20 text-violet-300' : 'text-slate-500 hover:text-slate-300 hover:bg-white/5'"
                        class="flex-1 text-center text-[11px] font-semibold py-1.5 rounded-md transition-colors cursor-pointer">繁中</a>
                </div>

                <!-- User -->
                <Link :href="route('profile.edit')" class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-white/5 transition-colors group">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-600/40 to-indigo-600/40 border border-violet-500/30 flex items-center justify-center text-violet-300 text-sm font-bold flex-shrink-0">
                        {{ user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-slate-300 truncate group-hover:text-white transition-colors">{{ user?.name }}</div>
                        <div class="text-[10px] text-slate-600 truncate">{{ user?.email }}</div>
                    </div>
                </Link>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 text-sm transition-all"
                >
                    <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    {{ $t('nav.logout') }}
                </Link>
            </div>
        </aside>

        <!-- ========== MAIN AREA ========== -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <Transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="alert" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 px-4 backdrop-blur-sm" @click.self="dismissAlert">
                    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-950 px-6 py-7 text-white shadow-2xl shadow-black/40 sm:px-8">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border border-amber-400/30 bg-amber-500/10 text-amber-300 shadow-[0_0_35px_rgba(251,191,36,0.18)]">
                            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.66 18h16.68a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z" />
                            </svg>
                        </div>

                        <div class="mt-5 text-center">
                            <h3 class="text-2xl font-bold tracking-tight text-white">
                                {{ alert.title ?? '通知' }}
                            </h3>
                            <p class="mt-3 text-sm leading-6 text-slate-300">
                                {{ alert.message }}
                            </p>
                        </div>

                        <div class="mt-7 flex justify-center">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-amber-400 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-300"
                                @click="dismissAlert"
                            >
                                知道了
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Top Header -->
            <header class="flex items-center h-16 px-4 sm:px-6 bg-slate-950/50 border-b border-white/5 backdrop-blur-sm flex-shrink-0">
                <!-- Hamburger (mobile) -->
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden mr-3 p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5 transition-colors"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <!-- Role Badge -->
                <span v-if="user?.role === 'admin'" class="px-2 py-1 bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-md text-[10px] font-bold uppercase tracking-wider">Super Admin</span>
                <span v-else-if="user?.role === 'manager'" class="px-2 py-1 bg-amber-500/20 text-amber-400 border border-amber-500/20 rounded-md text-[10px] font-bold uppercase tracking-wider">Manager</span>
                <span v-else class="px-2 py-1 bg-slate-700/40 text-slate-400 border border-slate-700/40 rounded-md text-[10px] font-bold uppercase tracking-wider">Employee</span>

                <!-- Right side info -->
                <div class="ml-auto flex items-center gap-3 text-sm text-slate-500">
                    <span class="hidden sm:inline font-medium text-slate-400">{{ user?.name }}</span>
                </div>
            </header>

            <!-- Scrollable Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
