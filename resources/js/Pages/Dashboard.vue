<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    punches: Object,
    lastPunch: Object,
    leaveEntitlements: Object,
    adminStats: Object,
});

const currentTime = ref(new Date());
let timer;

onMounted(() => { timer = setInterval(() => { currentTime.value = new Date(); }, 1000); });
onUnmounted(() => { clearInterval(timer); });

const formatTime = (d) => d.toLocaleTimeString('en-US', { hour12: false });
const formatDate = (d) => new Intl.DateTimeFormat('zh-TW', { dateStyle: 'long' }).format(d);

const form = useForm({ type: '', latitude: null, longitude: null });
const isPunching = ref(false);

const submitPunch = (type) => {
    isPunching.value = true;
    const send = (lat, lng) => {
        form.type = type;
        form.latitude = lat;
        form.longitude = lng;
        form.post(route('punch.store'), {
            preserveScroll: true,
            onFinish: () => { isPunching.value = false; },
        });
    };
    if (!navigator.geolocation) { send(null, null); return; }
    navigator.geolocation.getCurrentPosition(
        (p) => send(p.coords.latitude, p.coords.longitude),
        () => send(null, null),
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
    );
};

const hour = computed(() => new Date().getHours());
const greeting = computed(() => {
    if (hour.value < 12) return '早安';
    if (hour.value < 18) return '午安';
    return '晚安';
});
</script>

