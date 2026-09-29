<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    Plus,
    Search,
    AlertTriangle,
    TrendingUp,
    CheckCircle2,
    Trash2,
    Edit3,
    Sparkles,
    DollarSign,
    Layers,
    Store,
    ArrowUpRight,
    X,
    Upload,
    Minus,
    PlusCircle
} from 'lucide-vue-next';
import DragDropImageUploader from '@/Components/DragDropImageUploader.vue';

const props = defineProps({
    myStock: {
        type: Array,
        default: () => [],
    },
    globalCatalog: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', category_id: '', tab: 'my_stock' }),
    },
    metrics: {
        type: Object,
        default: () => ({
            totalItemsCount: 0,
            lowStockCount: 0,
            outOfStockCount: 0,
            totalInventoryValue: 0,
            totalPotentialRevenue: 0,
            totalEstimatedProfit: 0,
        }),
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

// State
const activeTab = ref(props.filters.tab || 'my_stock');
const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id || '');

// Modals state
const showCustomModal = ref(false);
const showGlobalModal = ref(false);
const showEditModal = ref(false);
const selectedGlobalProduct = ref(null);
const selectedEditProduct = ref(null);

// Forms
const globalForm = useForm({
    global_product_id: '',
    stock_level: 24,
    cost_price: 0,
    selling_price: 0,
});

const customForm = useForm({
    name: '',
    category_id: '',
    unit: 'bottle',
    cost_price: '',
    selling_price: '',
    stock_level: 12,
    is_chilled: true,
    description: '',
    image_url: '',
    image: null,
});

const editForm = useForm({
    _method: 'PUT',
    name: '',
    category_id: '',
    unit: 'bottle',
    cost_price: 0,
    selling_price: 0,
    stock_level: 0,
    is_chilled: true,
    description: '',
    image_url: '',
    image: null,
});

// Format currency in Naira
const formatNaira = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Search & Filter Handler
const handleFilterChange = () => {
    router.get(
        route('vendor.inventory.index'),
        {
            search: searchQuery.value,
            category_id: selectedCategory.value,
            tab: activeTab.value,
        },
        { preserveState: true, replace: true }
    );
};

// Stock Adjustment Handler
const updatingStockId = ref(null);
const updateStockLevel = (product, newLevel) => {
    if (newLevel < 0) return;
    updatingStockId.value = product.id;
    router.patch(
        route('vendor.inventory.update-stock', product.id),
        { stock_level: newLevel },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingStockId.value = null;
            },
        }
    );
};

const toggleChilledStatus = (product) => {
    router.patch(route('vendor.inventory.toggle-chilled', product.id), {}, {
        preserveScroll: true,
    });
};

// Open Global Stocking Modal
const openGlobalModal = (product) => {
    selectedGlobalProduct.value = product;
    globalForm.global_product_id = product.id;
    globalForm.cost_price = product.cost_price;
    globalForm.selling_price = product.selling_price;
    globalForm.stock_level = 24;
    showGlobalModal.value = true;
};

const submitGlobalStock = () => {
    globalForm.post(route('vendor.inventory.store-global'), {
        preserveScroll: true,
        onSuccess: () => {
            showGlobalModal.value = false;
            activeTab.value = 'my_stock';
        },
    });
};

// Submit Custom Item
const submitCustomProduct = () => {
    customForm.post(route('vendor.inventory.store-custom'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showCustomModal.value = false;
            customForm.reset();
        },
    });
};

// Open Edit Modal
const openEditModal = (product) => {
    selectedEditProduct.value = product;
    editForm.name = product.name;
    editForm.category_id = product.category_id;
    editForm.unit = product.unit;
    editForm.cost_price = product.cost_price;
    editForm.selling_price = product.selling_price;
    editForm.stock_level = product.stock_level;
    editForm.is_chilled = Boolean(product.is_chilled);
    editForm.description = product.description || '';
    editForm.image_url = product.image_url || '';
    editForm.image = null;
    showEditModal.value = true;
};

