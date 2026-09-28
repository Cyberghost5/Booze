<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-zinc-950 pt-8 sm:justify-center sm:pt-0 text-zinc-100"
    >
        <div class="mb-2">
            <Link href="/">
                <ApplicationLogo class="h-14 w-auto" />
            </Link>
        </div>

        <div
            class="mt-4 w-full overflow-hidden bg-zinc-900 border border-zinc-800 px-6 py-6 shadow-2xl sm:max-w-md sm:rounded-2xl"
        >
            <slot />
        </div>
    </div>
</template>
