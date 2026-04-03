<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    corrections: Object,
});

const reviewing = ref(null);

const form = useForm({
    status: 'approved',
    comment: '',
});

const openReview = (correction) => {
    reviewing.value = correction;
    form.reset();
    form.status = 'approved';
};

const submitReview = () => {
    form.patch(route('admin.punch-corrections.update', reviewing.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewing.value = null;
            form.reset();
        },
    });
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
    <Head title="補打卡審核" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-white">補打卡審核</h1>
                </div>

                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="p-3 bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 rounded-lg text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Table -->
                <div class="bg-slate-800 rounded-xl overflow-hidden border border-slate-700">
                    <div class="px-6 py-4 border-b border-slate-700">
                        <h2 class="text-base font-bold text-white">部屬補打卡申請（近30天）</h2>
                    </div>

                    <div v-if="corrections.data.length === 0" class="p-8 text-center text-slate-500 text-sm">
                        目前無待審核的補打卡申請
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-700">
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">員工</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">申請日期</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">類別</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">申請時間</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">原因</th>
                                <th class="text-left px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">狀態</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="c in corrections.data"
                                :key="c.id"
                                class="border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white">{{ c.user?.name }}</div>
                                    <div class="text-xs text-slate-500">{{ c.user?.employee_id }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-300 font-medium">{{ c.correction_date }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ typeLabel(c.correction_type) }}</td>
                                <td class="px-6 py-4 text-slate-400">{{ formatDateTime(c.requested_time) }}</td>
                                <td class="px-6 py-4 text-slate-400 max-w-xs truncate">{{ c.reason || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold" :class="statusClass(c.status)">
                                        {{ statusLabel(c.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="c.status === 'pending'"
                                        @click="openReview(c)"
                                        class="px-3 py-1 bg-violet-600 hover:bg-violet-500 text-white text-xs font-medium rounded transition-colors"
                                    >
                                        審核
                                    </button>
                                    <span v-else class="text-xs text-slate-600">
                                        {{ c.approver?.name ?? '-' }}
                                    </span>
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

        <!-- Review Modal -->
        <Teleport to="body">
            <div v-if="reviewing" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="reviewing = null">
                <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md shadow-xl border border-slate-700 space-y-4">
                    <h3 class="text-base font-bold text-white">審核補打卡申請</h3>

                    <!-- Summary -->
                    <div class="bg-slate-700/50 rounded-lg p-4 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">員工</span>
                            <span class="text-white font-medium">{{ reviewing.user?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">日期</span>
                            <span class="text-white">{{ reviewing.correction_date }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">類別</span>
                            <span class="text-white">{{ typeLabel(reviewing.correction_type) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">申請時間</span>
                            <span class="text-white">{{ formatDateTime(reviewing.requested_time) }}</span>
                        </div>
                        <div v-if="reviewing.reason" class="flex justify-between">
                            <span class="text-slate-400">原因</span>
                            <span class="text-white text-right max-w-[60%]">{{ reviewing.reason }}</span>
                        </div>
                    </div>

                    <!-- Decision -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">審核決定</label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.status" value="approved" class="accent-emerald-500" />
                                <span class="text-emerald-400 text-sm font-medium">核准</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.status" value="rejected" class="accent-rose-500" />
                                <span class="text-rose-400 text-sm font-medium">駁回</span>
                            </label>
                        </div>
                    </div>

                    <!-- Comment -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">審核備註（選填）</label>
                        <textarea
                            v-model="form.comment"
                            rows="2"
                            placeholder="可輸入審核說明..."
                            class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="submitReview"
                            :disabled="form.processing"
                            class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
                            :class="form.status === 'approved'
                                ? 'bg-emerald-600 hover:bg-emerald-500 text-white'
                                : 'bg-rose-600 hover:bg-rose-500 text-white'"
                        >
                            {{ form.processing ? '處理中...' : (form.status === 'approved' ? '確認核准' : '確認駁回') }}
                        </button>
                        <button
                            @click="reviewing = null"
                            class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium rounded-lg transition-colors"
                        >
                            取消
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
