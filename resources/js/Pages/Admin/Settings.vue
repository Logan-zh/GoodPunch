<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    office_latitude:  [String, Number],
    office_longitude: [String, Number],
    allowed_radius:   [String, Number],
    company_name:     String,
});

const form = useForm({
    office_latitude:  props.office_latitude  ?? '',
    office_longitude: props.office_longitude ?? '',
    allowed_radius:   props.allowed_radius   ?? 100,
});

const isGettingLocation = ref(false);

const hasLocation = computed(() =>
    form.office_latitude !== '' && form.office_latitude !== null &&
    form.office_longitude !== '' && form.office_longitude !== null
);

const mapUrl = computed(() => {
    if (!hasLocation.value) return null;
    const lat = parseFloat(form.office_latitude);
    const lng = parseFloat(form.office_longitude);
    const r   = parseInt(form.allowed_radius) || 100;
    // OpenStreetMap static tile via OSM iframe embed
    return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - 0.002},${lat - 0.002},${lng + 0.002},${lat + 0.002}&layer=mapnik&marker=${lat},${lng}`;
});

const useCurrentLocation = () => {
    if (!navigator.geolocation) {
        alert('您的瀏覽器不支援地理位置功能。');
        return;
    }
    if (!window.isSecureContext) {
        alert('地理位置功能需要安全連線 (HTTPS 或 localhost)。');
        return;
    }
    isGettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.office_latitude  = position.coords.latitude;
            form.office_longitude = position.coords.longitude;
            isGettingLocation.value = false;
        },
        (error) => {
            const msgs = {
                1: '權限被拒絕，請檢查瀏覽器設定。',
                2: '無法取得位置資訊。',
                3: '請求逾時，請重試。',
            };
            alert('獲取位置失敗：' + (msgs[error.code] ?? '發生未知錯誤。'));
            isGettingLocation.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const submit = () => {
    form.post(route('admin.settings.update'), { preserveScroll: true });
};
</script>

<template>
    <Head title="打卡範圍設定" />

    <AuthenticatedLayout>
        <div class="punch-container">
            <div class="max-w-2xl mx-auto py-8 space-y-6">

                <!-- Header -->
                <div>
                    <h1 class="text-3xl font-bold text-white">打卡範圍設定</h1>
                    <p class="text-slate-400 mt-1">
                        設定
                        <span v-if="company_name" class="text-violet-400 font-medium">{{ company_name }}</span>
                        的辦公室位置與允許打卡範圍。
                    </p>
                </div>

                <!-- Flash -->
                <div v-if="$page.props.flash?.success"
                     class="p-4 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-xl text-sm">
                    ✓ {{ $page.props.flash.success }}
                </div>

                <!-- Form Card -->
                <div class="glass-card space-y-6">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Coordinates row -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                    辦公室緯度 <span class="text-slate-500 font-normal">(Latitude)</span>
                                </label>
                                <input
                                    v-model="form.office_latitude"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-3 focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm"
                                    placeholder="例：25.0339"
                                >
                                <p v-if="form.errors.office_latitude" class="text-red-400 text-xs mt-1">
                                    {{ form.errors.office_latitude }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                    辦公室經度 <span class="text-slate-500 font-normal">(Longitude)</span>
                                </label>
                                <input
                                    v-model="form.office_longitude"
                                    type="text"
                                    inputmode="decimal"
                                    class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-3 focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm"
                                    placeholder="例：121.5644"
                                >
                                <p v-if="form.errors.office_longitude" class="text-red-400 text-xs mt-1">
                                    {{ form.errors.office_longitude }}
                                </p>
                            </div>
                        </div>

                        <!-- Radius -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1.5">
                                允許打卡範圍（公尺）
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.allowed_radius"
                                    type="number"
                                    min="0"
                                    max="50000"
                                    class="w-full bg-slate-900/50 border border-slate-700 text-white rounded-lg p-3 pr-14 focus:ring-2 focus:ring-violet-500 focus:border-transparent text-sm"
                                    placeholder="例：100"
                                >
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">公尺</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5">
                                設為 0 或留空則停用距離限制，員工可在任何地點打卡。
                            </p>
                            <p v-if="form.errors.allowed_radius" class="text-red-400 text-xs mt-1">
                                {{ form.errors.allowed_radius }}
                            </p>
                        </div>

                        <!-- Status indicator -->
                        <div class="flex items-center gap-3 p-3 rounded-lg"
                             :class="hasLocation && form.allowed_radius > 0
                                 ? 'bg-emerald-500/10 border border-emerald-500/20'
                                 : 'bg-slate-700/30 border border-slate-700'">
                            <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                 :class="hasLocation && form.allowed_radius > 0 ? 'bg-emerald-400' : 'bg-slate-500'">
                            </div>
                            <p class="text-sm"
                               :class="hasLocation && form.allowed_radius > 0 ? 'text-emerald-300' : 'text-slate-400'">
                                <template v-if="hasLocation && form.allowed_radius > 0">
                                    打卡範圍限制已啟用：員工必須在辦公室 {{ form.allowed_radius }} 公尺內才能打卡。
                                </template>
                                <template v-else>
                                    打卡範圍限制未啟用：員工可在任何地點打卡。
                                </template>
                            </p>
                        </div>

                        <!-- Map preview -->
                        <div v-if="hasLocation" class="rounded-xl overflow-hidden border border-slate-700">
                            <div class="bg-slate-800 px-4 py-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-slate-300 text-xs font-medium">辦公室位置預覽</span>
                                <span class="ml-auto text-slate-500 text-xs">
                                    {{ parseFloat(form.office_latitude).toFixed(5) }}, {{ parseFloat(form.office_longitude).toFixed(5) }}
                                </span>
                            </div>
                            <iframe
                                :src="mapUrl"
                                class="w-full h-48 border-0"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                            ></iframe>
                        </div>

                        <!-- Actions -->
                        <div class="pt-2 flex flex-col gap-3">
                            <button
                                type="button"
                                @click="useCurrentLocation"
                                :disabled="isGettingLocation"
                                class="w-full bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-slate-200 font-medium py-3 px-4 rounded-lg transition-all flex items-center justify-center gap-2 text-sm border border-slate-700"
                            >
                                <svg v-if="!isGettingLocation" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-violet-400"></span>
                                {{ isGettingLocation ? '正在取得位置…' : '使用我目前的位置' }}
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white font-bold py-3 px-4 rounded-lg transition-all"
                            >
                                {{ form.processing ? '儲存中…' : '儲存設定' }}
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import "@css/punch.css";
</style>

