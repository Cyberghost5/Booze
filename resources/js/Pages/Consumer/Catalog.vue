<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import LocationPicker from '@/Components/LocationPicker.vue';
import {
    Beer,
    Wine,
    Flame,
    CupSoda,
    Search,
    ShoppingBag,
    ShieldAlert,
    MapPin,
    Plus,
    Minus,
    Trash2,
    X,
    CheckCircle2,
    Lock,
    Phone,
    User,
    Calendar,
    ArrowRight,
    Sparkles,
    Navigation,
    Truck
} from 'lucide-vue-next';

const lastOrderId = ref(null);

onMounted(() => {
    lastOrderId.value = localStorage.getItem('booze_last_order_id');
});

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', category_id: '' }),
    },
    deliveryFee: {
        type: Number,
        default: 500.00,
    },
    activeOrder: {
        type: Object,
        default: () => null,
    },
});

const page = usePage();
const flashError = computed(() => page.props.errors?.dob || page.props.errors?.cart);

// Age Verification Gate State
const isAgeVerified = ref(false);
const dobInput = ref('');
const ageError = ref('');

const checkAgeVerification = () => {
    const verified = localStorage.getItem('booze_dob_verified');
    if (verified === 'true') {
        isAgeVerified.value = true;
    }
};

const verifyAge = () => {
    if (!dobInput.value) {
        ageError.value = 'Please select your Date of Birth.';
        return;
    }

    const birthDate = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    if (age < 18) {
        ageError.value = 'Access Denied: You must be 18 years or older to enter.';
        return;
    }

    localStorage.setItem('booze_dob_verified', 'true');
    localStorage.setItem('booze_user_dob', dobInput.value);
    isAgeVerified.value = true;
    ageError.value = '';
};

onMounted(() => {
    checkAgeVerification();
});

// Search & Filter State
const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || '');

const handleFilterChange = (catId = selectedCategory.value) => {
    selectedCategory.value = catId;
    router.get(
        route('consumer.catalog'),
        { search: searchQuery.value, category_id: selectedCategory.value },
        { preserveState: true, replace: true }
    );
};

// Cart State (Stored in localStorage / reactive)
const cart = ref([]);

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

const addToCart = (product) => {
    const existing = cart.value.find((i) => i.product_id === product.id);
    if (existing) {
        if (existing.quantity < product.stock_level) {
            existing.quantity++;
        }
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            selling_price: parseFloat(product.selling_price),
            image_url: product.image_url,
            unit: product.unit,
            stock_level: product.stock_level,
            quantity: 1,
        });
    }
    saveCart();
    isCartOpen.value = true;
};

const updateQuantity = (productId, delta) => {
    const item = cart.value.find((i) => i.product_id === productId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.value = cart.value.filter((i) => i.product_id !== productId);
    }
    saveCart();
};

const removeFromCart = (productId) => {
    cart.value = cart.value.filter((i) => i.product_id !== productId);
    saveCart();
};

const cartSubtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.selling_price * item.quantity, 0);
});

const cartTotalCount = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const cartGrandTotal = computed(() => {
    return cartSubtotal.value > 0 ? cartSubtotal.value + props.deliveryFee : 0;
});

// Drawers & Modals State
const isCartOpen = ref(false);
const isCheckoutOpen = ref(false);

// Preset Gwallameji Locations
const presetLocations = [
    { name: 'ATBU Gwallameji Gate Lodge', lat: 10.284700, lng: 9.791500 },
    { name: 'Federal Poly Gwallameji Hostel 1', lat: 10.285200, lng: 9.792800 },
    { name: 'Gwallameji Market Square', lat: 10.283100, lng: 9.790200 },
    { name: 'Executive Student Lodge 14', lat: 10.286500, lng: 9.794100 },
];

const selectedPreset = ref(presetLocations[0]);

// Checkout Form
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

const selectPresetLocation = (loc) => {
    selectedPreset.value = loc;
    checkoutForm.delivery_address = loc.name;
    checkoutForm.latitude = loc.lat;
    checkoutForm.longitude = loc.lng;
};

const authUser = computed(() => page.props.auth?.user);

// Auth Modal state for checkout
const authMode = ref('login'); // 'login', 'register', 'otp'
const authLoading = ref(false);
const authError = ref('');
const authSuccess = ref('');

