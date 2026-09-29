<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import {
    ShoppingBag,
    Trash2,
    Plus,
    Minus,
    ArrowRight,
    ArrowLeft,
    Check,
    X,
    MapPin,
    Lock,
    ShieldAlert,
    User,
    Truck,
    Sparkles,
    Flame,
    Beer,
    Wine,
    CupSoda,
    PartyPopper,
} from 'lucide-vue-next';
import axios from 'axios';
import LocationPicker from '@/Components/LocationPicker.vue';

const props = defineProps({
    partyBundles: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    deliveryFee: {
        type: Number,
        default: 500.00,
    },
    activeOrder: {
        type: Object,
        default: () => null,
    },
    deliveryLocations: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flashError = computed(() => page.props.errors?.dob || page.props.errors?.cart);

// Cart State (Persisted in localStorage)
const cart = ref([]);
const isCartOpen = ref(false);

const loadCart = () => {
    const saved = localStorage.getItem('booze_cart');
    if (saved) {
        try {
            cart.value = JSON.parse(saved);
        } catch (e) {
            cart.value = [];
        }
    }
};

const saveCart = () => {
    localStorage.setItem('booze_cart', JSON.stringify(cart.value));
};

onMounted(() => {
    loadCart();
});

const addBundleToCart = (bundle) => {
    if (!bundle.items || bundle.items.length === 0) return;

    bundle.items.forEach(item => {
        if (!item.product) return;
        const existing = cart.value.find(i => i.product_id === item.product.id);
        if (existing) {
            existing.quantity += item.quantity;
        } else {
            cart.value.push({
                product_id: item.product.id,
                name: item.product.name,
                selling_price: item.product.selling_price,
                image_url: item.product.image_url,
                quantity: item.quantity,
            });
        }
    });

    saveCart();
    isCartOpen.value = true;
};

const updateQuantity = (productId, delta) => {
    const item = cart.value.find(i => i.product_id === productId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.value = cart.value.filter(i => i.product_id !== productId);
    }
    saveCart();
};

const removeFromCart = (productId) => {
    cart.value = cart.value.filter(i => i.product_id !== productId);
    saveCart();
};

const cartTotalCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const cartSubtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.selling_price * item.quantity), 0);
});

const cartGrandTotal = computed(() => {
    if (cart.value.length === 0) return 0;
    return cartSubtotal.value + props.deliveryFee;
});

// Checkout Modal & Location Picker State
const isCheckoutOpen = ref(false);
const presetLocations = [
    { name: 'Gwallameji Junction, Bauchi', lat: 10.2845, lng: 9.7912 },
    { name: 'Executive Lodge, Gwallameji', lat: 10.2847, lng: 9.7915 },
    { name: 'Palace Lodge, Gwallameji', lat: 10.2850, lng: 9.7920 },
    { name: 'ATBU Campus Gate, Gwallameji', lat: 10.2840, lng: 9.7905 },
];

const selectedPreset = ref(presetLocations[0]);

const checkoutForm = useForm({
    customer_name: '',
    customer_phone: '',
    delivery_address: presetLocations[0].name,
    latitude: presetLocations[0].lat,
    longitude: presetLocations[0].lng,
    dob: localStorage.getItem('booze_user_dob') || '2002-01-01',
    notes: '',
    save_as_default_address: true,
    items: [],
});

const authUser = computed(() => page.props.auth?.user);

// Auth Modal state for checkout
const authMode = ref('login');
const authLoading = ref(false);
const authError = ref('');
const authSuccess = ref('');

const authForm = ref({
    name: '',
    phone: '',
    password: '',
    otp: '',
});

watch(authUser, (user) => {
    if (user) {
        checkoutForm.customer_name = user.name || '';
        checkoutForm.customer_phone = user.phone || '';
        if (user.default_address) {
            checkoutForm.delivery_address = user.default_address;
        }
        if (user.default_latitude && user.default_longitude) {
            checkoutForm.latitude = parseFloat(user.default_latitude);
            checkoutForm.longitude = parseFloat(user.default_longitude);
        }
        if (user.phone) {
            authForm.value.phone = user.phone;
        }
    }
}, { immediate: true });

