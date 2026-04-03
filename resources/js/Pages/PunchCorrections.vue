<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    corrections: Object,
});

const showForm = ref(false);

const form = useForm({
    correction_date: '',
    correction_type: 'in',
    requested_time: '',
    reason: '',
});

const submit = () => {
    form.post(route('punch-corrections.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const cancel = (id) => {
    if (confirm('確定要取消此申請嗎？')) {
        router.delete(route('punch-corrections.destroy', id), {
            preserveScroll: true,
        });
    }
};

const typeLabel = (type) => ({
    in: '上班打卡',
    out: '下班打卡',
    overtime_in: '加班開始',
    overtime_out: '加班結束',
}[type] ?? type);

const statusClass = (status) => ({
    pending: 'bg-amber-500/20 text-amber-400',
    approved: 'bg-emerald-500/20 text-emerald-400',
    rejected: 'bg-rose-500/20 text-rose-400',
}[status] ?? 'bg-slate-500/20 text-slate-400');

const statusLabel = (status) => ({
    pending: '待審核',
    approved: '已核准',
    rejected: '已駁回',
}[status] ?? status);

const formatDateTime = (dt) =>
    dt ? new Date(dt).toLocaleString('zh-TW', { dateStyle: 'short', timeStyle: 'short' }) : '-';
</script>

<template>
    <Head title="補打卡申請" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-white">補打卡申請</h1>
                    <button
                        @click="showForm = !showForm"
                        class="px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        {{ showForm ? '取消' : '+ 新增申請' }}
                    </button>
                </div>

                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="p-3 bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 rounded-lg text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Create Form -->
                <div v-if="showForm" class="bg-slate-800 rounded-xl p-6 space-y-4 border border-slate-700">
                    <h2 class="text-base font-bold text-white mb-4">填寫補打卡申請</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">申請日期</label>
                            <input
                                v-model="form.correction_date"
                                type="date"
                                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500"
                            />
                            <p v-if="form.errors.correction_date" class="text-rose-400 text-xs mt-1">{{ form.errors.correction_date }}</p>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">打卡類別</label>
                            <select
                                v-model="form.correction_type"
                                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500"
                            >
                                <option value="in">上班打卡</option>
                                <option value="out">下班打卡</option>
                                <option value="overtime_in">加班開始</option>
                                <option value="overtime_out">加班結束</option>
                            </select>
                            <p v-if="form.errors.correction_type" class="text-rose-400 text-xs mt-1">{{ form.errors.correction_type }}</p>
                        </div>

                        <!-- Time -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">申請時間 (HH:MM)</label>
                            <input
                                v-model="form.requested_time"
                                type="time"
                                class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500"
                            />
                            <p v-if="form.errors.requested_time" class="text-rose-400 text-xs mt-1">{{ form.errors.requested_time }}</p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">原因說明</label>
                        <textarea
                            v-model="form.reason"
                            rows="3"
                            placeholder="請說明補打卡原因..."
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 resize-none"
                        ></textarea>
                        <p v-if="form.errors.reason" class="text-rose-400 text-xs mt-1">{{ form.errors.reason }}</p>
                    </div>

                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        {{ form.processing ? '送出中...' : '送出申請' }}
                    </button>
                </div>

                <!-- Corrections List -->
                <div class="bg-slate-800 rounded-xl overflow-hidden border border-slate-700">
                    <div class="px-6 py-4 border-b border-slate-700">
                        <h2 class="text-base font-bold text-white">我的申請紀錄</h2>
                    </div>

                    <div v-if="corrections.data.length === 0" class="p-8 text-center text-slate-500 text-sm">
                        尚無補打卡申請紀錄
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">申請日期</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">類別</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">申請時間</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">原因</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">狀態</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">審核者</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="c in corrections.data"
                                :key="c.id"
                                class="border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="px-6 py-4 text-slate-300 font-medium">{{ c.correction_date }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ typeLabel(c.correction_type) }}</td>
                                <td class="px-6 py-4 text-slate-400">{{ formatDateTime(c.requested_time) }}</td>
                                <td class="px-6 py-4 text-slate-400 max-w-xs truncate">{{ c.reason || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold" :class="statusClass(c.status)">
                                        {{ statusLabel(c.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ c.approver?.name || '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="c.status === 'pending'"
                                        @click="cancel(c.id)"
                                        class="text-rose-400 hover:text-rose-300 text-xs font-medium"
                                    >
                                        取消
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="corrections.last_page > 1" class="px-6 py-4 border-t border-slate-700 flex items-center gap-2">
                        <a
                            v-for="link in corrections.links"
                            :key="link.label"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 rounded text-xs font-medium transition-colors"
                            :class="link.active
                                ? 'bg-violet-600 text-white'
                                : link.url
                                    ? 'text-slate-400 hover:text-white hover:bg-slate-700'
                                    : 'text-slate-600 cursor-not-allowed'"
                        ></a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
