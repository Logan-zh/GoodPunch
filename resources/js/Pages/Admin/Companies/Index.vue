<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    companies: Object,
});

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
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-white">Platform Enterprise Management</h1>
                    <p class="text-slate-400 mt-2">Oversee all registered enterprises and their activity.</p>
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
                                    <button 
                                        @click="confirmDelete(company)"
                                        class="text-rose-500 hover:text-rose-400 font-bold text-sm transition-colors"
                                    >
                                        Remove Enterprise
                                    </button>
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