<template>
    <Head title="儀表板" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- Page Header -->
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ greeting }}，{{ $page.props.auth.user.name }} 👋
                        </h1>
                        <p class="text-slate-400 text-sm mt-1">{{ formatDate(currentTime) }}</p>
                    </div>
                    <div v-if="lastPunch && lastPunch.type === 'in'" class="status-badge status-in">
                        <span class="dot" style="background-color:#10b981;"></span> 上班中
                    </div>
                    <div v-else class="status-badge status-out">
                        <span class="dot" style="background-color:#ef4444;"></span> 已下班
                    </div>
                </div>

                <!-- ===== ADMIN / MANAGER KPI SECTION ===== -->
                <template v-if="adminStats">
                    <div>
                        <h2 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-3">今日概覽</h2>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                            <div class="glass-card !p-5 !mb-0">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">員工總數</p>
                                        <p class="text-3xl font-bold text-white">{{ adminStats.total_staff }}</p>
                                    </div>
                                    <div class="w-10 h-10 bg-violet-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-600 mt-2">已登記員工</p>
                            </div>

                            <div class="glass-card !p-5 !mb-0 border-b-2 border-emerald-500">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">今日出勤</p>
                                        <p class="text-3xl font-bold text-emerald-400">{{ adminStats.present_today }}</p>
                                    </div>
                                    <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-600 mt-2">今天已打卡</p>
                            </div>

                            <div class="glass-card !p-5 !mb-0 border-b-2 border-amber-500">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">今日請假</p>
                                        <p class="text-3xl font-bold text-amber-400">{{ adminStats.on_leave_today }}</p>
                                    </div>
                                    <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-600 mt-2">核准假勤中</p>
                            </div>

                            <div class="glass-card !p-5 !mb-0 border-b-2 border-rose-500">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">待審核</p>
                                        <p class="text-3xl font-bold text-rose-400">{{ adminStats.pending_approvals }}</p>
                                    </div>
                                    <div class="w-10 h-10 bg-rose-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-600 mt-2">假單待批核</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div>
                        <h2 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-3">快速操作</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <Link :href="route('admin.users.index')" class="glass-card !p-4 !mb-0 flex flex-col items-center gap-2 hover:border-violet-500/50 hover:bg-violet-500/5 transition-all group text-center">
                                <div class="w-10 h-10 bg-violet-500/20 rounded-xl flex items-center justify-center group-hover:bg-violet-500/30 transition-colors">
                                    <svg class="w-5 h-5 text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <span class="text-xs text-slate-400 group-hover:text-white font-medium transition-colors">員工管理</span>
                            </Link>
                            <Link :href="route('admin.attendance.index')" class="glass-card !p-4 !mb-0 flex flex-col items-center gap-2 hover:border-cyan-500/50 hover:bg-cyan-500/5 transition-all group text-center">
                                <div class="w-10 h-10 bg-cyan-500/20 rounded-xl flex items-center justify-center group-hover:bg-cyan-500/30 transition-colors">
                                    <svg class="w-5 h-5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <span class="text-xs text-slate-400 group-hover:text-white font-medium transition-colors">出勤紀錄</span>
                            </Link>
                            <Link :href="route('admin.leave-management.index')" class="glass-card !p-4 !mb-0 flex flex-col items-center gap-2 hover:border-amber-500/50 hover:bg-amber-500/5 transition-all group text-center">
                                <div class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center group-hover:bg-amber-500/30 transition-colors">
                                    <svg class="w-5 h-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <span class="text-xs text-slate-400 group-hover:text-white font-medium transition-colors">假單審核</span>
                            </Link>
                            <Link :href="route('admin.leave-policies.index')" class="glass-card !p-4 !mb-0 flex flex-col items-center gap-2 hover:border-emerald-500/50 hover:bg-emerald-500/5 transition-all group text-center">
                                <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center group-hover:bg-emerald-500/30 transition-colors">
                                    <svg class="w-5 h-5 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                </div>
                                <span class="text-xs text-slate-400 group-hover:text-white font-medium transition-colors">假勤政策</span>
                            </Link>
                        </div>
                    </div>

                    <hr class="border-white/5" />
                </template>

                <!-- ===== EMPLOYEE SECTION ===== -->
                <div>
                    <h2 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-3">我的出勤</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                        <!-- Clock + Punch Buttons -->
                        <div class="lg:col-span-2 glass-card !mb-0 text-center">
                            <div class="text-slate-400 text-sm font-medium mb-1">{{ formatDate(currentTime) }}</div>
                            <div class="clock-display">{{ formatTime(currentTime) }}</div>

                            <div class="grid grid-cols-2 gap-4 mt-6">
                                <button
                                    @click="submitPunch('in')"
                                    :disabled="(lastPunch && lastPunch.type === 'in') || isPunching"
                                    class="btn-punch btn-in"
                                    :class="{ 'opacity-50 cursor-not-allowed': (lastPunch && lastPunch.type === 'in') || isPunching }"
                                >
                                    <svg v-if="!isPunching" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                                    <span v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white inline-block"></span>
                                    {{ isPunching ? '處理中...' : $t('dashboard.punch_in') }}
                                </button>
                                <button
                                    @click="submitPunch('out')"
                                    :disabled="!lastPunch || lastPunch.type === 'out' || isPunching"
                                    class="btn-punch btn-out"
                                    :class="{ 'opacity-50 cursor-not-allowed': !lastPunch || lastPunch.type === 'out' || isPunching }"
                                >
                                    <svg v-if="!isPunching" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    <span v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white inline-block"></span>
                                    {{ isPunching ? '處理中...' : $t('dashboard.punch_out') }}
                                </button>
                            </div>

                            <div v-if="$page.props.flash?.success" class="mt-4 p-3 bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 rounded-lg text-sm font-medium">
                                {{ $page.props.flash.success }}
                            </div>
                            <div v-if="$page.props.flash?.error" class="mt-4 p-3 bg-rose-500/20 border border-rose-500/50 text-rose-400 rounded-lg text-sm font-medium">
                                {{ $page.props.flash.error }}
                            </div>

                            <!-- Overtime Punch -->
                            <div class="mt-5 pt-5 border-t border-white/5">
                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-3">加班打卡</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        @click="submitPunch('overtime_in')"
                                        :disabled="isPunching"
                                        class="btn-punch"
                                        style="background: linear-gradient(135deg, #7c3aed, #5b21b6);"
                                        :class="{ 'opacity-50 cursor-not-allowed': isPunching }"
                                    >
                                        <svg v-if="!isPunching" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline-block"></span>
                                        加班開始
                                    </button>
                                    <button
                                        @click="submitPunch('overtime_out')"
                                        :disabled="isPunching"
                                        class="btn-punch"
                                        style="background: linear-gradient(135deg, #4f46e5, #3730a3);"
                                        :class="{ 'opacity-50 cursor-not-allowed': isPunching }"
                                    >
                                        <svg v-if="!isPunching" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                                        <span v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-white inline-block"></span>
                                        加班結束
                                    </button>
                                </div>
                            </div>

                            <!-- Punch Correction Link -->
                            <div class="mt-4 text-center">
                                <Link :href="route('punch-corrections.index')" class="text-xs text-slate-500 hover:text-violet-400 transition-colors underline underline-offset-2">
                                    補打卡申請
                                </Link>
                            </div>
                        </div>

                        <!-- Leave Balance -->
                        <div class="flex flex-col gap-3" v-if="leaveEntitlements">
                            <div
                                v-for="lt in [
                                    { key: 'annual',   label: '特休假', accent: 'emerald' },
                                    { key: 'personal', label: '事假',   accent: 'blue'    },
                                    { key: 'sick',     label: '病假',   accent: 'rose'    },
                                ]"
                                :key="lt.key"
                                class="glass-card !p-4 !mb-0"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">{{ lt.label }}</div>
                                    <div class="text-xs text-slate-500">{{ leaveEntitlements[lt.key] ?? 0 }} 天</div>
                                </div>
                                <div class="flex items-baseline gap-1 mb-2">
                                    <span
                                        class="text-2xl font-bold"
                                        :class="{
                                            'text-emerald-400': lt.accent === 'emerald',
                                            'text-blue-400':    lt.accent === 'blue',
                                            'text-rose-400':    lt.accent === 'rose',
                                        }"
                                    >{{ leaveEntitlements[lt.key + '_remaining'] ?? 0 }}</span>
                                    <span class="text-xs text-slate-500">天剩餘</span>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-1.5">
                                    <div
                                        class="h-1.5 rounded-full transition-all duration-500"
                                        :class="{
                                            'bg-emerald-500': lt.accent === 'emerald',
                                            'bg-blue-500':    lt.accent === 'blue',
                                            'bg-rose-500':    lt.accent === 'rose',
                                        }"
                                        :style="{
                                            width: leaveEntitlements[lt.key]
                                                ? Math.round((leaveEntitlements[lt.key + '_remaining'] / leaveEntitlements[lt.key]) * 100) + '%'
                                                : '0%'
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== RECENT ACTIVITY ===== -->
                <div class="glass-card !mb-0">
                    <h3 class="text-base font-bold mb-4 flex items-center gap-2 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $t('dashboard.recent_activity') }}
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>{{ $t('dashboard.date') }}</th>
                                    <th>{{ $t('dashboard.time') }}</th>
                                    <th>{{ $t('dashboard.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="punch in punches.data" :key="punch.id" class="history-row">
                                    <td class="font-medium text-slate-300">{{ new Date(punch.punch_time).toLocaleDateString('zh-TW') }}</td>
                                    <td class="text-slate-400">{{ new Date(punch.punch_time).toLocaleTimeString('zh-TW') }}</td>
                                    <td>
                                        <span class="px-2 py-1 rounded text-xs font-bold uppercase"
                                              :class="{
                                                  'text-emerald-400 bg-emerald-400/10': punch.type === 'in',
                                                  'text-rose-400 bg-rose-400/10': punch.type === 'out',
                                                  'text-violet-400 bg-violet-400/10': punch.type === 'overtime_in',
                                                  'text-indigo-400 bg-indigo-400/10': punch.type === 'overtime_out',
                                              }">
                                            {{ { in: $t('dashboard.punch_in'), out: $t('dashboard.punch_out'), overtime_in: '加班開始', overtime_out: '加班結束' }[punch.type] ?? punch.type }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="punches.data.length === 0">
                                    <td colspan="3" class="text-center py-8 text-slate-500">{{ $t('dashboard.no_records') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
