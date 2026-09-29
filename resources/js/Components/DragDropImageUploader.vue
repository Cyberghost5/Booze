<script setup>
import { ref, watch, computed } from 'vue';
import { UploadCloud, Image as ImageIcon, X, Link as LinkIcon, CheckCircle2, RefreshCw, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    modelValueFile: {
        type: [Object, File, null],
        default: null,
    },
    modelValueUrl: {
        type: String,
        default: '',
    },
    currentImageUrl: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: 'Product Image',
    },
});

const emit = defineEmits(['update:modelValueFile', 'update:modelValueUrl']);

const isDragging = ref(false);
const fileInputRef = ref(null);
const activeTab = ref('file'); // 'file' or 'url'
const uploadError = ref('');

// Preview computation
const filePreviewUrl = ref('');

watch(() => props.modelValueFile, (newFile) => {
    if (newFile instanceof File) {
        filePreviewUrl.value = URL.createObjectURL(newFile);
    } else {
        filePreviewUrl.value = '';
    }
}, { immediate: true });

const activePreview = computed(() => {
    if (filePreviewUrl.value) return filePreviewUrl.value;
    if (props.modelValueUrl) return props.modelValueUrl;
    if (props.currentImageUrl) return props.currentImageUrl;
    return null;
});

const triggerBrowse = () => {
    uploadError.value = '';
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleFileSelect = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        validateAndSetFile(files[0]);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    uploadError.value = '';
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        validateAndSetFile(files[0]);
        activeTab.value = 'file';
    }
};

const validateAndSetFile = (file) => {
    uploadError.value = '';
    if (!file.type.startsWith('image/')) {
        uploadError.value = 'Selected file is not an image. Please upload PNG, JPG, WEBP, or SVG.';
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        uploadError.value = 'File size exceeds 10MB limit. Please upload a smaller image.';
        return;
    }
    emit('update:modelValueFile', file);
    // Clear URL value if new file is selected
    emit('update:modelValueUrl', '');
};

const removeImage = () => {
    uploadError.value = '';
    emit('update:modelValueFile', null);
    emit('update:modelValueUrl', '');
    filePreviewUrl.value = '';
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const updateUrlInput = (val) => {
    uploadError.value = '';
    emit('update:modelValueUrl', val);
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(1) + ' KB';
    }
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};
</script>

