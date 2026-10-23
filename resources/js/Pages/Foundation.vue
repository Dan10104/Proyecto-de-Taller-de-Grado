<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    message: { type: String, required: true },
});
</script>

<template>
    <Head title="ESPACIO-U" />
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-12 text-slate-900">
        <section class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight">ESPACIO-U</h1>
            <p class="mt-3 text-base text-slate-600">{{ message }}</p>
        </section>
    </main>
</template>
