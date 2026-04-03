<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    policies: Array,
    company: Object,
    availableApprovers: Array,
    approverTypes: Array,
    holidays: { type: Array, default: () => [] },
});

const form = useForm({
    type: 'annual',
    min_years: 0,
    days: 0,
});

const companyForm = useForm({
    work_hours_per_day: props.company?.work_hours_per_day ?? 8.0,
    work_start_time: props.company?.work_start_time ?? '09:00',
    work_end_time: props.company?.work_end_time ?? '18:00',
    leave_approval_chain: props.company?.leave_approval_chain ?? [
        { type: 'supervisor', name: 'Direct Supervisor', user_id: null }
    ],
});

const holidayForm = useForm({
    holidays: [...(props.holidays ?? [])],
});
const newHolidayDate = ref('');

const addHoliday = () => {
    const d = newHolidayDate.value?.trim();
    if (d && /^\d{4}-\d{2}-\d{2}$/.test(d) && !holidayForm.holidays.includes(d)) {
        holidayForm.holidays.push(d);
        holidayForm.holidays.sort();
    }
    newHolidayDate.value = '';
};

const removeHoliday = (date) => {
    holidayForm.holidays = holidayForm.holidays.filter(d => d !== date);
};

const submitHolidays = () => {
    holidayForm.patch(route('admin.leave-policies.update-holidays'), { preserveScroll: true });
};

const submit = () => {
    form.post(route('admin.leave-policies.store'), {
        onSuccess: () => form.reset('min_years', 'days'),
    });
};

const submitCompanySettings = () => {
    companyForm.patch(route('admin.leave-policies.update-settings'), {
        preserveScroll: true,
    });
};

const addStep = () => {
    companyForm.leave_approval_chain.push({ type: 'supervisor', name: 'Next Approver', user_id: null });
};

const removeStep = (index) => {
    companyForm.leave_approval_chain.splice(index, 1);
};

const deletePolicy = (id) => {
    if (confirm('Remove this tier?')) {
        form.delete(route('admin.leave-policies.destroy', id));
    }
};