<template>
    <div class="space-y-2.5">
        <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-300 dark:text-zinc-300">
                {{ label }}
            </label>
            <div class="flex rounded-xl bg-zinc-950 p-1 text-[11px] font-bold border border-zinc-800 shadow-inner">
                <button
                    type="button"
                    @click="activeTab = 'file'; uploadError = '';"
                    :class="[
                        'px-3 py-1 rounded-lg transition-all flex items-center gap-1.5',
                        activeTab === 'file' ? 'bg-amber-500 text-zinc-950 font-extrabold shadow-md' : 'text-zinc-400 hover:text-white'
                    ]"
                >
                    <UploadCloud class="h-3.5 w-3.5" />
                    Drag & Drop File
                </button>
                <button
                    type="button"
                    @click="activeTab = 'url'; uploadError = '';"
                    :class="[
                        'px-3 py-1 rounded-lg transition-all flex items-center gap-1.5',
                        activeTab === 'url' ? 'bg-amber-500 text-zinc-950 font-extrabold shadow-md' : 'text-zinc-400 hover:text-white'
                    ]"
                >
                    <LinkIcon class="h-3.5 w-3.5" />
                    Image URL
                </button>
            </div>
        </div>

        <!-- Hidden File Input -->
        <input
            ref="fileInputRef"
            type="file"
            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,image/gif"
            class="hidden"
            @change="handleFileSelect"
        />

        <!-- PREVIEW MODE IF IMAGE IS LOADED -->
        <div v-if="activePreview" class="relative group rounded-2xl border border-amber-500/30 bg-zinc-950/90 p-3.5 flex items-center justify-between gap-4 shadow-xl backdrop-blur-md">
            <div class="flex items-center gap-4 min-w-0">
                <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-amber-500/40 bg-zinc-900 shadow-md">
                    <img :src="activePreview" alt="Product Image Preview" class="h-full w-full object-cover transition-transform group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-1">
                        <span class="text-[9px] font-bold text-amber-400 uppercase">Preview</span>
                    </div>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-400 mb-0.5">
                        <CheckCircle2 class="h-4 w-4 shrink-0" />
                        <span>{{ modelValueFile ? 'New Image Selected' : 'Product Image Ready' }}</span>
                    </div>
                    <p class="text-xs font-semibold text-zinc-200 truncate max-w-xs">
                        {{ modelValueFile ? modelValueFile.name : activePreview }}
                    </p>
                    <span v-if="modelValueFile" class="text-[11px] text-amber-400/90 font-mono block mt-0.5 font-bold">
                        ⚡ {{ formatFileSize(modelValueFile.size) }}
                    </span>
                    <span v-else-if="currentImageUrl && !modelValueUrl" class="text-[10px] text-zinc-500 block mt-0.5">
                        Current catalog image active
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="triggerBrowse"
                    class="rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-xs font-bold text-amber-400 hover:border-amber-500/50 hover:bg-zinc-800 transition-all flex items-center gap-1.5 shadow"
                    title="Choose Different Image"
                >
                    <RefreshCw class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">Replace</span>
                </button>

                <button
                    type="button"
                    @click="removeImage"
                    class="rounded-xl border border-zinc-800 bg-zinc-900 p-2 text-zinc-400 hover:text-red-400 hover:border-red-500/50 hover:bg-red-950/40 transition-all shadow"
                    title="Remove Image"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- TAB 1: DRAG AND DROP ZONE -->
        <div
            v-else-if="activeTab === 'file'"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="triggerBrowse"
            :class="[
                'relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-6 text-center cursor-pointer transition-all duration-300',
                isDragging
                    ? 'border-amber-400 bg-amber-500/15 scale-[1.01] shadow-xl shadow-amber-500/10'
                    : 'border-zinc-800 bg-zinc-950/80 hover:border-amber-500/50 hover:bg-zinc-900/90 shadow-md'
            ]"
        >
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 mb-3 border border-amber-500/20 shadow-inner group-hover:scale-110 transition-transform">
                <UploadCloud class="h-7 w-7" />
            </div>

            <p class="text-xs font-bold text-white">
                Drag & Drop product image here, or <span class="text-amber-400 underline decoration-amber-400/50 font-extrabold">browse file</span>
            </p>
            <p class="text-[11px] text-zinc-400 mt-1">
                Supports PNG, JPG, WEBP, or SVG (Up to 10MB)
            </p>
        </div>

        <!-- TAB 2: IMAGE URL INPUT -->
        <div v-else class="space-y-2">
            <div class="relative">
                <LinkIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <input
                    :value="modelValueUrl"
                    @input="updateUrlInput($event.target.value)"
                    type="text"
                    placeholder="https://images.unsplash.com/... or image link"
                    class="w-full rounded-xl border border-zinc-800 bg-zinc-950 pl-10 pr-4 py-2.5 text-xs font-medium text-white placeholder-zinc-500 focus:border-amber-500 focus:outline-none shadow-md"
                />
            </div>
            <p class="text-[11px] text-zinc-400">
                Paste a direct link to an online beverage image (e.g. Unsplash or supplier CDN)
            </p>
        </div>

        <!-- ERROR MESSAGE BANNER -->
        <div v-if="uploadError" class="rounded-xl bg-red-950/80 border border-red-500/40 p-2.5 text-xs text-red-300 font-semibold flex items-center gap-2">
            <AlertCircle class="h-4 w-4 shrink-0 text-red-400" />
            <span>{{ uploadError }}</span>
        </div>
    </div>
</template>
