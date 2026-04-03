<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    requests: Array,
    leaveEntitlements: Object,
});

const isApplying = ref(false);

const form = useForm({
    type: 'annual',
    start_at: '',
    end_at: '',
    reason: '',
});

const submit = () => {
    form.post(route('leave-requests.store'), {
        onSuccess: () => {
            isApplying.value = false;
            form.reset();
        },
    });
};

const cancelRequest = (id) => {
    if (confirm('Cancel this pending request?')) {
        form.delete(route('leave-requests.destroy', id));
    }
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-500/20 text-amber-500',
        approved: 'bg-emerald-500/20 text-emerald-500',
        rejected: 'bg-rose-500/20 text-rose-500',
        cancelled: 'bg-slate-500/20 text-slate-500',
    };
    return classes[status] || 'bg-slate-500/20 text-slate-500';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString([], {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const leaveTypes = [
    {
        key: 'annual',
        label: '特休假',
        color: 'emerald',
        borderClass: 'border-emerald-500',
        barClass: 'bg-emerald-500',
        iconColor: 'text-emerald-400',
        bgColor: 'bg-emerald-500/10',
    },
    {
        key: 'sick',
        label: '病假',
        color: 'rose',
        borderClass: 'border-rose-500',
        barClass: 'bg-rose-500',
        iconColor: 'text-rose-400',
        bgColor: 'bg-rose-500/10',
    },
    {
        key: 'personal',
        label: '事假',
        color: 'blue',
        borderClass: 'border-blue-500',
        barClass: 'bg-blue-500',
        iconColor: 'text-blue-400',
        bgColor: 'bg-blue-500/10',
    },
];

const getBalanceBarColor = (remaining, total) => {
    if (!total) return 'bg-slate-600';
    const pct = remaining / total;
    if (pct > 0.5) return 'bg-emerald-500';
    if (pct > 0.2) return 'bg-amber-500';
    return 'bg-rose-500';
};

const getBalanceTextColor = (remaining, total) => {
    if (!total) return 'text-slate-400';
    const pct = remaining / total;
    if (pct > 0.5) return 'text-emerald-400';
    if (pct > 0.2) return 'text-amber-400';
    return 'text-rose-400';
};
</script>

<template>
    <Head title="Leave Requests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-white">
                    {{ $t('nav.leave_requests') }}
                </h2>
                <button 
                    @click="isApplying = true"
                    class="bg-violet-600 hover:bg-violet-500 text-white font-bold py-2 px-4 rounded-lg transition-all"
                >
                    {{ $t('leave.apply') }}
                </button>
            </div>
        </template>

        <div class="punch-container py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Leave Balances -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        v-for="lt in leaveTypes"
                        :key="lt.key"
                        class="glass-card p-6 border-l-4"
                        :class="lt.borderClass"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-slate-300 text-sm font-semibold">{{ lt.label }}</h3>
                            <span
                                class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="[lt.bgColor, lt.iconColor]"
                            >
                                {{ leaveEntitlements[lt.key] ?? 0 }} 天
                            </span>
                        </div>

                        <!-- Remaining / Total -->
                        <div class="flex items-baseline gap-2 mb-2">
                            <span
                                class="text-4xl font-bold"
                                :class="getBalanceTextColor(leaveEntitlements[lt.key + '_remaining'], leaveEntitlements[lt.key])"
                            >
                                {{ leaveEntitlements[lt.key + '_remaining'] ?? 0 }}
                            </span>
                            <span class="text-slate-500 text-sm">/ {{ leaveEntitlements[lt.key] ?? 0 }} 天剩餘</span>
                        </div>

                        <!-- Progress bar -->
                        <div class="w-full bg-slate-700 rounded-full h-2 mb-2">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                :class="getBalanceBarColor(leaveEntitlements[lt.key + '_remaining'], leaveEntitlements[lt.key])"
                                :style="{
                                    width: leaveEntitlements[lt.key]
                                        ? Math.round((leaveEntitlements[lt.key + '_remaining'] / leaveEntitlements[lt.key]) * 100) + '%'
                                        : '0%'
                                }"
                            ></div>
                        </div>

                        <!-- Used hours -->
                        <div class="text-xs text-slate-500">
                            已用 {{ ((leaveEntitlements[lt.key] ?? 0) - (leaveEntitlements[lt.key + '_remaining'] ?? 0)).toFixed(1) }} 天　·　剩餘 {{ leaveEntitlements[lt.key + '_remaining_hours'] ?? 0 }} 小時
                        </div>
                    </div>
                </div>

                <!-- Apply Modal -->
                <div v-if="isApplying" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="glass-card max-w-md w-full p-8 relative">
                         <button @click="isApplying = false" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <h2 class="text-2xl font-bold text-white mb-6">{{ $t('leave.apply') }}</h2>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel :value="$t('leave.type')" />
                                <select v-model="form.type" class="mt-1 block w-full bg-slate-900 border-slate-700 text-white rounded-md">
                                    <option value="annual">{{ $t('leave.annual') }}</option>
                                    <option value="personal">{{ $t('leave.personal') }}</option>
                                    <option value="sick">{{ $t('leave.sick') }}</option>
                                </select>
                                <InputError :message="form.errors.type" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel :value="$t('leave.start_at')" />
                                    <TextInput id="start_at" type="datetime-local" class="mt-1 block w-full" v-model="form.start_at" required />
                                </div>
                                <div>
                                    <InputLabel :value="$t('leave.end_at')" />
                                    <TextInput id="end_at" type="datetime-local" class="mt-1 block w-full" v-model="form.end_at" required />
                                </div>
                            </div>
                            <InputError :message="form.errors.start_at" />
                            <InputError :message="form.errors.end_at" />

                            <div>
                                <InputLabel :value="$t('leave.reason')" />
                                <textarea 
                                    v-model="form.reason" 
                                    class="mt-1 block w-full bg-slate-900 border-slate-700 text-white rounded-md h-24"
                                    placeholder="Explain your request..."
                                ></textarea>
                                <InputError :message="form.errors.reason" />
                            </div>

                            <div class="pt-4 flex gap-3">
                                <button type="button" @click="isApplying = false" class="flex-1 bg-slate-800 text-white py-2.5 rounded-lg">{{ $t('leave.cancel') }}</button>
                                <button type="submit" :disabled="form.processing" class="flex-1 bg-violet-600 text-white py-2.5 rounded-lg">{{ $t('leave.submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- History Table -->
                <div class="glass-card p-6">
                    <h3 class="text-lg font-medium text-white mb-4">{{ $t('leave.history') }}</h3>
                    <div class="overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-800/50 text-slate-300 text-xs uppercase font-bold">
                                    <th class="px-6 py-3">{{ $t('leave.type') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.period') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.duration') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.status') }}</th>
                                    <th class="px-6 py-3 text-right">{{ $t('dashboard.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <tr v-for="req in requests" :key="req.id" class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-white capitalize">{{ req.type }}</div>
                                        <div class="text-[10px] text-slate-500 overflow-hidden whitespace-nowrap text-ellipsis max-w-[150px]">{{ req.reason }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <div>{{ formatDate(req.start_at) }}</div>
                                        <div class="text-slate-500">to {{ formatDate(req.end_at) }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-violet-400">{{ req.hours }}</span> <span class="text-xs text-slate-500">{{ $t('leave.hours') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <span :class="getStatusClass(req.status)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase inline-block w-fit">
                                                {{ $t('status.' + req.status) }}
                                            </span>
                                            <div class="flex gap-1">
                                                <div v-for="(step, i) in req.steps" :key="step.id" 
                                                     class="w-2 h-2 rounded-full"
                                                     :class="{
                                                         'bg-emerald-500': step.status === 'approved',
                                                         'bg-rose-500': step.status === 'rejected',
                                                         'bg-amber-500': step.status === 'pending' && req.current_step === i,
                                                         'bg-slate-700': step.status === 'pending' && req.current_step !== i
                                                     }"
                                                     :title="step.step_name + ': ' + step.status"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            v-if="req.status === 'pending'"
                                            @click="cancelRequest(req.id)"
                                            class="text-rose-500 hover:text-rose-400 text-xs font-bold"
                                        >
                                            {{ $t('leave.cancel') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="requests.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">{{ $t('dashboard.no_records') }}</td>
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
