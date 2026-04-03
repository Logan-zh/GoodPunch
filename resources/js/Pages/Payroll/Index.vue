<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    records: Array,
});

const openSlip = (record) => {
    window.open(route('payroll.slip', record.id), '_blank');
};

const fmt = (val) => {
    if (!val) return 'NT$0';
    return 'NT$' + Number(val).toLocaleString('zh-TW', { maximumFractionDigits: 0 });
};

const period = (r) => `${r.year} 年 ${r.month} 月`;
</script>

<template>
    <Head title="我的薪資" />
    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-3xl mx-auto py-8">

                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-white">我的薪資</h1>
                    <p class="text-slate-400 mt-1">已確認的薪資記錄</p>
                </div>

                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-6 py-4">月份</th>
                                <th class="px-6 py-4 text-right">本薪</th>
                                <th class="px-6 py-4 text-right">實發薪資</th>
                                <th class="px-6 py-4 text-center">狀態</th>
                                <th class="px-6 py-4 text-right">薪資單</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr v-for="record in records" :key="record.id"
                                class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 font-medium text-white">{{ period(record) }}</td>
                                <td class="px-6 py-4 text-right font-mono">{{ fmt(record.base_salary) }}</td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-emerald-400">{{ fmt(record.net_pay) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs rounded font-bold uppercase">
                                        已確認
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openSlip(record)"
                                            class="text-violet-400 hover:text-violet-300 transition-colors text-sm font-medium">
                                        查看薪資單 →
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="records.length === 0" class="py-16 text-center text-slate-500">
                        目前尚無薪資記錄
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
