<script setup>
import { useForm } from '@inertiajs/vue3';
const form = useForm({ email: 'admin@example.com', password: 'password', remember: true });
</script>
<template><div class="mx-auto max-w-md pt-10"><div class="card"><h1 class="mb-1 text-2xl font-bold">Sign in</h1><p class="mb-5 text-sm text-slate-400">Default local credentials come from .env.</p><form @submit.prevent="form.post('/login')" class="space-y-4"><div><label class="label">Email</label><input v-model="form.email" class="input" type="email"></div><div><label class="label">Password</label><input v-model="form.password" class="input" type="password"></div><label class="flex gap-2 text-sm"><input v-model="form.remember" type="checkbox"> Remember me</label><button class="btn btn-primary w-full" :disabled="form.processing">Login</button><p v-if="form.errors.email" class="text-sm text-red-300">{{ form.errors.email }}</p></form></div></div></template>
