<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    supervisors: Array,
    departments: Array,
});

const isCreating = ref(false);
const editingUser = ref(null);
const resettingPassword = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    hired_at: '',
    supervisor_id: '',
    employee_id: '',
    position: '',
    department_id: '',
});

const submit = () => {
    if (editingUser.value) {
        form.patch(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => {
                isCreating.value = false;
                editingUser.value = null;
                form.reset();
            },
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                isCreating.value = false;
                form.reset();
            },
        });
    }
};

const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

const handleResetPassword = () => {
    resetForm.post(route('admin.users.reset-password', resettingPassword.value.id), {
        onSuccess: () => {
            resettingPassword.value = null;
            resetForm.reset();
        },
    });
};

const editUser = (user) => {
    editingUser.value = user;
    isCreating.value = true;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.hired_at = user.hired_at;
    form.supervisor_id = user.supervisor_id || '';
    form.employee_id = user.employee_id || '';
    form.position = user.position || '';
    form.department_id = user.department_id || '';
    form.password = '';
    form.password_confirmation = '';
};

const confirmDelete = (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-7xl mx-auto py-8">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ $t('users.title') }}</h1>
                        <p class="text-slate-400 mt-2">{{ $t('users.subtitle') }}</p>
                    </div>
                    <button 
                        @click="isCreating = true; editingUser = null; form.reset()"
                        class="bg-violet-600 hover:bg-violet-500 text-white font-bold py-2 px-4 rounded-lg transition-all"
                    >
                        {{ $t('users.add_user') }}
                    </button>
                </div>

                <!-- Create/Edit Modal Overlay -->
                <div v-if="isCreating" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="glass-card max-w-md w-full p-8 relative">
                        <button @click="isCreating = false" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        
                        <h2 class="text-2xl font-bold text-white mb-6">{{ editingUser ? $t('users.edit') : $t('users.create') }}</h2>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.name') }}</label>
                                <input v-model="form.name" type="text" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.email') }}</label>
                                <input v-model="form.email" type="email" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.role') }}</label>
                                <select v-model="form.role" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                                    <option value="user">Staff (User)</option>
                                    <option value="manager">Enterprise Manager</option>
                                    <option v-if="$page.props.auth.user.role === 'admin'" value="admin">System Admin</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.hired_at') }}</label>
                                <input v-model="form.hired_at" type="date" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.supervisor') }}</label>
                                <select v-model="form.supervisor_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                                    <option value="">-- {{ $t('users.no_supervisor') }} --</option>
                                    <option v-for="s in supervisors" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Employee ID</label>
                                <input v-model="form.employee_id" type="text" placeholder="e.g. EMP-001" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Position / 職稱</label>
                                <input v-model="form.position" type="text" placeholder="e.g. Senior Engineer" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Department</label>
                                <select v-model="form.department_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                                    <option value="">-- No Department --</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            
                            <div v-if="!editingUser" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.password') }}</label>
                                    <input v-model="form.password" type="password" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.confirm_password') }}</label>
                                    <input v-model="form.password_confirmation" type="password" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                                </div>
                            </div>

                            <div class="pt-4 flex gap-3">
                                <button type="button" @click="isCreating = false" class="flex-1 bg-slate-800 text-white py-2.5 rounded-lg">{{ $t('leave.cancel') }}</button>
                                <button type="submit" :disabled="form.processing" class="flex-1 bg-violet-600 text-white py-2.5 rounded-lg">{{ editingUser ? $t('users.save') : $t('users.create') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reset Password Modal -->
                <div v-if="resettingPassword" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="glass-card max-w-md w-full p-8">
                        <h2 class="text-2xl font-bold text-white mb-2">{{ $t('users.reset_password') }}</h2>
                        <p class="text-slate-400 mb-6">{{ resettingPassword.name }}</p>
                        
                        <form @submit.prevent="handleResetPassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.password') }}</label>
                                <input v-model="resetForm.password" type="password" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">{{ $t('users.confirm_password') }}</label>
                                <input v-model="resetForm.password_confirmation" type="password" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                            </div>
                            <div class="pt-4 flex gap-3">
                                <button type="button" @click="resettingPassword = null" class="flex-1 bg-slate-800 text-white py-2.5 rounded-lg">{{ $t('leave.cancel') }}</button>
                                <button type="submit" :disabled="resetForm.processing" class="flex-1 bg-violet-600 text-white py-2.5 rounded-lg">{{ $t('users.reset_password') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-6 py-4">{{ $t('users.name') }}</th>
                                <th class="px-6 py-4">{{ $t('users.email') }}</th>
                                <th class="px-6 py-4">{{ $t('nav.leave_requests') }}</th>
                                <th class="px-6 py-4">{{ $t('users.role') }}</th>
                                <th class="px-6 py-4 text-right">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr v-for="user in users.data" :key="user.id" class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-white">{{ user.name }}</div>
                                    <div class="text-xs text-slate-400" v-if="user.employee_id">
                                        {{ user.employee_id }}
                                        <span v-if="user.position"> · {{ user.position }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500" v-if="user.supervisor">
                                        {{ $t('users.supervisor') }}: {{ user.supervisor.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">{{ user.email }}</div>
                                    <div class="text-xs text-slate-500" v-if="user.hired_at">
                                        {{ $t('users.hired_at') }}: {{ user.hired_at }} ({{ user.leave_entitlements.years }}y)
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2 text-[10px]">
                                        <div class="bg-emerald-500/10 text-emerald-400 p-1 rounded border border-emerald-500/20">
                                            {{ $t('leave.annual') }}: <span class="font-bold text-sm">{{ user.leave_entitlements.annual }}</span>
                                        </div>
                                        <div class="bg-blue-500/10 text-blue-400 p-1 rounded border border-blue-500/20">
                                            {{ $t('leave.personal') }}: <span class="font-bold text-sm">{{ user.leave_entitlements.personal }}</span>
                                        </div>
                                        <div class="bg-rose-500/10 text-rose-400 p-1 rounded border border-rose-500/20">
                                            {{ $t('leave.sick') }}: <span class="font-bold text-sm">{{ user.leave_entitlements.sick }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span 
                                        class="px-2 py-1 rounded text-xs font-bold uppercase"
                                        :class="{
                                            'bg-violet-500/20 text-violet-400': user.role === 'admin' || user.role === 'manager',
                                            'bg-emerald-500/20 text-emerald-400': user.role === 'user'
                                        }"
                                    >
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <button @click="resettingPassword = user" class="text-slate-400 hover:text-violet-400 transition-colors">{{ $t('users.reset_password') }}</button>
                                    <button @click="editUser(user)" class="text-slate-400 hover:text-white transition-colors">{{ $t('users.edit') }}</button>
                                    <button 
                                        @click="confirmDelete(user)" 
                                        v-if="user.id !== $page.props.auth.user.id"
                                        class="text-slate-400 hover:text-rose-400 transition-colors"
                                    >
                                        {{ $t('users.delete') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination placeholder -->
                <div class="mt-6 flex justify-center gap-2">
                    <!-- Pure Inertia pagination logic could go here -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
