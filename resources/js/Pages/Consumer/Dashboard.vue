<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('orders'); // 'orders' or 'profile'
const statusFilter = ref('all');
const isLocating = ref(false);
const locationStatus = ref('');

const detectCurrentLocation = () => {
    if (!navigator.geolocation) {
        locationStatus.value = 'Geolocation is not supported by your browser.';
        return;
    }
    isLocating.value = true;
    locationStatus.value = '';

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            profileForm.default_latitude = Number(lat.toFixed(6));
            profileForm.default_longitude = Number(lng.toFixed(6));

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
                            profileForm.default_address = shortAddr;
                        }
                    }
                }
            } catch (e) {
                if (!profileForm.default_address) {
                    profileForm.default_address = 'Gwallameji, Bauchi';
                }
            } finally {
                isLocating.value = false;
                locationStatus.value = 'Current location detected successfully!';
            }
        },
        (error) => {
            isLocating.value = false;
            locationStatus.value = 'Unable to detect location. Please type your address manually.';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

const profileForm = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    default_address: props.user.default_address || '',
    default_latitude: props.user.default_latitude || 10.2805,
    default_longitude: props.user.default_longitude || 9.8223,
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch(route('consumer.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.password = '';
            profileForm.password_confirmation = '';
        },
    });
};

const cancelOrder = (orderId) => {
    if (!confirm('Are you sure you want to cancel this order? Stock will be restored.')) return;
    
    useForm({}).patch(route('consumer.orders.cancel', orderId), {
        preserveScroll: true,
    });
};

const filteredOrders = computed(() => {
    if (statusFilter.value === 'all') return props.orders;
    if (statusFilter.value === 'active') {
        return props.orders.filter(o => ['pending', 'packed', 'out_for_delivery'].includes(o.status));
    }
    return props.orders.filter(o => o.status === statusFilter.value);
});

const activeOrdersCount = computed(() => {
    return props.orders.filter(o => ['pending', 'packed', 'out_for_delivery'].includes(o.status)).length;
});

const completedOrdersCount = computed(() => {
    return props.orders.filter(o => o.status === 'delivered').length;
});

const getStatusBadge = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-100 text-amber-800 border-amber-300';
        case 'packed': return 'bg-blue-100 text-blue-800 border-blue-300';
        case 'out_for_delivery': return 'bg-purple-100 text-purple-800 border-purple-300';
        case 'delivered': return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        case 'cancelled': return 'bg-rose-100 text-rose-800 border-rose-300';
        default: return 'bg-slate-100 text-slate-800 border-slate-300';
    }
};

