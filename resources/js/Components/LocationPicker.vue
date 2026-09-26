<script setup>
import { ref } from 'vue';
import { MapPin, Building, Check, LocateFixed, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    address: {
        type: String,
        default: '',
    },
    latitude: {
        type: [Number, String],
        default: 10.284700,
    },
    longitude: {
        type: [Number, String],
        default: 9.791500,
    },
});

const emit = defineEmits(['update:address', 'update:latitude', 'update:longitude']);

const isLocating = ref(false);
const statusMessage = ref('');
const statusType = ref('success');

// Popular Gwallameji Landmarks for quick 1-tap address selection
const popularLandmarks = [
    { name: 'ATBU Gwallameji Gate', lat: 10.284700, lng: 9.791500 },
    { name: 'Fed Poly Hostel 1 & 2', lat: 10.285200, lng: 9.792800 },
    { name: 'Gwallameji Market Square', lat: 10.283100, lng: 9.790200 },
    { name: 'Executive Student Lodges', lat: 10.286500, lng: 9.794100 },
    { name: 'Yelwa Bypass Junction', lat: 10.281000, lng: 9.788500 },
];

const selectLandmark = (landmark) => {
    emit('update:address', landmark.name);
    emit('update:latitude', landmark.lat);
    emit('update:longitude', landmark.lng);
    statusMessage.value = '';
};

const detectCurrentLocation = () => {
    if (!navigator.geolocation) {
        statusMessage.value = 'Geolocation is not supported by your browser.';
        statusType.value = 'error';
        return;
    }

    isLocating.value = true;
    statusMessage.value = '';

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            emit('update:latitude', Number(lat.toFixed(6)));
            emit('update:longitude', Number(lng.toFixed(6)));

            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
                );
                if (response.ok) {
                    const data = await response.json();
                    if (data && data.display_name) {
                        const parts = data.display_name.split(',');
                        const shortAddr = parts.slice(0, 3).join(',').trim();
                        if (shortAddr) {
                            emit('update:address', shortAddr);
                        }
                    }
                }
            } catch (e) {
                if (!props.address) {
                    emit('update:address', 'Gwallameji, Bauchi');
                }
            } finally {
                isLocating.value = false;
                statusMessage.value = 'Current location detected!';
                statusType.value = 'success';
            }
        },
        (error) => {
            isLocating.value = false;
            let errorText = 'Unable to fetch your location.';
            if (error.code === error.PERMISSION_DENIED) {
                errorText = 'Location permission denied. Please select a landmark below or type your address.';
            }
            statusMessage.value = errorText;
            statusType.value = 'error';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};
</script>

<template>
    <div class="space-y-4">
        <!-- Simple Delivery Address Input -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1">
                Delivery Address / Lodge Name & Room Number
            </label>
            <div class="relative">
                <input
                    :value="address"
                    @input="$emit('update:address', $event.target.value)"
                    type="text"
                    placeholder="e.g. Block C, Room 14, Opposite Bayan Gari Lodge, Gwallameji"
                    required
                    class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-white focus:border-amber-500 focus:outline-none pl-10"
                />
                <MapPin class="absolute left-3 top-3.5 h-4 w-4 text-amber-500" />
            </div>
            <p class="text-[11px] text-zinc-500 mt-1">Please enter your lodge name, room number, or nearest landmark for the rider.</p>
        </div>

        <!-- Get Current Location Button -->
        <div>
            <button
                type="button"
                @click="detectCurrentLocation"
                :disabled="isLocating"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-xs font-bold text-amber-400 hover:bg-amber-500/20 active:scale-95 transition disabled:opacity-50 shadow-sm"
            >
                <Loader2 v-if="isLocating" class="h-4 w-4 animate-spin text-amber-400" />
                <LocateFixed v-else class="h-4 w-4 text-amber-500" />
                <span>{{ isLocating ? 'Detecting Location...' : '🎯 Use My Current Location' }}</span>
            </button>

            <p v-if="statusMessage" :class="['text-xs font-medium mt-1.5 flex items-center gap-1', statusType === 'success' ? 'text-emerald-400' : 'text-rose-400']">
                <span>{{ statusType === 'success' ? '✓' : '⚠️' }} {{ statusMessage }}</span>
            </p>
        </div>

        <!-- Quick Select Popular Landmarks -->
        <div>
            <span class="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2 flex items-center gap-1.5">
                <Building class="h-3.5 w-3.5 text-amber-500" />
                Select Popular Landmark
            </span>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="landmark in popularLandmarks"
                    :key="landmark.name"
                    type="button"
                    @click="selectLandmark(landmark)"
                    :class="[
                        'rounded-xl px-3 py-2 text-xs font-semibold border transition flex items-center gap-1.5',
                        address === landmark.name || address.startsWith(landmark.name)
                            ? 'bg-amber-500/20 border-amber-500/60 text-amber-300 shadow-sm'
                            : 'bg-zinc-950 border-zinc-800 text-zinc-400 hover:border-zinc-700 hover:text-zinc-200'
                    ]"
                >
                    <Check v-if="address === landmark.name" class="h-3.5 w-3.5 text-amber-400" />
                    <MapPin v-else class="h-3.5 w-3.5 text-amber-500" />
                    {{ landmark.name }}
                </button>
            </div>
        </div>
    </div>
</template>