const authForm = ref({
    name: '',
    phone: '',
    password: '',
    otp: '',
});

// Auto-fill checkout form if user is logged in
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
                    submitCheckout();
                },
            });
        }
    } catch (err) {
        authLoading.value = false;
        authError.value = err.response?.data?.message || 'Login failed. Please check your credentials.';
    }
};

const handleModalRegister = async () => {
    authLoading.value = true;
    authError.value = '';
    authSuccess.value = '';
    try {
        const response = await axios.post(route('auth.phone-register'), {
            name: authForm.value.name,
            phone: authForm.value.phone,
            password: authForm.value.password,
        });

        authLoading.value = false;
        if (response.data.success) {
            authSuccess.value = response.data.message;
            authMode.value = 'otp';
        }
    } catch (err) {
        authLoading.value = false;
        authError.value = err.response?.data?.message || 'Registration failed. Please check your details.';
    }
};

const handleModalVerifyOtp = async () => {
    authLoading.value = true;
    authError.value = '';
    authSuccess.value = '';
    try {
        const response = await axios.post(route('auth.phone-verify-otp'), {
            phone: authForm.value.phone,
            otp: authForm.value.otp,
        });

        if (response.data.success) {
            authSuccess.value = response.data.message;
            router.reload({
                only: ['auth', 'activeOrder'],
                onSuccess: () => {
                    authLoading.value = false;
                    checkoutForm.customer_name = response.data.user.name;
                    checkoutForm.customer_phone = response.data.user.phone;
                    submitCheckout();
                },
            });
        }
    } catch (err) {
        authLoading.value = false;
        authError.value = err.response?.data?.message || 'Invalid OTP code.';
    }
};