const submitEditProduct = () => {
    editForm.post(route('vendor.inventory.update', selectedEditProduct.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Delete Product
const deleteProduct = (product) => {
    if (confirm(`Are you sure you want to remove "${product.name}" from your inventory?`)) {
        router.delete(route('vendor.inventory.destroy', product.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Vendor Inventory Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white flex items-center gap-2">
                        <Store class="h-7 w-7 text-amber-500" />
                        {{ $page.props.auth.user.store_name || 'My Store Inventory' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Vendor Stock & Catalog Control Panel
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="showCustomModal = true"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500"
                    >
                        <Plus class="h-4 w-4" />
                        Add Custom Item
                    </button>
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

                <!-- Top Metrics Bar -->
                <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Items</span>
                            <Package class="h-5 w-5 text-indigo-500" />
                        </div>
                        <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ metrics.totalItemsCount }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Active stocked SKUs</span>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Low Stock Alert</span>
                            <AlertTriangle class="h-5 w-5 text-amber-500" />
                        </div>
                        <p class="mt-3 text-2xl font-bold text-amber-900 dark:text-amber-200">{{ metrics.lowStockCount }}</p>
                        <span class="text-xs text-amber-600 dark:text-amber-400">Items with &le; 10 units</span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Inventory Cost (COGS)</span>
                            <DollarSign class="h-5 w-5 text-emerald-500" />
                        </div>
                        <p class="mt-3 text-xl font-bold text-gray-900 dark:text-white">{{ formatNaira(metrics.totalInventoryValue) }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Capital tied in stock</span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Expected Profit</span>
                            <TrendingUp class="h-5 w-5 text-amber-500" />
                        </div>
                        <p class="mt-3 text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatNaira(metrics.totalEstimatedProfit) }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">If all current stock sells</span>
                    </div>
                </div>

                <!-- Tabs & Controls -->
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Tab Switcher -->
                    <div class="flex rounded-xl bg-gray-200/80 p-1 dark:bg-gray-800">
                        <button
                            @click="activeTab = 'my_stock'; handleFilterChange()"
                            :class="[
                                'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition',
                                activeTab === 'my_stock'
                                    ? 'bg-white text-gray-900 shadow dark:bg-gray-900 dark:text-white'
                                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            <Store class="h-4 w-4" />
                            My Store Inventory ({{ metrics.totalItemsCount }})
                        </button>
                        <button
                            @click="activeTab = 'global_catalog'; handleFilterChange()"
                            :class="[
                                'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition',
                                activeTab === 'global_catalog'
                                    ? 'bg-amber-600 text-white shadow'
                                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'
                            ]"
                        >
                            <Sparkles class="h-4 w-4" />
                            Global Catalog (&lt;15 min Stocking)
                        </button>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative min-w-[200px] flex-1 sm:flex-initial">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                @input="handleFilterChange"
                                type="text"
                                placeholder="Search beverage..."
                                class="w-full rounded-lg border border-gray-300 bg-white pl-9 pr-4 py-2 text-sm text-gray-900 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <select
                            v-model="selectedCategory"
                            @change="handleFilterChange"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- TAB 1: MY STORE INVENTORY -->
                <div v-if="activeTab === 'my_stock'">
                    <div v-if="myStock.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900">
                        <Package class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">No items in your store inventory yet</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Browse the pre-populated Global Catalog to add items in under 15 minutes, or create custom local inventory items.
                        </p>
                        <div class="mt-6 flex justify-center gap-4">
                            <button
                                @click="activeTab = 'global_catalog'"
                                class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-500"
                            >
                                <Sparkles class="h-4 w-4" />
                                Browse Global Catalog
                            </button>
                        </div>
                    </div>

                    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                                    <tr>
                                        <th class="px-6 py-4">Item</th>
                                        <th class="px-6 py-4">Category</th>
                                        <th class="px-6 py-4">Temp</th>
                                        <th class="px-6 py-4">Cost Price (COGS)</th>
                                        <th class="px-6 py-4">Selling Price</th>
                                        <th class="px-6 py-4">Margin / Unit</th>
                                        <th class="px-6 py-4">Stock Control</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                    <tr
                                        v-for="item in myStock"
                                        :key="item.id"
                                        class="transition hover:bg-gray-50/50 dark:hover:bg-gray-800/50"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img
                                                    :src="item.image_url"
                                                    :alt="item.name"
                                                    class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-gray-800 shrink-0"
                                                />
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white">{{ item.name }}</div>
                                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                        Per {{ item.unit }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                {{ item.category?.name || 'Beverage' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <button
                                                @click="toggleChilledStatus(item)"
                                                :title="item.is_chilled ? 'Click to switch to Room Temp' : 'Click to switch to Ice-Cold'"
                                                :class="[
                                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold transition border',
                                                    item.is_chilled
                                                        ? 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30 hover:bg-cyan-500/20'
                                                        : 'bg-gray-800 text-gray-400 border-gray-700 hover:bg-gray-700'
                                                ]"
                                            >
                                                <span>{{ item.is_chilled ? '❄️ Ice-Cold' : '🌡️ Room Temp' }}</span>
                                            </button>
                                        </td>

                                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                            {{ formatNaira(item.cost_price) }}
                                        </td>

                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                            {{ formatNaira(item.selling_price) }}
                                        </td>

                                        <td class="px-6 py-4 font-semibold text-emerald-600 dark:text-emerald-400">
                                            +{{ formatNaira(item.selling_price - item.cost_price) }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <!-- Interactive Stock Control -->
                                                <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-700">
                                                    <button
                                                        @click="updateStockLevel(item, item.stock_level - 1)"
                                                        :disabled="item.stock_level <= 0 || updatingStockId === item.id"
                                                        class="px-2.5 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-800"
                                                    >
                                                        <Minus class="h-3.5 w-3.5" />
                                                    </button>
                                                    <span class="px-3 text-sm font-bold text-gray-900 dark:text-white">
                                                        {{ item.stock_level }}
                                                    </span>
                                                    <button
                                                        @click="updateStockLevel(item, item.stock_level + 1)"
                                                        :disabled="updatingStockId === item.id"
                                                        class="px-2.5 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-800"
                                                    >
                                                        <Plus class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>

                                                <!-- Stock Badges -->
                                                <span
                                                    v-if="item.stock_level === 0"
                                                    class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-bold text-red-500"
                                                >
                                                    Out of Stock
                                                </span>
                                                <span
                                                    v-else-if="item.stock_level <= 10"
                                                    class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-bold text-amber-500"
                                                >
                                                    Low Stock ({{ item.stock_level }})
                                                </span>
                                                <span
                                                    v-else
                                                    class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-500"
                                                >
                                                    In Stock
                                                </span>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    @click="openEditModal(item)"
                                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                                                    title="Edit item"
                                                >
                                                    <Edit3 class="h-4 w-4" />
                                                </button>
                                                <button
                                                    @click="deleteProduct(item)"
                                                    class="rounded-lg p-2 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                                    title="Remove item"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: GLOBAL CATALOG BROWSER (<15 Min Stocking Target) -->
                <div v-if="activeTab === 'global_catalog'">
                    <div class="mb-4 rounded-xl bg-amber-950/40 border border-amber-500/30 p-4 text-amber-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <Sparkles class="h-5 w-5 text-amber-400 shrink-0" />
                            <p class="text-sm">
                                <strong>Rapid Stocking:</strong> Select pre-configured popular beverages from the catalog to stock your digital store in under 15 minutes.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="globalItem in globalCatalog"
                            :key="globalItem.id"
                            class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-amber-500 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800 mb-4">
                                <img
                                    :src="globalItem.image_url"
                                    :alt="globalItem.name"
                                    class="h-full w-full object-cover transition group-hover:scale-105"
                                />
                                <span class="absolute top-2 right-2 rounded-full bg-black/60 px-2.5 py-0.5 text-xs font-semibold text-white backdrop-blur">
                                    {{ globalItem.category?.name || 'Beverage' }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ globalItem.name }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ globalItem.description || 'Standard beverage item' }}
                            </p>

                            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                                <div>
                                    <span class="block text-xs text-gray-400">Rec. Retail Price</span>
                                    <span class="text-base font-bold text-amber-500">{{ formatNaira(globalItem.selling_price) }}</span>
                                </div>

                                <button
                                    @click="openGlobalModal(globalItem)"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-amber-500"
                                >
                                    <PlusCircle class="h-4 w-4" />
                                    Stock This Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: ONE-CLICK GLOBAL CATALOG STOCKING -->
        <div v-if="showGlobalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-amber-500" />
                        Stock {{ selectedGlobalProduct?.name }}
                    </h3>
                    <button @click="showGlobalModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitGlobalStock" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Initial Stock Count</label>
                        <input
                            v-model.number="globalForm.stock_level"
                            type="number"
                            min="0"
                            required
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Cost Price (COGS ₦)</label>
                            <input
                                v-model.number="globalForm.cost_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Selling Price (₦)</label>
                            <input
                                v-model.number="globalForm.selling_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="rounded-lg bg-amber-500/10 p-3 text-xs text-amber-600 dark:text-amber-400">
                        Profit Margin per unit: <strong>{{ formatNaira(globalForm.selling_price - globalForm.cost_price) }}</strong>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            @click="showGlobalModal = false"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="globalForm.processing"
                            class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-500 disabled:opacity-50"
                        >
                            Add to Store Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ADD CUSTOM ITEM -->
        <div v-if="showCustomModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Plus class="h-5 w-5 text-amber-500" />
                        Create Custom Local Product
                    </h3>
                    <button @click="showCustomModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCustomProduct" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Product Name</label>
                        <input
                            v-model="customForm.name"
                            type="text"
                            placeholder="e.g. Local Palm Wine 1L"
                            required
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Category</label>
                            <select
                                v-model="customForm.category_id"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">Select Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Unit Type</label>
                            <select
                                v-model="customForm.unit"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="bottle">Bottle</option>
                                <option value="can">Can</option>
                                <option value="crate">Crate</option>
                                <option value="pack">Pack</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Cost Price (COGS ₦)</label>
                            <input
                                v-model.number="customForm.cost_price"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Selling Price (₦)</label>
                            <input
                                v-model.number="customForm.selling_price"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Initial Stock</label>
                            <input
                                v-model.number="customForm.stock_level"
                                type="number"
                                min="0"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <DragDropImageUploader
                        v-model:modelValueFile="customForm.image"
                        v-model:modelValueUrl="customForm.image_url"
                        label="Product Image (Drag & Drop File or Paste URL)"
                    />

                    <div v-if="Object.keys(customForm.errors).length > 0" class="rounded-xl bg-red-950/80 border border-red-500/40 p-3 text-xs text-red-300 font-semibold space-y-1">
                        <p v-for="(err, key) in customForm.errors" :key="key">⚠️ {{ err }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            @click="showCustomModal = false"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="customForm.processing"
                            class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-500 disabled:opacity-50"
                        >
                            Create Item
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDIT PRODUCT -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Edit3 class="h-5 w-5 text-amber-500" />
                        Edit Product: {{ selectedEditProduct?.name }}
                    </h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEditProduct" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Product Name</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Category</label>
                            <select
                                v-model="editForm.category_id"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Unit</label>
                            <select
                                v-model="editForm.unit"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="bottle">Bottle</option>
                                <option value="can">Can</option>
                                <option value="crate">Crate</option>
                                <option value="pack">Pack</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Cost Price (COGS ₦)</label>
                            <input
                                v-model.number="editForm.cost_price"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Selling Price (₦)</label>
                            <input
                                v-model.number="editForm.selling_price"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Stock Level</label>
                            <input
                                v-model.number="editForm.stock_level"
                                type="number"
                                min="0"
                                required
                                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <DragDropImageUploader
                        v-model:modelValueFile="editForm.image"
                        v-model:modelValueUrl="editForm.image_url"
                        :current-image-url="selectedEditProduct?.image_url"
                        label="Product Image (Drag & Drop File or Paste URL)"
                    />

                    <div v-if="Object.keys(editForm.errors).length > 0" class="rounded-xl bg-red-950/80 border border-red-500/40 p-3 text-xs text-red-300 font-semibold space-y-1">
                        <p v-for="(err, key) in editForm.errors" :key="key">⚠️ {{ err }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-500 disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
