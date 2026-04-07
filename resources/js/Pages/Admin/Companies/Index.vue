<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    companies: Object,
});

const showCreate = ref(false);

const form = useForm({
    name: '',
    code: '',
    tax_id: '',
    principal: '',
    phone: '',
    address: '',
});

const submit = () => {
    form.post(route('admin.companies.store'), {
        onSuccess: () => {
            showCreate.value = false;
            form.reset();
        },
    });
};

const confirmDelete = (company) => {
    if (confirm(`Are you sure you want to delete ${company.name}? This will delete all users and punches associated with it.`)) {
        router.delete(route('admin.companies.destroy', company.id));
    }
};
</script>

<template>
    <Head title="Company Management" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-7xl mx-auto py-8">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Platform Enterprise Management</h1>
                        <p class="text-slate-400 mt-2">Oversee all registered enterprises and their activity.</p>
                    </div>
                    <button @click="showCreate = !showCreate" class="btn-primary">
                        {{ showCreate ? 'Cancel' : '+ New Enterprise' }}
                    </button>
                </div>

                <!-- Create Form -->
                <div v-if="showCreate" class="glass-card p-6 mb-8">
                    <h2 class="text-lg font-bold text-white mb-4">New Enterprise</h2>
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Company Name <span class="text-rose-400">*</span></label>
                            <input v-model="form.name" type="text" class="input-field w-full bg-black" placeholder="Acme Corp" required />
                            <p v-if="form.errors.name" class="text-rose-400 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Company Code <span class="text-rose-400">*</span></label>
                            <input v-model="form.code" type="text" class="input-field w-full bg-black" placeholder="ACME" required />
                            <p v-if="form.errors.code" class="text-rose-400 text-xs mt-1">{{ form.errors.code }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tax ID</label>
                            <input v-model="form.tax_id" type="text" class="input-field w-full bg-black" placeholder="12345678" />
                            <p v-if="form.errors.tax_id" class="text-rose-400 text-xs mt-1">{{ form.errors.tax_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Principal</label>
                            <input v-model="form.principal" type="text" class="input-field w-full bg-black" placeholder="John Doe" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Phone</label>
                            <input v-model="form.phone" type="text" class="input-field w-full bg-black" placeholder="0912345678" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Address</label>
                            <input v-model="form.address" type="text" class="input-field w-full bg-black" placeholder="123 Main St" />
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" :disabled="form.processing" class="btn-primary">
                                Create Enterprise
                            </button>
                        </div>
                    </form>
                </div>

                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-6 py-4">Enterprise</th>
                                <th class="px-6 py-4">Tax ID / Code</th>
                                <th class="px-6 py-4">Stats</th>
                                <th class="px-6 py-4">Contact</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <tr v-for="company in companies.data" :key="company.id" class="text-slate-300 hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white text-lg">{{ company.name }}</div>
                                    <div class="text-xs text-slate-500 uppercase tracking-tighter">{{ company.code }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">{{ company.tax_id || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ company.principal || 'No Principal' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs bg-violet-500/10 text-violet-400 px-2 py-0.5 rounded-full w-fit">
                                            {{ company.users_count }} Staff
                                        </span>
                                        <span class="text-xs bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full w-fit">
                                            {{ company.punches_count }} Punches
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs">{{ company.phone || 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-500 max-w-[150px] truncate">{{ company.address }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-4">
                                        <Link
                                            :href="route('admin.users.index', { company_id: company.id })"
                                            class="text-violet-400 hover:text-violet-300 font-bold text-sm transition-colors"
                                        >
                                            Manage Users →
                                        </Link>
                                        <button 
                                            @click="confirmDelete(company)"
                                            class="text-rose-500 hover:text-rose-400 font-bold text-sm transition-colors"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>