const formatStatusText = (status) => {
    switch (status) {
        case 'pending': return '⏳ Pending Confirmation';
        case 'packed': return '📦 Packed & Ready';
        case 'out_for_delivery': return '🛵 Out for Delivery';
        case 'delivered': return '✅ Delivered';
        case 'cancelled': return '❌ Cancelled';
        default: return status;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="My Account & Orders - BoozeApp Gwallameji" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <!-- Top Banner Header -->
        <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-30 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <Link href="/" class="flex items-center space-x-2 text-amber-500 font-extrabold text-xl tracking-wider">
                        <span>🍺 BOOZE</span>
                        <span class="text-xs bg-amber-500/20 text-amber-400 border border-amber-500/40 px-2 py-0.5 rounded-full font-mono uppercase">Gwallameji</span>
                    </Link>
                </div>

                <div class="flex items-center space-x-4">
                    <Link href="/" class="inline-flex items-center space-x-1 text-sm bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-3 py-1.5 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 11h14l1 12H4L5 11z"/></svg>
                        <span>Back to Shop</span>
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="text-xs text-slate-400 hover:text-rose-400 border border-slate-700 hover:border-rose-500/40 px-2.5 py-1.5 rounded-lg transition-colors">
                        Logout
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- User Welcome Card -->
            <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-slate-900 border border-amber-500/20 rounded-2xl p-6 mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl">
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-black text-slate-100">Welcome, {{ user.name }}</h1>
                        <span class="text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full">Verified Consumer</span>
                    </div>
                    <p class="text-sm text-slate-400 mt-1">
                        📱 Phone: <span class="text-amber-400 font-mono">{{ user.phone }}</span> | ✉️ Email: <span class="text-slate-300 font-mono">{{ user.email }}</span>
                    </p>
                </div>

                <div class="flex items-center space-x-2 bg-slate-900/80 p-1.5 rounded-xl border border-slate-800">
                    <button 
                        @click="activeTab = 'orders'"
                        :class="[
                            'px-4 py-2 text-sm font-semibold rounded-lg transition-all',
                            activeTab === 'orders' ? 'bg-amber-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        📦 My Orders ({{ orders.length }})
                    </button>
                    <button 
                        @click="activeTab = 'profile'"
                        :class="[
                            'px-4 py-2 text-sm font-semibold rounded-lg transition-all',
                            activeTab === 'profile' ? 'bg-amber-500 text-slate-950 shadow-md' : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        👤 Profile & Default Address
                    </button>
                </div>
            </div>

            <!-- Toast Success Flash Alert -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 px-4 py-3 rounded-xl flex items-center justify-between text-sm shadow-lg">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>

            <!-- TAB 1: ORDERS HISTORY -->
            <div v-if="activeTab === 'orders'">
                <!-- Stat Summary Row -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-medium">Total Orders Placed</p>
                            <p class="text-2xl font-black text-amber-400 mt-0.5">{{ orders.length }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-between p-2.5 text-amber-400">
                            📋
                        </div>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-medium">Active In-Progress</p>
                            <p class="text-2xl font-black text-blue-400 mt-0.5">{{ activeOrdersCount }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-between p-2.5 text-blue-400">
                            🛵
                        </div>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-medium">Completed Deliveries</p>
                            <p class="text-2xl font-black text-emerald-400 mt-0.5">{{ completedOrdersCount }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-between p-2.5 text-emerald-400">
                            ✅
                        </div>
                    </div>
                </div>

                <!-- Status Filter Bar -->
                <div class="flex items-center space-x-2 overflow-x-auto pb-3 mb-4">
                    <button 
                        @click="statusFilter = 'all'"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all', statusFilter === 'all' ? 'bg-slate-800 text-amber-400 border-amber-500/50' : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200']"
                    >
                        All Orders ({{ orders.length }})
                    </button>
                    <button 
                        @click="statusFilter = 'active'"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all', statusFilter === 'active' ? 'bg-slate-800 text-amber-400 border-amber-500/50' : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200']"
                    >
                        ⚡ Active Orders ({{ activeOrdersCount }})
                    </button>
                    <button 
                        @click="statusFilter = 'delivered'"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all', statusFilter === 'delivered' ? 'bg-slate-800 text-amber-400 border-amber-500/50' : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200']"
                    >
                        ✅ Delivered
                    </button>
                    <button 
                        @click="statusFilter = 'cancelled'"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all', statusFilter === 'cancelled' ? 'bg-slate-800 text-amber-400 border-amber-500/50' : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200']"
                    >
                        ❌ Cancelled
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="filteredOrders.length === 0" class="bg-slate-900/50 border border-slate-800 rounded-2xl p-12 text-center">
                    <div class="text-4xl mb-3">🍺</div>
                    <h3 class="text-lg font-bold text-slate-200">No orders found</h3>
                    <p class="text-slate-400 text-sm mt-1">You haven't placed any orders matching this filter yet.</p>
                    <Link href="/" class="mt-4 inline-block bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-lg transition-colors text-sm">
                        Start Shopping Now
                    </Link>
                </div>

                <!-- Orders List Grid -->
                <div v-else class="space-y-4">
                    <div 
                        v-for="order in filteredOrders" 
                        :key="order.id"
                        class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-5 transition-all shadow-md"
                    >
                        <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-slate-800 gap-3">
                            <div>
                                <div class="flex items-center space-x-3">
                                    <span class="font-mono font-black text-amber-400 text-lg">#{{ order.order_number }}</span>
                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border', getStatusBadge(order.status)]">
                                        {{ formatStatusText(order.status) }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Placed on {{ formatDate(order.created_at) }}
                                </p>
                            </div>

                            <div class="flex items-center space-x-3">
                                <Link 
                                    :href="route('consumer.orders.show', order.id)" 
                                    class="text-xs bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-3.5 py-2 rounded-xl transition-colors inline-flex items-center space-x-1"
                                >
                                    <span>Track Live Order</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </Link>
                                <button 
                                    v-if="order.status === 'pending'"
                                    @click="cancelOrder(order.id)"
                                    class="text-xs bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 border border-rose-800 px-3 py-2 rounded-xl transition-colors font-medium"
                                >
                                    Cancel Order
                                </button>
                            </div>
                        </div>

                        <!-- Items Preview & Address -->
                        <div class="pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold mb-2">Order Items ({{ order.items.length }})</p>
                                <ul class="space-y-1.5 text-sm">
                                    <li v-for="item in order.items" :key="item.id" class="flex justify-between items-center bg-slate-950/60 px-3 py-1.5 rounded-lg text-xs">
                                        <span class="text-slate-200"><strong class="text-amber-400 font-mono">{{ item.quantity }}x</strong> {{ item.product_name }}</span>
                                        <span class="text-slate-400 font-mono">₦{{ Number(item.subtotal).toLocaleString() }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 flex flex-col justify-between">
                                <div>
                                    <p class="text-xs text-slate-400 uppercase font-semibold">Delivery Address</p>
                                    <p class="text-xs text-slate-300 mt-1 line-clamp-2">📍 {{ order.delivery_address }}</p>
                                </div>
                                <div class="mt-3 pt-2 border-t border-slate-800/80 flex justify-between items-center text-sm">
                                    <span class="text-xs text-slate-400">Total Paid (Inc. Delivery):</span>
                                    <span class="font-extrabold font-mono text-amber-400 text-base">₦{{ Number(order.total).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PROFILE & DEFAULT ADDRESS -->
            <div v-if="activeTab === 'profile'" class="max-w-3xl mx-auto">
                <form @submit.prevent="updateProfile" class="space-y-6">
                    <!-- Personal Info Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
                        <h2 class="text-lg font-bold text-amber-400 flex items-center space-x-2">
                            <span>👤 Personal Account Details</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name</label>
                                <input 
                                    v-model="profileForm.name"
                                    type="text" 
                                    required
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500"
                                />
                                <span v-if="profileForm.errors.name" class="text-xs text-rose-400 mt-1 block">{{ profileForm.errors.name }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Phone Number (Login & Order Alert)</label>
                                <input 
                                    v-model="profileForm.phone"
                                    type="text" 
                                    required
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 font-mono focus:outline-none focus:border-amber-500"
                                />
                                <span v-if="profileForm.errors.phone" class="text-xs text-rose-400 mt-1 block">{{ profileForm.errors.phone }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address</label>
                            <input 
                                v-model="profileForm.email"
                                type="email" 
                                required
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500"
                            />
                            <span v-if="profileForm.errors.email" class="text-xs text-rose-400 mt-1 block">{{ profileForm.errors.email }}</span>
                        </div>
                    </div>

                    <!-- Default Delivery Address Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-amber-400 flex items-center space-x-2">
                                <span>📍 Saved Default Delivery Address</span>
                            </h2>
                            <button
                                type="button"
                                @click="detectCurrentLocation"
                                :disabled="isLocating"
                                class="text-xs bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 px-3 py-1.5 rounded-lg transition-colors font-semibold flex items-center space-x-1"
                            >
                                <span>🎯 {{ isLocating ? 'Detecting...' : 'Use My Current Location' }}</span>
                            </button>
                        </div>

                        <p class="text-xs text-slate-400">
                            Setting your default address here will automatically pre-fill your checkout drawer so you can order in 1-click!
                        </p>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Default Delivery Address (Landmarks / Lodge Name / Room Number)</label>
                            <textarea 
                                v-model="profileForm.default_address"
                                rows="3"
                                placeholder="e.g. Block C, Room 14, Opposite Bayan Gari Lodge, Gwallameji, Bauchi"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500"
                            ></textarea>
                            <span v-if="profileForm.errors.default_address" class="text-xs text-rose-400 mt-1 block">{{ profileForm.errors.default_address }}</span>
                        </div>
                    </div>

                    <!-- Password Update Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
                        <h2 class="text-lg font-bold text-amber-400 flex items-center space-x-2">
                            <span>🔒 Change Password (Optional)</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">New Password</label>
                                <input 
                                    v-model="profileForm.password"
                                    type="password"
                                    placeholder="Leave blank to keep unchanged" 
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500"
                                />
                                <span v-if="profileForm.errors.password" class="text-xs text-rose-400 mt-1 block">{{ profileForm.errors.password }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
                                <input 
                                    v-model="profileForm.password_confirmation"
                                    type="password"
                                    placeholder="Confirm new password" 
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-amber-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Save Action Button -->
                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            :disabled="profileForm.processing"
                            class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-6 py-3 rounded-xl transition-all shadow-lg text-sm flex items-center space-x-2"
                        >
                            <span>{{ profileForm.processing ? 'Saving Changes...' : 'Save Profile & Default Address' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
