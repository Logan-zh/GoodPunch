<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    records: Array,
    users:   Array,
    year:    Number,
    month:   Number,
    filters: Object,
});

// Index records by user_id for O(1) lookup
const recordMap = computed(() => {
    const map = {};
    props.records.forEach(r => { map[r.user_id] = r; });
    return map;
});

const calcForm = useForm({ year: props.year, month: props.month });

const navigate = (delta) => {
    let y = props.year, m = props.month + delta;
    if (m < 1)  { m = 12; y--; }
    if (m > 12) { m = 1;  y++; }
    router.get(route('admin.payroll.index'), { year: y, month: m }, { preserveState: false });
};

const calculate = () => {
    calcForm.year  = props.year;
    calcForm.month = props.month;
    calcForm.post(route('admin.payroll.calculate'));
};

const confirm = (record) => {
    router.patch(route('admin.payroll.confirm', record.id), {}, { preserveScroll: true });
};

const openSlip = (record) => {
    window.open(route('admin.payroll.admin-slip', record.id), '_blank');
};

const fmt = (val) => {
    if (val === null || val === undefined) return '-';
    return 'NT$' + Number(val).toLocaleString('zh-TW', { maximumFractionDigits: 0 });
};

const totals = computed(() => props.records.reduce((acc, r) => {
    acc.gross += Number(r.gross_pay || 0);
    acc.net   += Number(r.net_pay   || 0);
    return acc;
}, { gross: 0, net: 0 }));

const MONTHS = ['一','二','三','四','五','六','七','八','九','十','十一','十二'];
</script>

<template>
    <Head title="薪資管理" />
    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-7xl mx-auto py-8">

                <!-- Header -->
                <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-white">薪資管理</h1>
                        <p class="text-slate-400 mt-1">{{ year }} 年 {{ MONTHS[month - 1] }} 月份薪資</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a :href="route('admin.payroll.salary-structures')"
                           class="bg-slate-700 hover:bg-slate-600 text-white font-medium py-2 px-4 rounded-lg transition-all text-sm">
                            薪資結構設定
                        </a>
                        <button @click="calculate" :disabled="calcForm.processing"
                                class="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white font-bold py-2 px-5 rounded-lg transition-all flex items-center gap-2">
                            <svg v-if="!calcForm.processing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ calcForm.processing ? '結算中…' : '⚡ 一鍵結算' }}
                        </button>
                    </div>
                </div>

                <!-- Month Navigator -->
                <div class="glass-card p-4 mb-6 flex items-center justify-center gap-6">
                    <button @click="navigate(-1)"
                            class="text-slate-300 hover:text-white transition-colors px-4 py-1.5 rounded-lg hover:bg-slate-700 text-sm">
                        ‹ 上個月
                    </button>
                    <span class="text-white font-bold text-lg">{{ year }} 年 {{ month }} 月</span>
                    <button @click="navigate(1)"
                            class="text-slate-300 hover:text-white transition-colors px-4 py-1.5 rounded-lg hover:bg-slate-700 text-sm">
                        下個月 ›
                    </button>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success"
                     class="mb-4 p-4 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Table -->
                <div class="glass-card overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-4 py-4">員工</th>
                                <th class="px-4 py-4 text-right">本薪</th>
                                <th class="px-4 py-4 text-right">津貼/獎金</th>
                                <th class="px-4 py-4 text-right">加班費</th>
                                <th class="px-4 py-4 text-right">扣款合計</th>
                                <th class="px-4 py-4 text-right">應發薪資</th>
                                <th class="px-4 py-4 text-right">實發薪資</th>
                                <th class="px-4 py-4 text-center">狀態</th>
                                <th class="px-4 py-4 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr v-for="user in users" :key="user.id"
                                class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-white">{{ user.name }}</div>
                                    <div class="text-xs text-slate-500">{{ [user.employee_id, user.position].filter(Boolean).join(' · ') }}</div>
                                </td>

                                <template v-if="recordMap[user.id]">
                                    <td class="px-4 py-3 text-right font-mono">{{ fmt(recordMap[user.id].base_salary) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-emerald-400">
                                        {{ fmt(Number(recordMap[user.id].meal_allowance || 0) + Number(recordMap[user.id].perfect_attendance_bonus || 0)) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-blue-400">{{ fmt(recordMap[user.id].overtime_pay) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-rose-400">
                                        {{ fmt(Number(recordMap[user.id].late_deduction || 0) + Number(recordMap[user.id].leave_deduction || 0) + Number(recordMap[user.id].labor_insurance || 0) + Number(recordMap[user.id].health_insurance || 0)) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">{{ fmt(recordMap[user.id].gross_pay) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-400">{{ fmt(recordMap[user.id].net_pay) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-xs font-bold uppercase"
                                              :class="recordMap[user.id].status === 'confirmed'
                                                  ? 'bg-emerald-500/20 text-emerald-400'
                                                  : 'bg-slate-500/20 text-slate-400'">
                                            {{ recordMap[user.id].status === 'confirmed' ? '已確認' : '草稿' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-3">
                                        <button v-if="recordMap[user.id].status !== 'confirmed'"
                                                @click="confirm(recordMap[user.id])"
                                                class="text-xs text-emerald-400 hover:text-emerald-300 transition-colors font-medium">
                                            確認
                                        </button>
                                        <button @click="openSlip(recordMap[user.id])"
                                                class="text-xs text-violet-400 hover:text-violet-300 transition-colors font-medium">
                                            薪資單
                                        </button>
                                    </td>
                                </template>

                                <template v-else>
                                    <td v-for="_ in 6" class="px-4 py-3 text-right text-slate-600 text-xs">—</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-xs bg-slate-700/40 text-slate-500">未結算</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600 text-xs">—</td>
                                </template>
                            </tr>
                        </tbody>

                        <!-- Summary Footer -->
                        <tfoot v-if="records.length > 0">
                            <tr class="bg-slate-800/60 font-bold text-white border-t-2 border-slate-600">
                                <td class="px-4 py-3 text-slate-300 text-sm">合計（{{ records.length }} 人）</td>
                                <td colspan="4" class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right font-mono">{{ fmt(totals.gross) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-400">{{ fmt(totals.net) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div v-if="users.length === 0" class="py-16 text-center text-slate-500">
                        尚無員工資料
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
