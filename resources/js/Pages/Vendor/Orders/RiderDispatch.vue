<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    Bike,
    Phone,
    MapPin,
    Navigation,
    CheckCircle2,
    Clock,
    PackageCheck,
    Send,
    ArrowLeft,
    Sparkles,
    CheckSquare,
    Square,
    AlertCircle,
    ShoppingBag
} from 'lucide-vue-next';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    vendorStoreName: {
        type: String,
        default: 'Gwallameji Store',
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

// Active Tab Filter: 'all', 'out_for_delivery', 'packed'
const activeFilter = ref('all');

// Checklist state for items being loaded by riders: { [orderId_itemId]: boolean }
const checkedItems = ref({});

const toggleItemCheck = (orderId, itemId) => {
    const key = `${orderId}_${itemId}`;
    checkedItems.value[key] = !checkedItems.value[key];
};

const isItemChecked = (orderId, itemId) => {
    return Boolean(checkedItems.value[`${orderId}_${itemId}`]);
};

// Filtered Orders
const filteredOrders = computed(() => {
    if (activeFilter.value === 'all') return props.orders;
    return props.orders.filter(o => o.status === activeFilter.value);
});

const outForDeliveryCount = computed(() => props.orders.filter(o => o.status === 'out_for_delivery').length);
const packedCount = computed(() => props.orders.filter(o => o.status === 'packed').length);
const pendingCount = computed(() => props.orders.filter(o => o.status === 'pending').length);

import { fireSuccessConfetti } from '@/Utils/confetti';

// Status Update Handler
const updatingOrderId = ref(null);
const updateOrderStatus = (order, newStatus) => {
    updatingOrderId.value = order.id;
    router.patch(
        route('vendor.orders.update-status', order.id),
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (newStatus === 'delivered') {
                    fireSuccessConfetti();
                }
            },
            onFinish: () => {
                updatingOrderId.value = null;
            },
        }
    );
};