const handleModalLogin = async () => {
    authLoading.value = true;
    authError.value = '';
    authSuccess.value = '';
    try {
        const response = await axios.post(route('auth.phone-login'), {
            phone: authForm.value.phone,
            password: authForm.value.password,
        });

        if (response.data.success) {
            authSuccess.value = response.data.message;
            router.reload({
                only: ['auth', 'activeOrder'],
                onSuccess: () => {
                    authLoading.value = false;
                    checkoutForm.customer_name = response.data.user.name;
                    checkoutForm.customer_phone = response.data.user.phone;
                },
            });
        }
    } catch (err) {
        authLoading.value = false;
        authError.value = err.response?.data?.message || 'Login failed. Please check your credentials.';
    }
};

const openCheckout = () => {
    checkoutForm.items = cart.value.map((i) => ({
        product_id: i.product_id,
        quantity: i.quantity,
    }));
    isCartOpen.value = false;
    isCheckoutOpen.value = true;
};

const submitCheckout = () => {
    if (!authUser.value) {
        authMode.value = 'login';
        return;
    }

    checkoutForm.dob = localStorage.getItem('booze_user_dob') || '2002-01-01';
    checkoutForm.post(route('consumer.checkout'), {
        onSuccess: () => {
            cart.value = [];
            localStorage.removeItem('booze_cart');
            isCheckoutOpen.value = false;
        },
    });
};

const formatNaira = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 2,
    }).format(amount || 0);
};
</script>