const resendModalOtp = async () => {
    authLoading.value = true;
    authError.value = '';
    try {
        const response = await axios.post(route('auth.phone-resend-otp'), {
            phone: authForm.value.phone,
        });
        authLoading.value = false;
        authSuccess.value = response.data.message;
    } catch (err) {
        authLoading.value = false;
        authError.value = err.response?.data?.message || 'Failed to resend OTP.';
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

// Category Icon Helper
const getCategoryIcon = (slug) => {
    switch (slug) {
        case 'beers': return Beer;
        case 'spirits': return Flame;
        case 'wines': return Wine;
        case 'mixers': return CupSoda;
        default: return Sparkles;
    }
};
</script>

<template>
    <Head title="Booze App Gwallameji - 18+ Beverage Marketplace" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 font-sans selection:bg-amber-500 selection:text-black">
        <!-- TOP NAV HEADER -->
        <header class="sticky top-0 z-40 border-b border-zinc-900 bg-zinc-950/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
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
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-4">
                    <!-- Search Bar -->
                    <div class="relative hidden sm:block w-64">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                        <input
                            v-model="searchQuery"
                            @input="handleFilterChange()"
                            type="text"
                            placeholder="Search drinks..."
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-900/80 pl-9 pr-4 py-2 text-sm text-zinc-200 placeholder-zinc-500 focus:border-amber-500 focus:outline-none"
                        />
                    </div>

                    <!-- My Account / Dashboard Link -->
                    <Link
                        v-if="authUser"
                        :href="route('dashboard')"
                        class="flex items-center gap-1.5 rounded-xl border border-zinc-800 bg-zinc-900 px-3.5 py-2 text-xs font-bold text-amber-400 hover:border-amber-500/50 hover:bg-zinc-800 transition"
                    >
                        <User class="h-4 w-4 text-amber-400" />
                        <span class="hidden md:inline">{{ authUser.name }}</span>
                        <span class="md:hidden">Account</span>
                    </Link>

                    <!-- Track Order Button if previous order exists -->
                    <Link
                        v-if="lastOrderId"
                        :href="route('consumer.orders.show', lastOrderId)"
                        class="flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs font-bold text-amber-400 hover:bg-amber-500/20 transition"
                    >
                        <Truck class="h-4 w-4" />
                        <span class="hidden md:inline">Track Active Order</span>
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

        <!-- MAIN CATALOG CONTENT -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- ACTIVE ORDER NOTIFICATION BANNER -->
            <div
                v-if="activeOrder"
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

            <!-- HERO BANNER -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-600 via-amber-700 to-zinc-900 p-8 text-white shadow-2xl mb-8">
                <div class="relative z-10 max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-black/40 px-3 py-1 text-xs font-bold text-amber-300 backdrop-blur">
                        ⚡ 15-Min Express Delivery in Gwallameji
                    </span>
                    <h2 class="mt-4 text-3xl font-black sm:text-5xl tracking-tight leading-none">
                        Chilled Booze Delivered to Your Lodge.
                    </h2>
                    <p class="mt-3 text-sm text-amber-100 sm:text-base">
                        Chilled beers, fine spirits, wines, and mixers. Order in seconds, delivered directly to your doorstep.
                    </p>
                </div>
                <div class="absolute -right-10 -bottom-10 opacity-20 text-9xl pointer-events-none">
                    🍻
                </div>
            </div>

            <!-- CATEGORY FILTER TABS -->
            <div class="mb-8 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    @click="handleFilterChange('')"
                    :class="[
                        'flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-bold transition shrink-0',
                        selectedCategory === ''
                            ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20'
                            : 'bg-zinc-900 text-zinc-400 hover:bg-zinc-800 hover:text-white border border-zinc-800'
                    ]"
                >
                    <Sparkles class="h-4 w-4" />
                    All Drinks
                </button>

                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="handleFilterChange(cat.id)"
                    :class="[
                        'flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-bold transition shrink-0',
                        selectedCategory === cat.id
                            ? 'bg-amber-500 text-black shadow-lg shadow-amber-500/20'
                            : 'bg-zinc-900 text-zinc-400 hover:bg-zinc-800 hover:text-white border border-zinc-800'
                    ]"
                >
                    <component :is="getCategoryIcon(cat.slug)" class="h-4 w-4" />
                    {{ cat.name }}
                </button>
            </div>

            <!-- PRODUCT GRID -->
            <div v-if="products.length === 0" class="rounded-3xl border border-zinc-800 bg-zinc-900/50 p-12 text-center">
                <Beer class="mx-auto h-12 w-12 text-zinc-600" />
                <h3 class="mt-4 text-lg font-bold text-white">No active drinks found</h3>
                <p class="mt-2 text-sm text-zinc-400">Try changing your search query or category filter.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-zinc-800/80 bg-zinc-900/60 p-5 backdrop-blur transition hover:border-amber-500/50 hover:shadow-xl hover:shadow-amber-500/5"
                >
                    <div>
                        <!-- Product Image -->
                        <div class="relative aspect-square w-full overflow-hidden rounded-2xl bg-zinc-950 mb-4">
                            <img
                                :src="product.image_url"
                                :alt="product.name"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-110"
                            />
                            <span class="absolute top-2 left-2 rounded-full bg-black/70 px-2.5 py-0.5 text-xs font-semibold text-zinc-300 backdrop-blur">
                                {{ product.unit }}
                            </span>
                            <span
                                v-if="product.stock_level <= 10"
                                class="absolute top-2 right-2 rounded-full bg-amber-500/90 px-2.5 py-0.5 text-xs font-bold text-black"
                            >
                                Only {{ product.stock_level }} left
                            </span>
                        </div>

                        <!-- Product Title & Info -->
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-500">
                            {{ product.category?.name || 'Beverage' }}
                        </span>
                        <h3 class="text-base font-bold text-white mt-1 group-hover:text-amber-400 transition">
                            {{ product.name }}
                        </h3>
                        <p class="mt-1 text-xs text-zinc-400 line-clamp-2">
                            {{ product.description || 'Chilled & ready for delivery.' }}
                        </p>
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-zinc-800/60 pt-4">
                        <div>
                            <span class="block text-xs text-zinc-500">Price</span>
                            <span class="text-lg font-black text-white">{{ formatNaira(product.selling_price) }}</span>
                        </div>

                        <button
                            @click="addToCart(product)"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-black text-black shadow hover:bg-amber-400 active:scale-95"
                        >
                            <Plus class="h-4 w-4" />
                            Add
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- MANDATORY 18+ DOB AGE VERIFICATION GATE MODAL -->
        <div v-if="!isAgeVerified" class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-xl p-4">
            <div class="w-full max-w-md rounded-3xl border border-zinc-800 bg-zinc-900 p-8 text-center shadow-2xl">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 mb-4 border border-amber-500/20">
                    <Lock class="h-8 w-8" />
                </div>

                <h2 class="text-2xl font-black text-white">Age Verification Required</h2>
                <p class="mt-2 text-sm text-zinc-400">
                    Booze App Gwallameji is strictly restricted to individuals <strong>18 years of age or older</strong>. Please enter your Date of Birth to enter.
                </p>

                <div class="mt-6 text-left space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-1">
                            Date of Birth
                        </label>
                        <input
                            v-model="dobInput"
                            type="date"
                            required
                            class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-white focus:border-amber-500 focus:outline-none"
                        />
                    </div>

                    <div v-if="ageError" class="rounded-xl bg-red-950/60 border border-red-500/30 p-3 text-xs text-red-300 font-semibold flex items-center gap-2">
                        <ShieldAlert class="h-4 w-4 text-red-400 shrink-0" />
                        {{ ageError }}
                    </div>

                    <button
                        @click="verifyAge"
                        class="w-full rounded-xl bg-amber-500 py-3.5 text-sm font-black text-black shadow-lg shadow-amber-500/20 transition hover:bg-amber-400 active:scale-95"
                    >
                        Confirm & Enter App
                    </button>
                </div>

                <p class="mt-4 text-xs text-zinc-500">
                    By entering, you confirm that you meet the legal drinking age requirements in Nigeria.
                </p>
            </div>
        </div>

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

                <!-- Active Order Warning inside Checkout -->
                <div v-if="activeOrder || checkoutForm.errors?.active_order || page.props.errors?.active_order" class="mt-4 rounded-2xl bg-amber-950/80 border border-amber-500/40 p-4 text-amber-200 text-xs space-y-3">
                    <div class="flex items-start gap-2">
                        <Truck class="h-5 w-5 text-amber-400 shrink-0 mt-0.5" />
                        <div>
                            <strong class="text-sm font-bold text-amber-300 block mb-1">Active Order In Progress</strong>
                            <p>{{ checkoutForm.errors?.active_order || page.props.errors?.active_order || `You have an active order (#${activeOrder?.order_number}) in progress. You cannot place another order until your current order is delivered or cancelled.` }}</p>
                        </div>
                    </div>
                    <Link
                        v-if="activeOrder?.id || checkoutForm.errors?.active_order_id || page.props.errors?.active_order_id"
                        :href="route('consumer.orders.show', activeOrder?.id || checkoutForm.errors?.active_order_id || page.props.errors?.active_order_id)"
                        class="inline-flex items-center gap-1 rounded-xl bg-amber-500 px-4 py-2 font-bold text-black hover:bg-amber-400 transition"
                    >
                        Track Active Order <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <!-- FAST MODAL AUTHORIZATION FOR UNAUTHENTICATED USERS -->
                <div v-if="!authUser" class="my-4 rounded-2xl border border-amber-500/30 bg-zinc-950 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                            🔒 Fast Authentication Required
                        </span>
                        <div class="flex rounded-lg bg-zinc-900 p-0.5 text-xs font-bold">
                            <button
                                type="button"
                                @click="authMode = 'login'; authError = ''; authSuccess = '';"
                                :class="['px-3 py-1 rounded-md transition', authMode === 'login' ? 'bg-amber-500 text-black' : 'text-zinc-400 hover:text-white']"
                            >
                                Login
                            </button>
                            <button
                                type="button"
                                @click="authMode = 'register'; authError = ''; authSuccess = '';"
                                :class="['px-3 py-1 rounded-md transition', authMode === 'register' ? 'bg-amber-500 text-black' : 'text-zinc-400 hover:text-white']"
                            >
                                Register
                            </button>
                        </div>
                    </div>

                    <!-- Mode 1: FAST LOGIN -->
                    <form v-if="authMode === 'login'" @submit.prevent="handleModalLogin" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Phone Number</label>
                            <input
                                v-model="authForm.phone"
                                type="tel"
                                placeholder="09031704109"
                                required
                                class="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Password</label>
                            <input
                                v-model="authForm.password"
                                type="password"
                                placeholder="••••••••"
                                required
                                class="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="authLoading"
                            class="w-full rounded-xl bg-amber-500 py-2.5 text-xs font-black text-black hover:bg-amber-400 transition"
                        >
                            {{ authLoading ? 'Signing In...' : 'Fast Login & Continue to Order' }}
                        </button>
                    </form>

                    <!-- Mode 2: FAST REGISTER -->
                    <form v-else-if="authMode === 'register'" @submit.prevent="handleModalRegister" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Full Name</label>
                            <input
                                v-model="authForm.name"
                                type="text"
                                placeholder="e.g. Amina Student"
                                required
                                class="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Phone Number</label>
                            <input
                                v-model="authForm.phone"
                                type="tel"
                                placeholder="09031704109"
                                required
                                class="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">Password</label>
                            <input
                                v-model="authForm.password"
                                type="password"
                                placeholder="Minimum 6 characters"
                                required
                                minlength="6"
                                class="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="authLoading"
                            class="w-full rounded-xl bg-amber-500 py-2.5 text-xs font-black text-black hover:bg-amber-400 transition"
                        >
                            {{ authLoading ? 'Sending OTP SMS...' : 'Register & Send SMS OTP' }}
                        </button>
                    </form>

                    <!-- Mode 3: OTP VERIFICATION -->
                    <form v-else-if="authMode === 'otp'" @submit.prevent="handleModalVerifyOtp" class="space-y-3">
                        <p class="text-xs text-amber-300">
                            📲 Verification OTP sent to <strong>{{ authForm.phone }}</strong> via BulkSMS Nigeria. Please enter the 6-digit code below:
                        </p>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-400 mb-1">6-Digit Verification OTP</label>
                            <input
                                v-model="authForm.otp"
                                type="text"
                                placeholder="123456"
                                maxlength="6"
                                required
                                class="w-full text-center tracking-widest text-lg font-mono rounded-xl border border-amber-500/50 bg-zinc-900 px-3 py-2 text-amber-400 focus:border-amber-400 focus:outline-none"
                            />
                        </div>
                        <div class="flex items-center justify-between">
                            <button
                                type="button"
                                @click="resendModalOtp"
                                :disabled="authLoading"
                                class="text-xs text-amber-400 underline hover:text-amber-300"
                            >
                                Resend SMS OTP
                            </button>
                            <button
                                type="submit"
                                :disabled="authLoading"
                                class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-black text-black hover:bg-amber-400 transition"
                            >
                                {{ authLoading ? 'Verifying...' : 'Verify OTP & Complete Order' }}
                            </button>
                        </div>
                    </form>

                    <!-- Error / Success Feedback Banners -->
                    <div v-if="authError" class="rounded-xl bg-red-950/60 border border-red-500/40 p-2.5 text-xs text-red-300 font-semibold">
                        {{ authError }}
                    </div>
                    <div v-if="authSuccess" class="rounded-xl bg-emerald-950/60 border border-emerald-500/40 p-2.5 text-xs text-emerald-300 font-semibold">
                        {{ authSuccess }}
                    </div>
                </div>

                <div v-else class="mt-4">
                    <form @submit.prevent="submitCheckout" class="space-y-4">
                    <!-- Logged In User Status Badge -->
                    <div class="rounded-xl bg-zinc-950 border border-zinc-800 p-3 flex items-center justify-between text-xs text-zinc-300">
                        <span>Logged in as <strong>{{ authUser.name }}</strong> ({{ authUser.phone }})</span>
                        <span class="text-amber-400 font-bold">✓ Verified</span>
                    </div>

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

                    <!-- INTERACTIVE LOCATION & ADDRESS PICKER WITH LEAFLET MAP & GPS DETECT -->
                    <LocationPicker
                        v-model:address="checkoutForm.delivery_address"
                        v-model:latitude="checkoutForm.latitude"
                        v-model:longitude="checkoutForm.longitude"
                    />

                    <!-- SAVE AS DEFAULT ADDRESS CHECKBOX -->
                    <div class="flex items-center gap-2 px-1">
                        <input
                            id="save_default_addr"
                            v-model="checkoutForm.save_as_default_address"
                            type="checkbox"
                            class="rounded border-zinc-700 bg-zinc-950 text-amber-500 focus:ring-amber-500 h-4 w-4"
                        />
                        <label for="save_default_addr" class="text-xs text-zinc-300 font-medium">
                            Save as my default delivery address for future 1-click ordering
                        </label>
                    </div>

                    <!-- ORDER SUMMARY -->
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

                    <div v-if="flashError" class="rounded-xl bg-red-950/60 border border-red-500/30 p-3 text-xs text-red-300 font-semibold">
                        {{ flashError }}
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
                            :disabled="checkoutForm.processing || activeOrder !== null"
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
