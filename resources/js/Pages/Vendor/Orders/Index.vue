<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ShoppingBag,
    TrendingUp,
    DollarSign,
    Calendar,
    MapPin,
    Phone,
    User,
    CheckCircle2,
    Clock,
    Truck,
    PackageCheck,
    Send,
    ExternalLink,
    ChevronRight,
    AlertCircle,
    Copy,
    Download,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    dateFilter: {
        type: String,
        default: 'today',
    },
    ledger: {
        type: Object,
        default: () => ({
            totalSales: 0,
            totalCogs: 0,
            netProfit: 0,
            profitMargin: 0,
            totalOrdersCount: 0,
            pendingCount: 0,
            packedCount: 0,
            outForDeliveryCount: 0,
            deliveredCount: 0,
        }),
    },
    staffPhone: {
        type: String,
        default: '',
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.errors?.order);

const selectedFilter = ref(props.dateFilter);
const copiedOrderId = ref(null);

// Format Naira
const formatNaira = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Date Filter Change
const handleDateFilterChange = (filter) => {
    selectedFilter.value = filter;
    router.get(
        route('vendor.orders.index'),
        { date: filter },
        { preserveState: true, replace: true }
    );
};

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

const cancelOrder = (order) => {
    if (window.confirm(`Cancel order #${order.order_number} and restore stock levels?`)) {
        updateOrderStatus(order, 'cancelled');
    }
};

// Open WhatsApp Dispatch Link
const dispatchOrder = (order) => {
    window.open(order.whatsapp_dispatch_url, '_blank');
};

// Status Color & Label Helpers
const getStatusBadge = (status) => {
    switch (status) {
        case 'pending':
            return {
                label: 'Pending',
                bg: 'bg-amber-500/10 border-amber-500/30 text-amber-500',
                icon: Clock,
            };
        case 'packed':
            return {
                label: 'Packed & Ready',
                bg: 'bg-blue-500/10 border-blue-500/30 text-blue-500',
                icon: PackageCheck,
            };
        case 'out_for_delivery':
            return {
                label: 'Out for Delivery',
                bg: 'bg-purple-500/10 border-purple-500/30 text-purple-500',
                icon: Truck,
            };
        case 'delivered':
            return {
                label: 'Delivered',
                bg: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-500',
                icon: CheckCircle2,
            };
        case 'cancelled':
            return {
                label: 'Cancelled & Restored',
                bg: 'bg-red-500/10 border-red-500/30 text-red-500',
                icon: AlertCircle,
            };
        default:
            return {
                label: status,
                bg: 'bg-gray-500/10 border-gray-500/30 text-gray-500',
                icon: Clock,
            };
    }
};
</script>