const getLabel = (type) => {
    const labels = {
        annual: 'Annual Leave (特休/年假)',
        personal: 'Personal Leave (事假)',
        sick: 'Sick Leave (病假)',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Leave Policy Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                {{ $t('settings.title') }}
            </h2>
        </template>

        <div class="punch-container py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Company General Settings -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-medium text-white mb-4">{{ $t('settings.approval_chain') }}</h3>
                    <div class="space-y-6">
                        <div class="max-w-xs">
                            <InputLabel :value="$t('settings.work_hours')" />
                            <TextInput 
                                type="number" 
                                step="0.5" 
                                v-model="companyForm.work_hours_per_day"
                                class="mt-1 block w-full"
                            />
                            <p class="text-[10px] text-slate-500 mt-1">Used to convert hours to days (e.g. 8h = 1d)</p>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-40">
                                <InputLabel :value="$t('settings.work_start_time')" />
                                <TextInput
                                    type="time"
                                    v-model="companyForm.work_start_time"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="companyForm.errors.work_start_time" />
                            </div>
                            <div class="w-40">
                                <InputLabel :value="$t('settings.work_end_time')" />
                                <TextInput
                                    type="time"
                                    v-model="companyForm.work_end_time"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="companyForm.errors.work_end_time" />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <InputLabel :value="$t('settings.approval_chain')" />
                            <div v-for="(step, index) in companyForm.leave_approval_chain" :key="index" class="flex gap-3 items-end bg-slate-900/30 p-3 rounded-lg border border-slate-800">
                                <div class="text-slate-500 font-bold self-center px-2">#{{ index + 1 }}</div>
                                <div class="flex-1">
                                    <InputLabel class="text-[10px]">{{ $t('settings.step_name') }}</InputLabel>
                                    <TextInput v-model="step.name" class="w-full text-sm !p-1.5" />
                                </div>
                                <div class="w-40">
                                    <InputLabel class="text-[10px]">{{ $t('settings.approver_type') }}</InputLabel>
                                    <select v-model="step.type" class="w-full bg-slate-900 border-slate-700 text-white rounded text-sm !p-1.5">
                                        <option v-for="t in approverTypes" :key="t.value" :value="t.value">{{ $t(t.label_key) }}</option>
                                    </select>
                                </div>
                                <div class="w-40" v-if="step.type === 'user'">
                                    <InputLabel class="text-[10px]">{{ $t('settings.select_approver') }}</InputLabel>
                                    <select v-model="step.user_id" class="w-full bg-slate-900 border-slate-700 text-white rounded text-sm !p-1.5">
                                        <option v-for="u in availableApprovers" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                                <button @click="removeStep(index)" class="text-rose-500 hover:text-white p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <button @click="addStep" class="text-xs text-violet-400 hover:text-violet-300 font-bold flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('settings.add_step') }}
                            </button>
                        </div>

                        <div class="pt-4">
                            <PrimaryButton @click="submitCompanySettings" :disabled="companyForm.processing">
                                {{ $t('settings.save') }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Add New Policy / Tier -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-medium text-white mb-4">{{ $t('settings.add_rule_title') }}</h3>
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <InputLabel for="type" :value="$t('leave.type')" />
                            <select 
                                id="type" 
                                v-model="form.type"
                                class="mt-1 block w-full bg-slate-900 border-slate-700 text-white rounded-md focus:ring-violet-500"
                            >
                                <option value="annual">{{ $t('leave.annual') }}</option>
                                <option value="personal">{{ $t('leave.personal') }}</option>
                                <option value="sick">{{ $t('leave.sick') }}</option>
                            </select>
                        </div>
                        
                        <div v-if="form.type === 'annual'">
                            <InputLabel for="min_years" value="Min. Years of Service" />
                            <TextInput id="min_years" type="number" step="0.5" class="mt-1 block w-full" v-model="form.min_years" required />
                        </div>
                        <div v-else>
                            <label class="text-slate-500 text-sm">Fixed yearly allowance</label>
                            <div class="h-10"></div>
                        </div>

                        <div>
                            <InputLabel for="days" value="Days Granted" />
                            <TextInput id="days" type="number" class="mt-1 block w-full" v-model="form.days" required />
                        </div>

                        <div>
                            <PrimaryButton :disabled="form.processing">{{ $t('users.save') }}</PrimaryButton>
                        </div>
                    </form>
                    <InputError :message="form.errors.type" />
                    <InputError :message="form.errors.min_years" />
                    <InputError :message="form.errors.days" />
                </div>

                <!-- Active Policies List -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-medium text-white mb-4">{{ $t('settings.active_rules_title') }}</h3>
                    <div class="overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-800/50 text-slate-300 text-xs uppercase font-bold">
                                <tr>
                                    <th class="px-6 py-3">{{ $t('leave.type') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.period') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.days') }}</th>
                                    <th class="px-6 py-3 text-right">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <tr v-for="policy in policies" :key="policy.id" class="text-slate-300 hover:bg-slate-800/30">
                                    <td class="px-6 py-4 font-medium">{{ getLabel(policy.type) }}</td>
                                    <td class="px-6 py-4">
                                        <span v-if="policy.type === 'annual'">After {{ policy.min_years }} years</span>
                                        <span v-else class="text-slate-500">Every Year</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-emerald-400 font-bold text-lg">{{ policy.days }}</span> {{ $t('leave.days') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            @click="deletePolicy(policy.id)"
                                            class="text-rose-500 hover:text-rose-400 text-sm font-bold"
                                        >
                                            {{ $t('users.delete') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="policies.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                        No leave rules defined yet. Please add your company's policy.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Public Holidays Management -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-medium text-white mb-1">國定假日</h3>
                    <p class="text-xs text-slate-500 mb-5">設定後，請假時數計算將自動排除這些假日。</p>

                    <!-- Add holiday -->
                    <div class="flex gap-3 mb-5">
                        <TextInput
                            type="date"
                            v-model="newHolidayDate"
                            class="w-48"
                            placeholder="YYYY-MM-DD"
                            @keydown.enter.prevent="addHoliday"
                        />
                        <button
                            type="button"
                            @click="addHoliday"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-sm font-medium transition-colors"
                        >
                            新增假日
                        </button>
                    </div>

                    <!-- Holiday chips -->
                    <div v-if="holidayForm.holidays.length > 0" class="flex flex-wrap gap-2 mb-5">
                        <span
                            v-for="date in holidayForm.holidays"
                            :key="date"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 text-slate-200 rounded-full text-sm"
                        >
                            📅 {{ date }}
                            <button
                                type="button"
                                @click="removeHoliday(date)"
                                class="text-slate-400 hover:text-rose-400 transition-colors leading-none"
                                aria-label="移除"
                            >×</button>
                        </span>
                    </div>
                    <p v-else class="text-sm text-slate-500 mb-5">尚未設定任何假日。</p>

                    <InputError :message="holidayForm.errors.holidays" />

                    <PrimaryButton @click="submitHolidays" :disabled="holidayForm.processing">
                        儲存假日設定
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
