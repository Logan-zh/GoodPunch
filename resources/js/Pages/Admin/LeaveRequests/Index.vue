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
});

const reviewingRequest = ref(null);

const form = useForm({
    status: 'approved',
    comment: '',
});

const submitApproval = () => {
    form.patch(route('admin.leave-management.update', reviewingRequest.value.id), {
        onSuccess: () => {
            reviewingRequest.value = null;
            form.reset();
        },
    });
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
</script>

<template>
    <Head title="Leave Approvals" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                {{ $t('leave.approvals') }}
            </h2>
        </template>

        <div class="punch-container py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="glass-card p-6">
                    <h3 class="text-lg font-medium text-white mb-6">Staff Requests Awaiting Your Review</h3>
                    
                    <div class="overflow-hidden">
                        <table class="w-full text-left font-sans">
                            <thead class="bg-slate-800/50 text-slate-300 text-xs uppercase font-bold">
                                <tr>
                                    <th class="px-6 py-3">{{ $t('attendance.user') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.type') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.period') }} & {{ $t('leave.duration') }}</th>
                                    <th class="px-6 py-3">{{ $t('leave.status') }}</th>
                                    <th class="px-6 py-3 text-right">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <tr v-for="req in requests" :key="req.id" class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-white">{{ req.user.name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ req.user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="capitalize text-emerald-400 font-medium">{{ req.type }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs">{{ formatDate(req.start_at) }} - {{ formatDate(req.end_at) }}</div>
                                        <div class="text-xs text-slate-500 font-bold mt-1">{{ req.hours }} {{ $t('leave.hours') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex gap-1">
                                                <div v-for="(step, i) in req.steps" :key="step.id" 
                                                     class="w-2 h-2 rounded-full"
                                                     :class="{
                                                         'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]': step.status === 'approved',
                                                         'bg-rose-500': step.status === 'rejected',
                                                         'bg-amber-500 border border-white animate-pulse': step.status === 'pending' && req.current_step === i,
                                                         'bg-slate-700': step.status === 'pending' && req.current_step !== i
                                                     }"
                                                ></div>
                                            </div>
                                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">
                                                Step {{ req.current_step + 1 }} of {{ req.steps.length }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button 
                                            @click="reviewingRequest = req"
                                            class="bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold py-1.5 px-4 rounded transition-all"
                                        >
                                            Review
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="requests.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                                        {{ $t('dashboard.no_records') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Review Modal -->
                <div v-if="reviewingRequest" class="fixed inset-0 bg-slate-900/90 backdrop-blur-md z-50 flex items-center justify-center p-4">
                    <div class="glass-card max-w-lg w-full p-8 border border-slate-700 relative shadow-2xl">
                        <button @click="reviewingRequest = null" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <h2 class="text-2xl font-bold text-white mb-2">{{ $t('leave.approvals') }}</h2>
                        <p class="text-slate-400 mb-6 border-b border-slate-800 pb-4">{{ reviewingRequest.user.name }}</p>

                        <div class="grid grid-cols-2 gap-6 mb-6 text-sm">
                            <div>
                                <label class="text-slate-500 block uppercase text-[10px] font-bold">{{ $t('leave.type') }}</label>
                                <span class="capitalize text-white text-lg">{{ reviewingRequest.type }}</span>
                            </div>
                            <div>
                                <label class="text-slate-500 block uppercase text-[10px] font-bold">{{ $t('leave.duration') }}</label>
                                <span class="text-violet-400 text-lg font-bold">{{ reviewingRequest.hours }} {{ $t('leave.hours') }}</span>
                            </div>
                            <div class="col-span-2">
                                <label class="text-slate-500 block uppercase text-[10px] font-bold">{{ $t('leave.reason') }}</label>
                                <div class="bg-slate-900/50 p-3 rounded mt-1 border border-slate-800 italic text-slate-300">
                                    {{ reviewingRequest.reason || 'No reason provided.' }}
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submitApproval" class="space-y-4">
                            <div>
                                <InputLabel value="Decision" class="text-white" />
                                <div class="flex gap-4 mt-2">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" value="approved" v-model="form.status" class="hidden peer" />
                                        <div class="text-center p-3 rounded-lg border-2 border-slate-800 text-slate-400 peer-checked:border-emerald-500 peer-checked:text-emerald-500 peer-checked:bg-emerald-500/10 transition-all font-bold">
                                            {{ $t('leave.approve') }}
                                        </div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" value="rejected" v-model="form.status" class="hidden peer" />
                                        <div class="text-center p-3 rounded-lg border-2 border-slate-800 text-slate-400 peer-checked:border-rose-500 peer-checked:text-rose-500 peer-checked:bg-rose-500/10 transition-all font-bold">
                                            {{ $t('leave.reject') }}
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Approval/Rejection Comment" />
                                <textarea 
                                    v-model="form.comment" 
                                    class="mt-1 block w-full bg-slate-900 border-slate-700 text-white rounded-md h-24"
                                    placeholder="Add a comment for the employee..."
                                ></textarea>
                            </div>

                            <PrimaryButton class="w-full justify-center !py-4 text-lg" :disabled="form.processing">
                                {{ $t('common.confirm') }}
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