<template>
    <Head title="Vendor Orders & Bookkeeping Digest" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white flex items-center gap-2">
                        <ShoppingBag class="h-7 w-7 text-amber-500" />
                        Vendor Orders & Daily Bookkeeping
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Live order dispatch feed & automated COGS/profit digest
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 self-start sm:self-auto">
                    <!-- Date Range Filter Tabs -->
                    <div class="flex rounded-xl bg-gray-200/80 p-1 dark:bg-gray-800">
                        <button
                            @click="handleDateFilterChange('today')"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                                selectedFilter === 'today'
                                    ? 'bg-white text-gray-900 shadow dark:bg-gray-900 dark:text-white'
                                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            Today
                        </button>
                        <button
                            @click="handleDateFilterChange('7days')"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                                selectedFilter === '7days'
                                    ? 'bg-white text-gray-900 shadow dark:bg-gray-900 dark:text-white'
                                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            Last 7 Days
                        </button>
                        <button
                            @click="handleDateFilterChange('all')"
                            :class="[
                                'px-3 py-1.5 text-xs font-semibold rounded-lg transition',
                                selectedFilter === 'all'
                                    ? 'bg-white text-gray-900 shadow dark:bg-gray-900 dark:text-white'
                                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            All Orders
                        </button>
                    </div>

                    <!-- Rider Dispatch View Button -->
                    <a
                        :href="route('vendor.orders.rider')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-extrabold text-gray-950 shadow-sm transition hover:bg-amber-400 focus:outline-none"
                    >
                        <Truck class="h-4 w-4" />
                        🛵 Rider Dispatch View
                    </a>

                    <!-- Export CSV Button -->
                    <a
                        :href="route('vendor.orders.export-csv', { date: selectedFilter })"
                        download
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none"
                    >
                        <Download class="h-4 w-4" />
                        Export Sales (CSV)
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 dark:bg-gray-950 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Flash Notification Banner -->
                <transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="flashSuccess"
                        class="mb-6 flex items-center justify-between rounded-xl bg-emerald-950/80 border border-emerald-500/30 p-4 text-emerald-200 shadow-lg"
                    >
                        <div class="flex items-center gap-3">
                            <CheckCircle2 class="h-6 w-6 text-emerald-400 shrink-0" />
                            <span class="text-sm font-medium">{{ flashSuccess }}</span>
                        </div>
                    </div>
                </transition>

                <transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="flashError"
                        class="mb-6 flex items-center justify-between rounded-xl bg-red-950/80 border border-red-500/30 p-4 text-red-200 shadow-lg"
                    >
                        <div class="flex items-center gap-3">
                            <AlertCircle class="h-6 w-6 text-red-400 shrink-0" />
                            <span class="text-sm font-medium">{{ flashError }}</span>
                        </div>
                    </div>
                </transition>

                <!-- BOOKKEEPING DIGEST SUMMARY CARDS -->
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Sales</span>
                            <DollarSign class="h-5 w-5 text-emerald-500" />
                        </div>
                        <p class="mt-3 text-2xl font-black text-gray-900 dark:text-white">{{ formatNaira(ledger.totalSales) }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ledger.totalOrdersCount }} orders in period</span>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">COGS</span>
                            <ShoppingBag class="h-5 w-5 text-indigo-500" />
                        </div>
                        <p class="mt-3 text-2xl font-black text-gray-900 dark:text-white">{{ formatNaira(ledger.totalCogs) }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Product wholesale cost</span>
                    </div>

                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Net Profit</span>
                            <TrendingUp class="h-5 w-5 text-emerald-400" />
                        </div>
                        <p class="mt-3 text-2xl font-black text-emerald-400">{{ formatNaira(ledger.netProfit) }}</p>
                        <span class="text-xs font-semibold text-emerald-300">
                            {{ ledger.profitMargin }}% Profit Margin
                        </span>
                    </div>

                    <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Bestseller</span>
                            <Sparkles class="h-5 w-5 text-amber-400" />
                        </div>
                        <p class="mt-3 text-sm font-black text-amber-200 truncate" :title="ledger.bestsellerToday">{{ ledger.bestsellerToday || 'No sales yet' }}</p>
                        <span class="text-xs text-amber-400/80">Top drink in Gwallameji</span>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</span>
                            <Truck class="h-5 w-5 text-amber-500" />
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1 text-[11px]">
                        </div>
                    </div>
                </div>

                <!-- ORDERS DISPATCH FEED -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Truck class="h-5 w-5 text-amber-500" />
                            Live Orders Dispatch Feed
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Showing {{ orders.length }} order(s)
                        </span>
                    </div>

                    <div v-if="orders.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900">
                        <ShoppingBag class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">No orders found for this period</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            When consumers place orders in Gwallameji, they will immediately appear here for WhatsApp dispatching.
                        </p>
                    </div>

                    <div
                        v-for="order in orders"
                        :key="order.id"
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <!-- Left: Order Details & Customer Info -->
                            <div class="space-y-4 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="text-lg font-extrabold text-amber-500 font-mono">
                                        #{{ order.order_number }}
                                    </span>

                                    <!-- Status Pill -->
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold',
                                            getStatusBadge(order.status).bg
                                        ]"
                                    >
                                        <component :is="getStatusBadge(order.status).icon" class="h-3.5 w-3.5" />
                                        {{ getStatusBadge(order.status).label }}
                                    </span>

                                    <span class="text-xs text-gray-400">
                                        {{ new Date(order.created_at).toLocaleString() }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <User class="h-4 w-4 text-amber-500 shrink-0" />
                                        <span><strong>Customer:</strong> {{ order.customer_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <Phone class="h-4 w-4 text-amber-500 shrink-0" />
                                        <span><strong>Phone:</strong> {{ order.customer_phone }}</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <MapPin class="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <strong>Delivery Address:</strong> {{ order.delivery_address }}
                                        <a
                                            :href="`https://maps.google.com/?q=${order.latitude},${order.longitude}`"
                                            target="_blank"
                                            class="ml-2 inline-flex items-center gap-1 text-xs font-semibold text-amber-500 underline hover:text-amber-400"
                                        >
                                            View Pin on Maps <ExternalLink class="h-3 w-3" />
                                        </a>
                                    </div>
                                </div>

                                <!-- Ordered Items List -->
                                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                        Items to Pack ({{ order.items.length }})
                                    </h4>
                                    <ul class="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                        <li
                                            v-for="item in order.items"
                                            :key="item.id"
                                            class="py-2 flex items-center justify-between text-gray-900 dark:text-gray-100"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-amber-500">{{ item.quantity }}x</span>
                                                <span>{{ item.product_name }}</span>
                                            </div>
                                            <span class="font-semibold text-gray-600 dark:text-gray-400">
                                                {{ formatNaira(item.subtotal) }}
                                            </span>
                                        </li>
                                    </ul>

                                    <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-2 dark:border-gray-800 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                        <span>Delivery Fee: {{ formatNaira(order.delivery_fee) }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                                            Total: {{ formatNaira(order.total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Status Actions & One-Click WhatsApp Dispatch -->
                            <div class="flex flex-col gap-3 lg:w-72 border-t border-gray-100 pt-4 lg:border-t-0 lg:pt-0 shrink-0">
                                <!-- Order Profit Summary -->
                                <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/10 p-3 text-xs">
                                    <div class="flex justify-between text-emerald-400 font-bold mb-1">
                                        <span>Order Profit:</span>
                                        <span>+{{ formatNaira(order.net_profit) }}</span>
                                    </div>
                                    <div class="flex justify-between text-gray-400">
                                        <span>Order COGS:</span>
                                        <span>{{ formatNaira(order.cogs) }}</span>
                                    </div>
                                </div>

                                <!-- ONE-CLICK WHATSAPP DISPATCH BUTTON -->
                                <button
                                    v-if="order.status !== 'delivered' && order.status !== 'cancelled'"
                                    @click="dispatchOrder(order)"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500 active:scale-95"
                                >
                                    <Send class="h-4 w-4" />
                                    Dispatch via WhatsApp
                                </button>

                                <!-- STATUS UPDATE CONTROLS (Disappears when Delivered or Cancelled) -->
                                <div v-if="order.status !== 'delivered' && order.status !== 'cancelled'" class="space-y-1.5 pt-2">
                                    <span class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400">
                                        Update Order Status
                                    </span>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            @click="updateOrderStatus(order, 'packed')"
                                            :disabled="order.status === 'packed' || updatingOrderId === order.id"
                                            :class="[
                                                'rounded-lg px-2.5 py-1.5 text-xs font-semibold transition border',
                                                order.status === 'packed'
                                                    ? 'bg-blue-600 text-white border-blue-600'
                                                    : 'border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'
                                            ]"
                                        >
                                            Packed
                                        </button>

                                        <button
                                            @click="updateOrderStatus(order, 'out_for_delivery')"
                                            :disabled="order.status === 'out_for_delivery' || updatingOrderId === order.id"
                                            :class="[
                                                'rounded-lg px-2.5 py-1.5 text-xs font-semibold transition border',
                                                order.status === 'out_for_delivery'
                                                    ? 'bg-purple-600 text-white border-purple-600'
                                                    : 'border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'
                                            ]"
                                        >
                                            Out for Delivery
                                        </button>
                                    </div>

                                    <button
                                        @click="updateOrderStatus(order, 'delivered')"
                                        :disabled="updatingOrderId === order.id"
                                        class="w-full rounded-lg px-2.5 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition"
                                    >
                                        Mark Delivered & Complete
                                    </button>

                                    <button
                                        @click="cancelOrder(order)"
                                        :disabled="updatingOrderId === order.id"
                                        class="w-full rounded-lg px-2.5 py-1.5 text-xs font-semibold transition border border-red-500/30 text-red-500 hover:bg-red-500/10"
                                    >
                                        Cancel Order & Restore Stock
                                    </button>
                                </div>

                                <!-- FINALIZED ORDER STATUS BADGES -->
                                <div v-else-if="order.status === 'delivered'" class="mt-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-3 text-center text-xs font-bold text-emerald-400">
                                    ✅ Order Delivered & Completed
                                </div>

                                <div v-else-if="order.status === 'cancelled'" class="mt-2 rounded-xl bg-red-500/10 border border-red-500/30 p-3 text-center text-xs font-bold text-red-400">
                                    ❌ Order Cancelled & Restored
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
