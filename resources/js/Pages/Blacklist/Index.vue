<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });
const props = defineProps({ entries: Object, filters: Object });
let q = props.filters.q || '';
const search = () => router.get('/blacklist', { q }, { preserveState: true });

function label(type) {
  return {
    denied_access: 'Denied access',
    official_warning: 'Official warning',
    gas_fine_recommended: 'GAS recommended',
    gas_fine_issued: 'GAS issued',
  }[type || 'denied_access'] || type;
}
</script>

<template>
  <section class="space-y-4">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
      <div>
        <h1 class="text-3xl font-bold">Access & enforcement records</h1>
        <p class="text-sm text-slate-400">Denied access, official warnings and GAS fine follow-up.</p>
      </div>
      <Link href="/blacklist/create" class="btn btn-primary">Add record</Link>
    </div>

    <form class="flex gap-2" @submit.prevent="search">
      <input v-model="q" class="input" placeholder="Search name, ID number, location, incident ref or GAS ref">
      <button class="btn btn-muted">Search</button>
    </form>

    <div class="card overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="text-slate-400">
          <tr>
            <th class="p-2">Name</th>
            <th>ID number</th>
            <th>Type</th>
            <th>Location</th>
            <th>Warning</th>
            <th>GAS</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in entries.data" :key="e.id" class="border-t border-slate-800">
            <td class="p-2 font-medium">{{ e.first_name }} {{ e.last_name }}</td>
            <td>{{ e.id_document_number }}</td>
            <td>{{ label(e.record_type) }}</td>
            <td>{{ e.location }}</td>
            <td>{{ e.official_warning_given ? 'Yes' : 'No' }}</td>
            <td>{{ e.gas_fine_status && e.gas_fine_status !== 'none' ? e.gas_fine_status : 'No' }}</td>
            <td>{{ e.status }}</td>
            <td class="text-right"><Link :href="`/blacklist/${e.id}/edit`" class="text-emerald-300">Edit</Link></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