// Format currency in Naira
const formatNaira = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Format Time
const formatTime = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Delivery Rider Dispatch Console" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 selection:bg-amber-500 selection:text-black">
        <!-- Top Sticky Rider Header -->
        <header class="sticky top-0 z-40 bg-zinc-900/90 border-b border-zinc-800 backdrop-blur-md shadow-xl">
            <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Link :href="route('vendor.orders.index')" class="p-2 rounded-xl bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-2">
                        <ApplicationLogo class="h-8 w-auto" />
                        <span class="text-xs bg-amber-500/20 text-amber-400 border border-amber-500/40 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider flex items-center gap-1">
                            <Bike class="w-3.5 h-3.5" /> Rider Console
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="hidden sm:inline text-xs text-zinc-400 font-mono">{{ vendorStoreName }}</span>
                    <Link :href="route('vendor.orders.index')" class="text-xs font-bold text-amber-400 hover:text-amber-300 bg-amber-500/10 border border-amber-500/30 px-3 py-1.5 rounded-xl transition">
                        Back to Admin
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Rider Container -->
        <main class="max-w-4xl mx-auto px-4 py-6">
            <!-- Flash Banner -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flashSuccess"
                    class="mb-6 flex items-center justify-between rounded-2xl bg-emerald-950/90 border border-emerald-500/40 p-4 text-emerald-200 shadow-xl"
                >
                    <div class="flex items-center gap-3">
                        <CheckCircle2 class="h-6 w-6 text-emerald-400 shrink-0" />
                        <span class="text-sm font-bold">{{ flashSuccess }}</span>
                    </div>
                </div>
            </transition>

            <!-- Rider Summary Bar & Tab Pills -->
            <div class="bg-zinc-900/80 border border-zinc-800 rounded-3xl p-5 mb-6 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-black text-white flex items-center gap-2">
                            🛵 Dispatch Control Board
                        </h1>
                        <p class="text-xs text-zinc-400 mt-0.5">
                            Hyper-local delivery queue for Gwallameji student lodges
                        </p>
                    </div>

                    <!-- Status Filter Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                        <button
                            type="button"
                            @click="activeFilter = 'all'"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-xl transition shrink-0',
                                activeFilter === 'all'
                                    ? 'bg-amber-500 text-black shadow-md'
                                    : 'bg-zinc-800 text-zinc-400 hover:text-white'
                            ]"
                        >
                            All Active ({{ orders.length }})
                        </button>

                        <button
                            type="button"
                            @click="activeFilter = 'out_for_delivery'"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-xl transition shrink-0 flex items-center gap-1',
                                activeFilter === 'out_for_delivery'
                                    ? 'bg-purple-500 text-white shadow-md'
                                    : 'bg-zinc-800 text-purple-400 hover:text-purple-300'
                            ]"
                        >
                            <Bike class="w-3.5 h-3.5" /> On Road ({{ outForDeliveryCount }})
                        </button>

                        <button
                            type="button"
                            @click="activeFilter = 'packed'"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-xl transition shrink-0 flex items-center gap-1',
                                activeFilter === 'packed'
                                    ? 'bg-blue-500 text-white shadow-md'
                                    : 'bg-zinc-800 text-blue-400 hover:text-blue-300'
                            ]"
                        >
                            <PackageCheck class="w-3.5 h-3.5" /> Packed ({{ packedCount }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-if="filteredOrders.length === 0" class="rounded-3xl border border-zinc-800 bg-zinc-900/50 p-12 text-center shadow-xl">
                <CheckCircle2 class="mx-auto h-16 w-16 text-emerald-500/80 mb-4" />
                <h2 class="text-xl font-black text-white">All Clear! No Active Deliveries</h2>
                <p class="mt-2 text-sm text-zinc-400 max-w-sm mx-auto">
                    There are currently no active dispatches waiting for delivery. New incoming orders will appear here automatically.
                </p>
            </div>

            <!-- RIDER ACTIVE DISPATCH CARDS -->
            <div v-else class="space-y-6">
                <div
                    v-for="order in filteredOrders"
                    :key="order.id"
                    :class="[
                        'rounded-3xl border transition-all duration-300 p-6 shadow-2xl relative overflow-hidden',
                        order.status === 'out_for_delivery'
                            ? 'bg-gradient-to-b from-purple-950/40 via-zinc-900 to-zinc-900 border-purple-500/50 ring-1 ring-purple-500/20'
                            : order.status === 'packed'
                            ? 'bg-gradient-to-b from-blue-950/40 via-zinc-900 to-zinc-900 border-blue-500/50'
                            : 'bg-zinc-900 border-zinc-800'
                    ]"
                >
                    <!-- Status Header Pill -->
                    <div class="flex items-center justify-between gap-2 border-b border-zinc-800/80 pb-4 mb-5">
                        <div class="flex items-center space-x-3">
                            <span class="text-lg font-black text-white font-mono tracking-tight">
                                #{{ order.order_number }}
                            </span>
                            <span class="text-xs text-zinc-400 font-mono">
                                {{ formatTime(order.created_at) }}
                            </span>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            <span
                                v-if="order.status === 'out_for_delivery'"
                                class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 px-3 py-1 text-xs font-black uppercase tracking-wider animate-pulse"
                            >
                                <Bike class="w-3.5 h-3.5" /> Out for Delivery
                            </span>
                            <span
                                v-else-if="order.status === 'packed'"
                                class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/40 px-3 py-1 text-xs font-black uppercase tracking-wider"
                            >
                                <PackageCheck class="w-3.5 h-3.5" /> Packed & Ready
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 px-3 py-1 text-xs font-black uppercase tracking-wider"
                            >
                                <Clock class="w-3.5 h-3.5" /> Pending Acceptance
                            </span>
                        </div>
                    </div>

                    <!-- CUSTOMER & LOCATION HIGHLIGHT BOX -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Customer Info Box -->
                        <div class="bg-zinc-950/80 border border-zinc-800 rounded-2xl p-4 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-zinc-400 block mb-1">
                                    Customer & Contact
                                </span>
                                <div class="text-base font-black text-white flex items-center gap-2">
                                    {{ order.customer_name }}
                                </div>
                                <div class="text-sm font-mono text-amber-400 mt-1">
                                    {{ order.customer_phone }}
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-zinc-800/80 flex items-center gap-2">
                                <!-- Direct Phone Call Button -->
                                <a
                                    :href="`tel:${order.customer_phone}`"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-extrabold text-xs py-2.5 px-3 transition shadow-lg"
                                >
                                    <Phone class="w-4 h-4 fill-current" />
                                    Call Customer
                                </a>

                                <!-- WhatsApp Arrival Ping Button -->
                                <a
                                    v-if="order.status === 'out_for_delivery'"
                                    :href="order.whatsapp_arrival_url"
                                    target="_blank"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600/20 border border-emerald-500/40 hover:bg-emerald-600/30 text-emerald-300 font-bold text-xs py-2.5 px-3 transition"
                                    title="Send WhatsApp alert to customer"
                                >
                                    <Send class="w-3.5 h-3.5" />
                                    Ping Arrival
                                </a>
                            </div>
                        </div>

                        <!-- Delivery Address & GPS Navigation Box -->
                        <div class="bg-zinc-950/80 border border-zinc-800 rounded-2xl p-4 flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-zinc-400 block mb-1">
                                    Gwallameji Delivery Destination
                                </span>
                                <div class="text-sm font-bold text-zinc-100 flex items-start gap-2 leading-snug">
                                    <MapPin class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
                                    <span>{{ order.delivery_address }}</span>
                                </div>
                                <div v-if="order.delivery_landmark" class="text-xs text-amber-300/90 font-medium mt-1.5 ml-6">
                                    📍 Landmark: {{ order.delivery_landmark }}
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-zinc-800/80">
                                <!-- 1-Tap Google Maps Navigation -->
                                <a
                                    :href="order.google_maps_url"
                                    target="_blank"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs py-2.5 px-3 border border-zinc-700 transition"
                                >
                                    <Navigation class="w-4 h-4 text-sky-400" />
                                    Open Navigation (Google Maps)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- INTERACTIVE ITEM LOADING CHECKLIST -->
                    <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-2xl p-4 mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-zinc-300 flex items-center gap-1.5">
                                <ShoppingBag class="w-4 h-4 text-amber-500" />
                                Rider Items Loading Checklist
                            </span>
                            <span class="text-[11px] text-zinc-400">
                                Tap items as you load onto bike
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                @click="toggleItemCheck(order.id, item.id)"
                                :class="[
                                    'flex items-center justify-between p-3 rounded-xl border cursor-pointer transition',
                                    isItemChecked(order.id, item.id)
                                        ? 'bg-emerald-950/30 border-emerald-500/40 text-emerald-200'
                                        : 'bg-zinc-900 border-zinc-800 text-zinc-200 hover:border-zinc-700'
                                ]"
                            >
                                <div class="flex items-center space-x-3">
                                    <button type="button" class="text-amber-500 shrink-0">
                                        <CheckSquare v-if="isItemChecked(order.id, item.id)" class="w-5 h-5 text-emerald-400" />
                                        <Square v-else class="w-5 h-5 text-zinc-600" />
                                    </button>

                                    <div class="flex items-center space-x-2">
                                        <span class="font-black text-sm text-white">
                                            {{ item.quantity }}x
                                        </span>
                                        <span :class="['text-sm font-semibold', isItemChecked(order.id, item.id) ? 'line-through opacity-70' : '']">
                                            {{ item.item_name }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <span v-if="item.product?.is_chilled" class="text-[10px] bg-cyan-950 text-cyan-300 border border-cyan-500/30 px-2 py-0.5 rounded-full font-bold">
                                        ❄️ Ice-Cold
                                    </span>
                                    <span class="text-xs font-mono font-bold text-zinc-400">
                                        {{ formatNaira(item.unit_price) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Payment Indicator -->
                        <div class="mt-4 pt-3 border-t border-zinc-800 flex items-center justify-between text-xs">
                            <span class="text-zinc-400">Payment Collection Total:</span>
                            <span class="text-base font-black text-emerald-400 font-mono">
                                {{ formatNaira(order.total_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- RIDER ACTION CONTROL BAR -->
                    <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
                        <!-- If status is packed: "Start Delivery 🛵" -->
                        <button
                            v-if="order.status === 'packed' || order.status === 'pending'"
                            type="button"
                            @click="updateOrderStatus(order, 'out_for_delivery')"
                            :disabled="updatingOrderId === order.id"
                            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-black text-sm py-3 px-6 shadow-xl transition disabled:opacity-50"
                        >
                            <Bike class="w-5 h-5" />
                            Start Delivery (Mark On Road 🛵)
                        </button>

                        <!-- If status is out_for_delivery: "Mark Delivered 🏁" -->
                        <button
                            v-if="order.status === 'out_for_delivery'"
                            type="button"
                            @click="updateOrderStatus(order, 'delivered')"
                            :disabled="updatingOrderId === order.id"
                            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm py-3.5 px-6 shadow-xl transition disabled:opacity-50"
                        >
                            <CheckCircle2 class="w-5 h-5" />
                            Mark Delivered 🏁 (Complete Order)
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
