<script setup>
import { Link } from '@inertiajs/vue3';

const features = [
    {
        text: '即時出勤打卡，GPS 定位驗證',
        icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        bg: 'bg-violet-500/20', color: 'text-violet-400'
    },
    {
        text: '線上請假申請，多層級審核流程',
        icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        bg: 'bg-emerald-500/20', color: 'text-emerald-400'
    },
    {
        text: '假勤政策靈活配置，年資自動計算',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        bg: 'bg-cyan-500/20', color: 'text-cyan-400'
    },
    {
        text: '多公司架構，數據完全隔離',
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        bg: 'bg-amber-500/20', color: 'text-amber-400'
    },
];
</script>

<template>
    <div class="min-h-screen flex" style="background: radial-gradient(circle at top right, #1e1b4b, #0a0f1e);">

        <!-- ===== LEFT BRANDING PANEL ===== -->
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-violet-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-600/10 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl pointer-events-none"></div>

            <!-- Logo -->
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-violet-500/30">
                        <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight text-white">HR<span class="text-violet-400">Portal</span></span>
                        <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">人資管理系統</div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="relative space-y-8">
                <div>
                    <h1 class="text-4xl font-black text-white leading-tight">
                        智慧人資管理<br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-cyan-400">一站式解決方案</span>
                    </h1>
                    <p class="mt-4 text-slate-400 text-lg leading-relaxed">
                        整合出勤追蹤、假勤申請、多階審核<br/>讓人力資源管理更高效、更透明
                    </p>
                </div>

                <div class="space-y-4">
                    <div v-for="feature in features" :key="feature.text" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :class="feature.bg">
                            <svg class="w-4 h-4" :class="feature.color" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon" />
                            </svg>
                        </div>
                        <span class="text-slate-300 text-sm font-medium">{{ feature.text }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative text-slate-600 text-xs">
                © {{ new Date().getFullYear() }} HR Portal. All rights reserved.
            </div>
        </div>

        <!-- ===== RIGHT LOGIN PANEL ===== -->
        <div class="flex-1 flex flex-col items-center justify-center p-6 lg:p-12">
            <!-- Mobile Logo -->
            <div class="lg:hidden mb-8 text-center">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-white">HR<span class="text-violet-400">Portal</span></span>
                </div>
            </div>

            <!-- Form Card -->
            <div class="w-full max-w-md">
                <div class="glass-card">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
<style>
@import "../../css/punch.css";
</style>
