<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    company_name: '',
    company_code: '',
    tax_id: '',
    principal: '',
    phone: '',
    address: '',
    name: '',
    email: '',
    hired_at: new Date().toISOString().split('T')[0],
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Enterprise Registration" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold text-white">Register your Enterprise</h2>
            <p class="text-slate-400 mt-1">Start managing your team's attendance today.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Company Info Section -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-violet-400 uppercase tracking-wider">Company Profile</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="company_name" value="Company Name (企業名稱)" />
                        <TextInput id="company_name" type="text" class="mt-1 block w-full" v-model="form.company_name" required autofocus />
                        <InputError class="mt-2" :message="form.errors.company_name" />
                    </div>
                    <div>
                        <InputLabel for="company_code" value="Short Code (代碼: e.g. ACME)" />
                        <TextInput id="company_code" type="text" class="mt-1 block w-full" v-model="form.company_code" required />
                        <InputError class="mt-2" :message="form.errors.company_code" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="tax_id" value="Tax ID (統一編號)" />
                        <TextInput id="tax_id" type="text" class="mt-1 block w-full" v-model="form.tax_id" required />
                        <InputError class="mt-2" :message="form.errors.tax_id" />
                    </div>
                    <div>
                        <InputLabel for="principal" value="Principal (負責人)" />
                        <TextInput id="principal" type="text" class="mt-1 block w-full" v-model="form.principal" required />
                        <InputError class="mt-2" :message="form.errors.principal" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="phone" value="Phone (連絡電話)" />
                        <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" required />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>
                    <div>
                        <InputLabel for="address" value="Address (地址)" />
                        <TextInput id="address" type="text" class="mt-1 block w-full" v-model="form.address" required />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>
                </div>
            </div>

            <hr class="border-slate-700" />

            <!-- Admin Info Section -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-emerald-400 uppercase tracking-wider">Initial Administrator</h3>

                <div>
                    <InputLabel for="name" value="Admin Name" />
                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="email" value="Admin Email" />
                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="hired_at" value="Hired Date (到職日)" />
                    <TextInput id="hired_at" type="date" class="mt-1 block w-full" v-model="form.hired_at" required />
                    <InputError class="mt-2" :message="form.errors.hired_at" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end">
                <Link :href="route('login')" class="text-sm text-slate-400 underline hover:text-white">
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register Enterprise
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
