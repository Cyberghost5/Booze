<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    PackageCheck,
    Truck,
    MapPin,
    Phone,
    User,
    ArrowLeft,
    ShoppingBag,
    ExternalLink,
    RefreshCw
} from 'lucide-vue-next';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});

import { fireSuccessConfetti } from '@/Utils/confetti';
import { watch } from 'vue';

let pollTimer = null;

watch(() => props.order?.status, (newStatus, oldStatus) => {
    if (newStatus === 'delivered' && oldStatus !== 'delivered') {
        fireSuccessConfetti();
    }
});

onMounted(() => {
    if (props.order?.id) {
        localStorage.setItem('booze_last_order_id', props.order.id);
    }

    if (props.order?.status === 'delivered') {
        fireSuccessConfetti();
    }

    // Poll every 5 seconds if order is active (not delivered or cancelled)
    if (props.order.status !== 'delivered' && props.order.status !== 'cancelled') {
        pollTimer = setInterval(() => {
            router.reload({ only: ['order'], preserveScroll: true });
        }, 5000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const formatNaira = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Status step mapping
const steps = [
    { key: 'pending', label: 'Order Received', icon: Clock },
    { key: 'packed', label: 'Packed & Ready', icon: PackageCheck },
    { key: 'out_for_delivery', label: 'Out for Delivery', icon: Truck },
    { key: 'delivered', label: 'Delivered', icon: CheckCircle2 },
];

const currentStepIndex = computed(() => {
    switch (props.order.status) {
        case 'pending': return 0;
        case 'packed': return 1;
        case 'out_for_delivery': return 2;
        case 'delivered': return 3;
        default: return 0;
    }
});
const cancelMyOrder = () => {
    if (window.confirm('Are you sure you want to cancel this order? Stock will be restored and you can place a new order.')) {
        router.patch(route('consumer.orders.cancel', props.order.id), {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="`Order #${order.order_number} Status`" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <!-- Back to Dashboard Link -->
            <Link
                :href="route('dashboard')"
                class="inline-flex items-center gap-2 text-xs font-bold text-amber-500 hover:text-amber-400 mb-6"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to My Dashboard
            </Link>

            <!-- ORDER HEADER CARD -->
            <div class="rounded-3xl border border-zinc-800 bg-zinc-900 p-6 sm:p-8 shadow-2xl mb-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-800 pb-6">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-400 border border-amber-500/20 mb-2">
                            🚀 Express Delivery
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black text-white">
                            Order #{{ order.order_number }}
                        </h1>
                        <p class="text-xs text-zinc-400 mt-1">
                            Placed on {{ new Date(order.created_at).toLocaleString() }}
                        </p>
                    </div>

                    <div class="text-right flex flex-col items-end gap-2">
                        <div>
                            <span class="block text-xs text-zinc-500">Total Payable</span>
                            <span class="text-2xl font-black text-amber-500">{{ formatNaira(order.total) }}</span>
                        </div>
                        <button
                            v-if="order.status === 'pending'"
                            @click="cancelMyOrder"
                            class="inline-flex items-center gap-1 rounded-xl bg-red-950/60 border border-red-500/40 px-3 py-1.5 text-xs font-bold text-red-400 hover:bg-red-900/60 transition"
                        >
                            Cancel This Order
                        </button>
                    </div>
                </div>

                <!-- CANCELLED ORDER BANNER -->
                <div v-if="order.status === 'cancelled'" class="py-6 my-4 rounded-2xl bg-red-950/40 border border-red-800/50 p-4 text-center text-red-300">
                    <span class="inline-flex items-center gap-2 text-sm font-black text-red-400 uppercase tracking-wider">
                        ❌ Order Cancelled
                    </span>
                    <p class="text-xs text-red-300/80 mt-1">This order was cancelled.</p>
                </div>

                <!-- LIVE ORDER STATUS TRACKER STEPS -->
                <div v-else class="py-8">
                    <div class="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider text-zinc-400 mb-6">
                        <RefreshCw v-if="order.status !== 'delivered'" class="h-3.5 w-3.5 text-amber-500 animate-spin" />
                        Live Order Status Tracker
                        <span v-if="order.status !== 'delivered'" class="text-amber-500 text-[10px] lowercase font-normal">(auto-updating)</span>
                    </div>

                    <div class="relative flex items-start justify-between px-2">
                        <!-- Progress Connecting Line Track (Centered at top-6 / 24px) -->
                        <div class="absolute left-6 right-6 top-6 -translate-y-1/2 h-1 bg-zinc-800 z-0 overflow-hidden rounded-full">
                            <div
                                class="h-full bg-amber-500 transition-all duration-500 shadow-sm"
                                :style="{ width: `${(currentStepIndex / (steps.length - 1)) * 100}%` }"
                            ></div>
                        </div>

                        <!-- Step Icons -->
                        <div
                            v-for="(step, index) in steps"
                            :key="step.key"
                            class="relative z-10 flex flex-col items-center"
                        >
                            <div
                                :class="[
                                    'flex h-12 w-12 items-center justify-center rounded-2xl border-2 transition duration-300',
                                    index <= currentStepIndex
                                        ? 'border-amber-500 bg-amber-500 text-black shadow-lg shadow-amber-500/30 ring-4 ring-zinc-900'
                                        : 'border-zinc-800 bg-zinc-900 text-zinc-500 ring-4 ring-zinc-900'
                                ]"
                            >
                                <component :is="step.icon" class="h-5 w-5" />
                            </div>
                            <span
                                :class="[
                                    'mt-3 text-xs font-bold text-center max-w-[90px] leading-tight',
                                    index <= currentStepIndex ? 'text-white' : 'text-zinc-500'
                                ]"
                            >
                                {{ step.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ORDER ITEMS & ADDRESS SUMMARY -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-zinc-800">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">
                            Delivery Details
                        </h4>
                        <div class="space-y-2 text-xs text-zinc-300">
                            <div class="flex items-center gap-2">
                                <User class="h-4 w-4 text-amber-500" />
                                <span>{{ order.customer_name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Phone class="h-4 w-4 text-amber-500" />
                                <span>{{ order.customer_phone }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <MapPin class="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
                                <div>
                                    {{ order.delivery_address }}
                                    <a
                                        :href="`https://maps.google.com/?q=${order.latitude},${order.longitude}`"
                                        target="_blank"
                                        class="ml-2 text-amber-500 underline hover:text-amber-400 inline-flex items-center gap-1"
                                    >
                                        Map Pin <ExternalLink class="h-3 w-3" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">
                            Items Ordered
                        </h4>
                        <ul class="divide-y divide-zinc-800 text-xs text-zinc-300">
                            <li
                                v-for="item in order.items"
                                :key="item.id"
                                class="py-2 flex justify-between"
                            >
                                <span><strong class="text-amber-500">{{ item.quantity }}x</strong> {{ item.product_name }}</span>
                                <span class="font-mono">{{ formatNaira(item.subtotal) }}</span>
                            </li>
                        </ul>

                        <div class="mt-3 border-t border-zinc-800 pt-2 space-y-1 text-xs">
                            <div class="flex justify-between text-zinc-400">
                                <span>Items Subtotal:</span>
                                <span>{{ formatNaira(order.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-400">
                                <span>Fixed Delivery Fee:</span>
                                <span class="text-amber-400 font-bold">{{ formatNaira(order.delivery_fee) }}</span>
                            </div>
                            <div class="flex justify-between font-black text-white text-sm pt-1">
                                <span>Grand Total:</span>
                                <span class="text-amber-500">{{ formatNaira(order.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
