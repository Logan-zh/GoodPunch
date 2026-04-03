<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    punches: Object,
    stats: Object,
    users: Array,
    filters: Object,
});

const filterForm = useForm({
    user_id: props.filters.user_id || '',
    date: props.filters.date || '',
});

watch(
    () => [filterForm.user_id, filterForm.date],
    () => {
        router.get(route('admin.attendance.index'), {
            user_id: filterForm.user_id,
            date: filterForm.date,
        }, { preserveState: true, replace: true });
    }
);

const handleExport = () => {
    const url = route('admin.attendance.export', {
        user_id: filterForm.user_id,
        date: filterForm.date,
    });
    window.location.href = url;
};
</script>

<template>
    <Head title="Attendance Logs" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-7xl mx-auto py-8">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ $t('attendance.title') }}</h1>
                        <p class="text-slate-400 mt-2">{{ $t('attendance.subtitle') }}</p>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="glass-card p-6 flex items-center gap-4">
                        <div class="p-3 bg-violet-500/20 rounded-xl text-violet-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">{{ $t('attendance.total_staff') }}</p>
                            <h3 class="text-2xl font-bold text-white">{{ stats.totalStaff }}</h3>
                        </div>
                    </div>
                    
                    <div class="glass-card p-6 flex items-center gap-4 border-l-4 border-emerald-500">
                        <div class="p-3 bg-emerald-500/20 rounded-xl text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">{{ $t('attendance.active_today') }}</p>
                            <h3 class="text-2xl font-bold text-white">{{ stats.punchedInToday }}</h3>
                        </div>
                    </div>

                    <div class="glass-card p-6 flex items-center gap-4">
                        <div class="p-3 bg-slate-500/20 rounded-xl text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-sm font-medium uppercase tracking-wider">{{ $t('attendance.total_punches') }}</p>
                            <h3 class="text-2xl font-bold text-white">{{ stats.totalPunchesToday }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Filters & Actions -->
                <div class="glass-card p-6 mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-4 flex-1">
                        <div class="w-full md:w-64">
                            <select 
                                v-model="filterForm.user_id"
                                class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 focus:ring-2 focus:ring-violet-500"
                            >
                                <option value="">{{ $t('attendance.all_staff') }}</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        <div class="w-full md:w-64">
                            <input 
                                v-model="filterForm.date"
                                type="date" 
                                class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 focus:ring-2 focus:ring-violet-500"
                            >
                        </div>
                    </div>
                    <button 
                        @click="handleExport"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-6 rounded-lg transition-all flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ $t('attendance.export_excel') }}
                    </button>
                </div>

                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-6 py-4">{{ $t('attendance.user') }}</th>
                                <th class="px-6 py-4">{{ $t('leave.type') }}</th>
                                <th class="px-6 py-4">{{ $t('dashboard.time') }}</th>
                                <th class="px-6 py-4">{{ $t('attendance.location') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr v-for="punch in punches.data" :key="punch.id" class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white">{{ punch.user.name }}</div>
                                    <div class="text-xs text-slate-500">{{ punch.user.email }}</div>
                                </td>
                                <td class="px-6 py-4 uppercase font-bold text-xs">
                                    <span :class="punch.type === 'in' ? 'text-emerald-400' : 'text-violet-400'">
                                        {{ punch.type === 'in' ? $t('attendance.check_in') : $t('attendance.check_out') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    {{ new Date(punch.punch_time).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-xs font-mono">
                                    <template v-if="punch.latitude && punch.longitude">
                                        {{ punch.latitude.toFixed(4) }}, {{ punch.longitude.toFixed(4) }}
                                    </template>
                                    <span v-else class="text-slate-600">{{ $t('attendance.no_data') }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
