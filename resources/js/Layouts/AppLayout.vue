<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
const page = usePage();
const logout = () => router.post('/logout');
</script>
<template>
  <div class="min-h-screen">
    <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/90 backdrop-blur">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <Link href="/" class="font-bold tracking-tight">Access Deny PWA</Link>
        <nav v-if="page.props.auth.user" class="flex items-center gap-2 text-sm">
          <Link href="/blacklist" class="rounded-lg px-3 py-2 hover:bg-slate-800">Blacklist</Link>
          <Link href="/blacklist/create" class="rounded-lg bg-emerald-500 px-3 py-2 font-semibold text-slate-950">Add</Link>
          <button @click="logout" class="rounded-lg px-3 py-2 hover:bg-slate-800">Logout</button>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6">
      <div v-if="page.props.flash.success" class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-3 text-emerald-200">{{ page.props.flash.success }}</div>
      <div v-if="page.props.flash.error" class="mb-4 rounded-xl border border-red-500/40 bg-red-500/10 p-3 text-red-200">{{ page.props.flash.error }}</div>
      <slot />
    </main>
  </div>
</template>
