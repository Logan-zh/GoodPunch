<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
});

const showPanel   = ref(false);
const editingUser = ref(null);

const form = useForm({
    user_id:                   '',
    base_salary:               '',
    meal_allowance:            '',
    perfect_attendance_bonus:  '',
    labor_insurance_employee:  '',
    health_insurance_employee: '',
    overtime_hourly_rate:      '',
    effective_from:            '',
});

const openEdit = (user) => {
    editingUser.value = user;
    const s = user.salary_structure;
    form.user_id                   = user.id;
    form.base_salary               = s?.base_salary               ?? '';
    form.meal_allowance            = s?.meal_allowance            ?? '';
    form.perfect_attendance_bonus  = s?.perfect_attendance_bonus  ?? '';
    form.labor_insurance_employee  = s?.labor_insurance_employee  ?? '';
    form.health_insurance_employee = s?.health_insurance_employee ?? '';
    form.overtime_hourly_rate      = s?.overtime_hourly_rate      ?? '';
    form.effective_from            = s?.effective_from            ?? '';
    form.clearErrors();
    showPanel.value = true;
};

const closePanel = () => {
    showPanel.value   = false;
    editingUser.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('admin.payroll.upsert-salary-structure'), {
        onSuccess: closePanel,
    });
};

const fmt = (val) => {
    if (!val && val !== 0) return '—';
    return 'NT$' + Number(val).toLocaleString('zh-TW', { maximumFractionDigits: 0 });
};
</script>

<template>
    <Head title="薪資結構設定" />
    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-5xl mx-auto py-8">

                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">薪資結構設定</h1>
                        <p class="text-slate-400 mt-1">設定每位員工的薪資結構</p>
                    </div>
                    <a :href="route('admin.payroll.index')"
                       class="text-slate-400 hover:text-white transition-colors text-sm">
                        ← 返回薪資管理
                    </a>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success"
                     class="mb-4 p-4 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-sm">
                    {{ $page.props.flash.success }}
                </div>

                <div class="glass-card overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-5 py-4">員工</th>
                                <th class="px-5 py-4 text-right">本薪</th>
                                <th class="px-5 py-4 text-right">伙食津貼</th>
                                <th class="px-5 py-4 text-right">全勤獎金</th>
                                <th class="px-5 py-4 text-right">勞保</th>
                                <th class="px-5 py-4 text-right">健保</th>
                                <th class="px-5 py-4 text-right">加班時薪</th>
                                <th class="px-5 py-4 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr v-for="user in users" :key="user.id"
                                class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-white">{{ user.name }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ [user.employee_id, user.position].filter(Boolean).join(' · ') }}
                                    </div>
                                </td>

                                <template v-if="user.salary_structure">
                                    <td class="px-5 py-3 text-right font-mono">{{ fmt(user.salary_structure.base_salary) }}</td>
                                    <td class="px-5 py-3 text-right font-mono">{{ fmt(user.salary_structure.meal_allowance) }}</td>
                                    <td class="px-5 py-3 text-right font-mono">{{ fmt(user.salary_structure.perfect_attendance_bonus) }}</td>
                                    <td class="px-5 py-3 text-right font-mono">{{ fmt(user.salary_structure.labor_insurance_employee) }}</td>
                                    <td class="px-5 py-3 text-right font-mono">{{ fmt(user.salary_structure.health_insurance_employee) }}</td>
                                    <td class="px-5 py-3 text-right font-mono">{{ fmt(user.salary_structure.overtime_hourly_rate) }}</td>
                                </template>

                                <template v-else>
                                    <td colspan="6" class="px-5 py-3 text-center">
                                        <span class="px-2 py-1 bg-amber-500/20 text-amber-400 text-xs rounded font-bold">
                                            尚未設定
                                        </span>
                                    </td>
                                </template>

                                <td class="px-5 py-3 text-right">
                                    <button @click="openEdit(user)"
                                            class="text-violet-400 hover:text-violet-300 transition-colors text-xs font-medium">
                                        {{ user.salary_structure ? '編輯' : '設定' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="users.length === 0" class="py-16 text-center text-slate-500">
                        尚無員工資料
                    </div>
                </div>

            </div>
        </div>

        <!-- Edit / Create Panel -->
        <div v-if="showPanel"
             class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="glass-card max-w-md w-full p-8 relative">
                <button @click="closePanel" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h2 class="text-xl font-bold text-white mb-1">薪資結構</h2>
                <p class="text-slate-400 text-sm mb-6">{{ editingUser?.name }}</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">本薪 *</label>
                            <input v-model="form.base_salary" type="number" step="1" min="0" required
                                   class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                            <p v-if="form.errors.base_salary" class="text-rose-400 text-xs mt-1">{{ form.errors.base_salary }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">伙食津貼</label>
                            <input v-model="form.meal_allowance" type="number" step="1" min="0"
                                   class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">全勤獎金</label>
                            <input v-model="form.perfect_attendance_bonus" type="number" step="1" min="0"
                                   class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">加班時薪</label>
                            <input v-model="form.overtime_hourly_rate" type="number" step="0.01" min="0"
                                   class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">勞保自負額</label>
                            <input v-model="form.labor_insurance_employee" type="number" step="1" min="0"
                                   class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">健保自負額</label>
                            <input v-model="form.health_insurance_employee" type="number" step="1" min="0"
                                   class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">生效日</label>
                        <input v-model="form.effective_from" type="date"
                               class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-violet-500">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closePanel"
                                class="flex-1 bg-slate-800 hover:bg-slate-700 text-white py-2.5 rounded-lg text-sm transition-all">
                            取消
                        </button>
                        <button type="submit" :disabled="form.processing"
                                class="flex-1 bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-bold transition-all">
                            {{ form.processing ? '儲存中…' : '儲存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