<template>
    <Head title="Party Bundles & Combo Packs - Booze App Gwallameji" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-amber-500 selection:text-black">
        <!-- TOP NAV HEADER -->
        <header class="sticky top-0 z-40 border-b border-zinc-900 bg-zinc-950/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <Link :href="route('consumer.catalog')" class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 font-extrabold text-black shadow-lg shadow-amber-500/20">
                            🍹
                        </div>
                        <div>
                            <h1 class="text-xl font-black tracking-wider text-white">BOOZE<span class="text-amber-500">.</span></h1>
                            <span class="inline-flex items-center gap-1 text-xs text-zinc-400">
                                <MapPin class="h-3 w-3 text-amber-500" />
                                Gwallameji Axis, Bauchi
                            </span>
                        </div>
                    </Link>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex items-center gap-2 ml-6">
                        <Link
                            :href="route('consumer.catalog')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-zinc-400 hover:text-white hover:bg-zinc-900 transition"
                        >
                            🍺 All Drinks Catalog
                        </Link>
                        <Link
                            :href="route('consumer.party-bundles')"
                            class="px-3.5 py-2 rounded-xl text-xs font-black bg-amber-500/10 text-amber-400 border border-amber-500/30 transition flex items-center gap-1.5"
                        >
                            <span>📦 Party Bundles</span>
                            <span class="bg-amber-500 text-black px-1.5 py-0.2 rounded-md text-[10px]">HOT</span>
                        </Link>
                    </nav>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2.5 sm:gap-4">
                    <!-- Link back to catalog on mobile -->
                    <Link
                        :href="route('consumer.catalog')"
                        class="md:hidden flex items-center gap-1.5 rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-xs font-bold text-zinc-300 hover:text-white transition"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        <span>Catalog</span>
                    </Link>

                    <!-- My Account / Dashboard Link -->
                    <Link
                        v-if="authUser"
                        :href="route('dashboard')"
                        class="flex items-center gap-1.5 rounded-xl border border-zinc-800 bg-zinc-900 px-3.5 py-2 text-xs font-bold text-amber-400 hover:border-amber-500/50 hover:bg-zinc-800 transition"
                    >
                        <User class="h-4 w-4 text-amber-400" />
                        <span class="hidden md:inline">{{ authUser.name }}</span>
                    </Link>

                    <!-- Cart Drawer Trigger Button -->
                    <button
                        @click="isCartOpen = true"
                        class="relative flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-black shadow-lg shadow-amber-500/20 transition hover:bg-amber-400 active:scale-95"
                    >
                        <ShoppingBag class="h-4 w-4" />
                        <span class="hidden sm:inline">Cart</span>
                        <span
                            v-if="cartTotalCount > 0"
                            class="flex h-5 w-5 items-center justify-center rounded-full bg-black text-xs font-black text-amber-400"
                        >
                            {{ cartTotalCount }}
                        </span>
                    </button>
                </div>
            </div>
        </header>

        <!-- MAIN PAGE CONTENT -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- BREADCRUMB / BACK LINK -->
            <div class="mb-6 flex items-center justify-between">
                <Link
                    :href="route('consumer.catalog')"
                    class="inline-flex items-center gap-2 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to All Drinks Catalog
                </Link>
            </div>

            <!-- HERO BANNER FOR PARTY BUNDLES -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-600 via-amber-700 to-zinc-900 p-8 text-white shadow-2xl mb-10">
                <div class="relative z-10 max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-black/40 px-3.5 py-1 text-xs font-bold text-amber-300 backdrop-blur">
                        🎉 Exclusive Party Packs & Student Combos
                    </span>
                    <h2 class="mt-4 text-3xl font-black sm:text-5xl tracking-tight leading-none">
                        Party Bundles & Combo Packs.
                    </h2>
                    <p class="mt-3 text-sm text-amber-100 sm:text-base">
                        Save up to 20% on curated drink combos! Perfect for hostel celebrations, weekend chillouts, and room chasers.
                    </p>
                </div>
                <div class="absolute -right-10 -bottom-10 opacity-20 text-9xl pointer-events-none">
                    🍾
                </div>
            </div>

            <!-- ACTIVE ORDER NOTIFICATION BANNER -->
            <div
                v-if="authUser && activeOrder"
                class="mb-8 rounded-3xl border border-amber-500/40 bg-amber-500/10 p-5 backdrop-blur flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xl shadow-amber-500/5"
            >
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-400 shrink-0">
                        <Truck class="h-6 w-6 animate-pulse" />
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-white">
                            Active Order #{{ activeOrder.order_number }} in Progress
                        </h4>
                        <p class="text-xs text-amber-200 mt-0.5">
                            Status: <strong class="uppercase text-amber-400">{{ activeOrder.status }}</strong> — Your order is currently being prepared/delivered in Gwallameji.
                        </p>
                    </div>
                </div>

                <Link
                    :href="route('consumer.orders.show', activeOrder.id)"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-black shadow-lg hover:bg-amber-400 transition shrink-0"
                >
                    Track Active Order
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>

            <!-- PARTY BUNDLES GRID -->
            <div v-if="partyBundles && partyBundles.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div
                    v-for="bundle in partyBundles"
                    :key="bundle.id"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-800 bg-zinc-900/90 p-6 shadow-xl hover:border-amber-500/50 transition-all duration-300"
                >
                    <!-- Visual Top Section -->
                    <div>
                        <div class="relative h-48 w-full overflow-hidden rounded-2xl bg-zinc-950 mb-5">
                            <img
                                :src="bundle.image_url"
                                :alt="bundle.title"
                                class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent"></div>
                            
                            <span
                                v-if="bundle.badge_text"
                                class="absolute top-3 left-3 rounded-full bg-amber-500 text-black px-3 py-1 text-[11px] font-black uppercase tracking-wide shadow-md"
                            >
                                {{ bundle.badge_text }}
                            </span>
                        </div>

                        <h4 class="text-xl font-black text-white group-hover:text-amber-400 transition">
                            {{ bundle.title }}
                        </h4>
                        <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                            {{ bundle.description }}
                        </p>

                        <!-- Items Breakdown Pill List -->
                        <div class="mt-4 pt-4 border-t border-zinc-800/80 space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block">
                                Includes in this Pack:
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="item in bundle.items"
                                    :key="item.id"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-950 px-2.5 py-1 text-xs font-semibold text-zinc-300 border border-zinc-800"
                                >
                                    <span class="font-black text-amber-400">{{ item.quantity }}x</span>
                                    <span>{{ item.product?.name || 'Drink Item' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Price & Action -->
                    <div class="mt-6 pt-4 border-t border-zinc-800 flex items-center justify-between">
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-black text-amber-500">
                                    {{ formatNaira(bundle.price) }}
                                </span>
                                <span v-if="bundle.original_price" class="text-xs font-bold text-zinc-500 line-through">
                                    {{ formatNaira(bundle.original_price) }}
                                </span>
                            </div>
                            <span v-if="bundle.discount_percentage" class="text-[10px] font-extrabold text-emerald-400 block mt-0.5">
                                Save {{ bundle.discount_percentage }}% instantly
                            </span>
                        </div>

                        <button
                            @click="addBundleToCart(bundle)"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-black shadow-lg shadow-amber-500/20 hover:bg-amber-400 active:scale-95 transition"
                        >
                            <span>Add Combo to Cart</span>
                            <Plus class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-3xl border border-zinc-800 bg-zinc-900 p-12 text-center">
                <PartyPopper class="h-12 w-12 text-zinc-600 mx-auto mb-4" />
                <h3 class="text-lg font-bold text-white">No Party Bundles Available Right Now</h3>
                <p class="text-xs text-zinc-400 mt-1 max-w-sm mx-auto">
                    Check back soon for new combo packs, or explore our full drinks catalog!
                </p>
                <Link
                    :href="route('consumer.catalog')"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-black text-black shadow"
                >
                    Browse Drinks Catalog
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </main>

        <!-- CART SLIDE-OVER DRAWER -->
        <div v-if="isCartOpen" class="fixed inset-0 z-50 flex justify-end bg-black/70 backdrop-blur-sm">
            <div class="flex h-full w-full max-w-md flex-col bg-zinc-900 border-l border-zinc-800 p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <h3 class="text-lg font-black text-white flex items-center gap-2">
                        <ShoppingBag class="h-5 w-5 text-amber-500" />
                        Your Order Cart ({{ cartTotalCount }})
                    </h3>
                    <button @click="isCartOpen = false" class="text-zinc-400 hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="cart.length === 0" class="flex flex-1 flex-col items-center justify-center text-center">
                    <ShoppingBag class="h-12 w-12 text-zinc-600 mb-3" />
                    <p class="text-sm text-zinc-400">Your cart is empty.</p>
                </div>

                <div v-else class="flex-1 overflow-y-auto py-4 space-y-4">
                    <div
                        v-for="item in cart"
                        :key="item.product_id"
                        class="flex items-center justify-between rounded-2xl border border-zinc-800/80 bg-zinc-950/60 p-4"
                    >
                        <div class="flex items-center gap-3">
                            <img :src="item.image_url" :alt="item.name" class="h-12 w-12 rounded-xl object-cover" />
                            <div>
                                <h4 class="text-sm font-bold text-white">{{ item.name }}</h4>
                                <span class="text-xs text-amber-500 font-semibold">{{ formatNaira(item.selling_price) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center rounded-lg border border-zinc-800 bg-zinc-900">
                                <button @click="updateQuantity(item.product_id, -1)" class="px-2 py-1 text-zinc-400 hover:text-white">
                                    <Minus class="h-3 w-3" />
                                </button>
                                <span class="px-2 text-xs font-bold text-white">{{ item.quantity }}</span>
                                <button @click="updateQuantity(item.product_id, 1)" class="px-2 py-1 text-zinc-400 hover:text-white">
                                    <Plus class="h-3 w-3" />
                                </button>
                            </div>

                            <button @click="removeFromCart(item.product_id)" class="text-red-500 hover:text-red-400">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CART FOOTER SUMMARY -->
                <div v-if="cart.length > 0" class="border-t border-zinc-800 pt-4 space-y-3">
                    <div class="flex justify-between text-xs text-zinc-400">
                        <span>Items Subtotal:</span>
                        <span>{{ formatNaira(cartSubtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-zinc-400">
                        <span>Delivery Fee:</span>
                        <span class="text-amber-400 font-bold">{{ formatNaira(deliveryFee) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-black text-white pt-2 border-t border-zinc-800">
                        <span>Total:</span>
                        <span class="text-amber-500">{{ formatNaira(cartGrandTotal) }}</span>
                    </div>

                    <button
                        @click="openCheckout"
                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-amber-500 py-3.5 text-sm font-black text-black shadow-lg shadow-amber-500/20 hover:bg-amber-400 active:scale-95"
                    >
                        Proceed to Checkout <ArrowRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- CHECKOUT & GWALLAMEJI PIN DROP MODAL -->
        <div v-if="isCheckoutOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4">
            <div class="w-full max-w-xl rounded-3xl border border-zinc-800 bg-zinc-900 p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
                    <h3 class="text-lg font-black text-white flex items-center gap-2">
                        <MapPin class="h-5 w-5 text-amber-500" />
                        Checkout
                    </h3>
                    <button @click="isCheckoutOpen = false" class="text-zinc-400 hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-4">
                    <form @submit.prevent="submitCheckout" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Your Name</label>
                                <input
                                    v-model="checkoutForm.customer_name"
                                    type="text"
                                    placeholder="e.g. John Student"
                                    required
                                    class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Phone Number</label>
                                <input
                                    v-model="checkoutForm.customer_phone"
                                    type="tel"
                                    placeholder="09031704109"
                                    required
                                    class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- 1-TAP SAVED LOCATIONS QUICK SELECTOR -->
                        <div v-if="deliveryLocations && deliveryLocations.length > 0" class="rounded-2xl bg-zinc-950 p-3.5 border border-zinc-800 space-y-2">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-400 block">
                                📍 Pick Saved Delivery Location
                            </span>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="loc in deliveryLocations"
                                    :key="loc.id"
                                    type="button"
                                    @click="checkoutForm.delivery_address = loc.address; if (loc.latitude) checkoutForm.latitude = Number(loc.latitude); if (loc.longitude) checkoutForm.longitude = Number(loc.longitude);"
                                    :class="[
                                        'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border',
                                        checkoutForm.delivery_address === loc.address
                                            ? 'bg-amber-500 text-black border-amber-400 shadow-md'
                                            : 'bg-zinc-900 text-zinc-300 hover:text-white border-zinc-800'
                                    ]"
                                >
                                    <span>{{ loc.label }}</span>
                                </button>
                            </div>
                        </div>

                        <LocationPicker
                            v-model:address="checkoutForm.delivery_address"
                            v-model:latitude="checkoutForm.latitude"
                            v-model:longitude="checkoutForm.longitude"
                        />

                        <div class="rounded-2xl bg-zinc-950 p-4 border border-zinc-800 space-y-2 text-xs">
                            <div class="flex justify-between text-zinc-400">
                                <span>Subtotal ({{ cartTotalCount }} items):</span>
                                <span>{{ formatNaira(cartSubtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-400">
                                <span>Delivery Fee:</span>
                                <span class="text-amber-400 font-bold">{{ formatNaira(deliveryFee) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-black text-white pt-2 border-t border-zinc-800">
                                <span>Total Payable:</span>
                                <span class="text-amber-500">{{ formatNaira(cartGrandTotal) }}</span>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 border-t border-zinc-800 pt-4">
                            <button
                                type="button"
                                @click="isCheckoutOpen = false"
                                class="rounded-xl border border-zinc-800 px-4 py-2.5 text-xs font-bold text-zinc-400 hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="checkoutForm.processing"
                                class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-black text-black shadow-lg shadow-amber-500/20 hover:bg-amber-400 active:scale-95 disabled:opacity-50"
                            >
                                Confirm & Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
