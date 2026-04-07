<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    departments: Array,
    managers: Array,
    users: Array,
});

const isCreating = ref(false);
const editingDepartment = ref(null);
const assigningDepartment = ref(null);

const form = useForm({
    name: '',
    parent_id: '',
    manager_id: '',
});

const assignForm = useForm({
    user_id: '',
});

const rootDepartments = computed(() =>
    props.departments.filter(d => !d.parent_id)
);

const childrenOf = (id) =>
    props.departments.filter(d => d.parent_id === id);

const openCreate = () => {
    editingDepartment.value = null;
    form.reset();
    isCreating.value = true;
};

const editDepartment = (dept) => {
    editingDepartment.value = dept;
    form.name = dept.name;
    form.parent_id = dept.parent_id || '';
    form.manager_id = dept.manager_id || '';
    isCreating.value = true;
};

const submit = () => {
    if (editingDepartment.value) {
        form.patch(route('admin.departments.update', editingDepartment.value.id), {
            onSuccess: () => {
                isCreating.value = false;
                editingDepartment.value = null;
                form.reset();
            },
        });
    } else {
        form.post(route('admin.departments.store'), {
            onSuccess: () => {
                isCreating.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (dept) => {
    if (confirm(`Delete department "${dept.name}"? This cannot be undone.`)) {
        router.delete(route('admin.departments.destroy', dept.id));
    }
};

const openAssign = (dept) => {
    assigningDepartment.value = dept;
    assignForm.reset();
};

const submitAssign = () => {
    assignForm.post(route('admin.departments.assign-user', assigningDepartment.value.id), {
        onSuccess: () => {
            assigningDepartment.value = null;
            assignForm.reset();
        },
    });
};

const usersNotInDepartment = (deptId) =>
    props.users.filter(u => u.department_id !== deptId);

const availableParents = computed(() => {
    if (!editingDepartment.value) return props.departments;
    return props.departments.filter(d => d.id !== editingDepartment.value.id);
});
</script>

<template>
    <Head title="Department Management" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-7xl mx-auto py-8">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Department Management</h1>
                        <p class="text-slate-400 mt-2">Manage your organisation's departments and structure.</p>
                    </div>
                    <button
                        @click="openCreate"
                        class="bg-violet-600 hover:bg-violet-500 text-white font-bold py-2 px-4 rounded-lg transition-all"
                    >
                        + Add Department
                    </button>
                </div>

                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-3 rounded-lg text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 bg-rose-500/10 border border-rose-500/30 text-rose-400 px-4 py-3 rounded-lg text-sm">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Create/Edit Modal -->
                <div v-if="isCreating" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="glass-card max-w-md w-full p-8 relative overflow-y-auto max-h-[90vh]">
                        <button @click="isCreating = false" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <h2 class="text-2xl font-bold text-white mb-6">
                            {{ editingDepartment ? 'Edit Department' : 'New Department' }}
                        </h2>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Department Name <span class="text-rose-400">*</span></label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g. Engineering"
                                    class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5"
                                    required
                                >
                                <p v-if="form.errors.name" class="text-rose-400 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Parent Department</label>
                                <select v-model="form.parent_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                                    <option value="">-- None (top-level) --</option>
                                    <option v-for="d in availableParents" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                                <p v-if="form.errors.parent_id" class="text-rose-400 text-xs mt-1">{{ form.errors.parent_id }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Manager</label>
                                <select v-model="form.manager_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5">
                                    <option value="">-- No Manager --</option>
                                    <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                                <p v-if="form.errors.manager_id" class="text-rose-400 text-xs mt-1">{{ form.errors.manager_id }}</p>
                            </div>

                            <div class="pt-4 flex gap-3">
                                <button type="button" @click="isCreating = false" class="flex-1 bg-slate-800 text-white py-2.5 rounded-lg">Cancel</button>
                                <button type="submit" :disabled="form.processing" class="flex-1 bg-violet-600 text-white py-2.5 rounded-lg disabled:opacity-50">
                                    {{ editingDepartment ? 'Save Changes' : 'Create' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Department Tree Table -->
                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-6 py-4">Department</th>
                                <th class="px-6 py-4">Parent</th>
                                <th class="px-6 py-4">Manager</th>
                                <th class="px-6 py-4">Staff</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <template v-if="departments.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        No departments yet. Create your first department above.
                                    </td>
                                </tr>
                            </template>

                            <template v-for="dept in rootDepartments" :key="dept.id">
                                <tr class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-white">{{ dept.name }}</div>
                                        <div class="text-xs text-slate-500">#{{ dept.id }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-sm">—</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span v-if="dept.manager" class="text-slate-300">{{ dept.manager.name }}</span>
                                        <span v-else class="text-slate-600">None</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs bg-violet-500/10 text-violet-400 px-2 py-0.5 rounded-full">
                                            {{ dept.users_count }} staff
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openAssign(dept)" class="text-emerald-400 hover:text-emerald-300 transition-colors text-sm">Assign</button>
                                        <button @click="editDepartment(dept)" class="text-slate-400 hover:text-white transition-colors text-sm">Edit</button>
                                        <button @click="confirmDelete(dept)" class="text-slate-400 hover:text-rose-400 transition-colors text-sm">Delete</button>
                                    </td>                                </tr>

                                <tr
                                    v-for="child in childrenOf(dept.id)"
                                    :key="child.id"
                                    class="text-slate-300 hover:bg-slate-800/30 transition-colors bg-slate-900/20"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-white pl-6 flex items-center gap-2">
                                            <span class="text-slate-600">└</span> {{ child.name }}
                                        </div>
                                        <div class="text-xs text-slate-500 pl-6">#{{ child.id }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-400">{{ dept.name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span v-if="child.manager" class="text-slate-300">{{ child.manager.name }}</span>
                                        <span v-else class="text-slate-600">None</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs bg-violet-500/10 text-violet-400 px-2 py-0.5 rounded-full">
                                            {{ child.users_count }} staff
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openAssign(child)" class="text-emerald-400 hover:text-emerald-300 transition-colors text-sm">Assign</button>
                                        <button @click="editDepartment(child)" class="text-slate-400 hover:text-white transition-colors text-sm">Edit</button>
                                        <button @click="confirmDelete(child)" class="text-slate-400 hover:text-rose-400 transition-colors text-sm">Delete</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assign Member Modal -->
        <div v-if="assigningDepartment" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="glass-card max-w-sm w-full p-8 relative overflow-y-auto max-h-[90vh]">
                <button @click="assigningDepartment = null" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h2 class="text-xl font-bold text-white mb-1">Assign Member</h2>
                <p class="text-slate-400 text-sm mb-6">→ {{ assigningDepartment.name }}</p>

                <form @submit.prevent="submitAssign" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Select Staff</label>
                        <select v-model="assignForm.user_id" class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-2.5" required>
                            <option value="">-- Choose a staff member --</option>
                            <option v-for="u in usersNotInDepartment(assigningDepartment.id)" :key="u.id" :value="u.id">
                                {{ u.name }}
                            </option>
                        </select>
                        <p v-if="assignForm.errors.user_id" class="text-rose-400 text-xs mt-1">{{ assignForm.errors.user_id }}</p>
                    </div>
                    <div class="pt-2 flex gap-3">
                        <button type="button" @click="assigningDepartment = null" class="flex-1 bg-slate-800 text-white py-2.5 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="assignForm.processing" class="flex-1 bg-emerald-600 text-white py-2.5 rounded-lg disabled:opacity-50">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
