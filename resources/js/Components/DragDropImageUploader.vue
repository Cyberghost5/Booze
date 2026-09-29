<script setup>
import { ref, watch, computed } from 'vue';
import { UploadCloud, Image as ImageIcon, X, Link as LinkIcon, CheckCircle2 } from 'lucide-vue-next';

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
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleFileSelect = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        setFile(files[0]);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        const droppedFile = files[0];
        if (droppedFile.type.startsWith('image/')) {
            setFile(droppedFile);
            activeTab.value = 'file';
        }
    }
};

const setFile = (file) => {
    emit('update:modelValueFile', file);
};

const removeImage = () => {
    emit('update:modelValueFile', null);
    emit('update:modelValueUrl', '');
    filePreviewUrl.value = '';
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const updateUrlInput = (val) => {
    emit('update:modelValueUrl', val);
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-400">
                {{ label }}
            </label>
            <div class="flex rounded-lg bg-zinc-950 p-0.5 text-[11px] font-bold border border-zinc-800">
                <button
                    type="button"
                    @click="activeTab = 'file'"
                    :class="[
                        'px-2.5 py-1 rounded-md transition flex items-center gap-1',
                        activeTab === 'file' ? 'bg-amber-500 text-black shadow' : 'text-zinc-400 hover:text-white'
                    ]"
                >
                    <UploadCloud class="h-3.5 w-3.5" />
                    Drag & Drop File
                </button>
                <button
                    type="button"
                    @click="activeTab = 'url'"
                    :class="[
                        'px-2.5 py-1 rounded-md transition flex items-center gap-1',
                        activeTab === 'url' ? 'bg-amber-500 text-black shadow' : 'text-zinc-400 hover:text-white'
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
            accept="image/*"
            class="hidden"
            @change="handleFileSelect"
        />

        <!-- PREVIEW MODE IF IMAGE IS LOADED -->
        <div v-if="activePreview" class="relative group rounded-2xl border border-zinc-800 bg-zinc-950 p-3 flex items-center gap-4">
            <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900">
                <img :src="activePreview" alt="Preview" class="h-full w-full object-cover" />
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 text-xs font-bold text-amber-400">
                    <CheckCircle2 class="h-4 w-4 text-emerald-400" />
                    <span>{{ modelValueFile ? 'Uploaded File Selected' : 'Image URL Loaded' }}</span>
                </div>
                <p class="text-xs text-zinc-400 truncate mt-1">
                    {{ modelValueFile ? modelValueFile.name : activePreview }}
                </p>
                <span v-if="modelValueFile" class="text-[10px] text-zinc-500 block font-mono">
                    {{ (modelValueFile.size / 1024).toFixed(1) }} KB
                </span>
            </div>

            <button
                type="button"
                @click="removeImage"
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-2 text-zinc-400 hover:text-red-400 hover:border-red-500/50 transition"
                title="Remove Image"
            >
                <X class="h-4 w-4" />
            </button>
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
                    ? 'border-amber-500 bg-amber-500/10 scale-[1.01]'
                    : 'border-zinc-800 bg-zinc-950/80 hover:border-amber-500/50 hover:bg-zinc-900/80'
            ]"
        >
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 mb-3 border border-amber-500/20">
                <UploadCloud class="h-6 w-6" />
            </div>

            <p class="text-xs font-bold text-white">
                Drag & Drop product image here, or <span class="text-amber-500 underline">browse</span>
            </p>
            <p class="text-[10px] text-zinc-500 mt-1">
                Supports PNG, JPG, WEBP, or SVG (Max 2MB)
            </p>
        </div>

        <!-- TAB 2: IMAGE URL INPUT -->
        <div v-else class="space-y-2">
            <div class="relative">
                <LinkIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                <input
                    :value="modelValueUrl"
                    @input="updateUrlInput($event.target.value)"
                    type="url"
                    placeholder="https://images.unsplash.com/..."
                    class="w-full rounded-xl border border-zinc-800 bg-zinc-950 pl-9 pr-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:border-amber-500 focus:outline-none"
                />
            </div>
            <p class="text-[10px] text-zinc-500">
                Paste a direct image URL (e.g. Unsplash or web image)
            </p>
        </div>
    </div>
</template>
