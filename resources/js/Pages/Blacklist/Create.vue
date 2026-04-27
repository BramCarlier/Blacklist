<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import ScannerPanel from '@/Components/ScannerPanel.vue';
import axios from 'axios';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const form = useForm({
  first_name: '',
  last_name: '',
  birth_date: '',
  id_document_number: '',
  nationality: '',
  reason: '',
  location: '',
  source: 'manual',
  status: 'active',
  notes: '',
  raw_payload: '',
});

const lookup = ref({ loading: false, checked: false, found: false, active_found: false, matches: [] });
const lookupError = ref('');

async function lookupPerson() {
  lookupError.value = '';
  lookup.value = { loading: true, checked: false, found: false, active_found: false, matches: [] };

  try {
    const response = await axios.get('/blacklist/lookup', {
      params: {
        id_document_number: form.id_document_number,
        raw_payload: form.raw_payload,
        first_name: form.first_name,
        last_name: form.last_name,
        birth_date: form.birth_date,
      },
    });

    lookup.value = { loading: false, checked: true, ...response.data };
  } catch (error) {
    lookup.value.loading = false;
    lookupError.value = error?.response?.data?.message || error?.message || 'Lookup failed.';
  }
}

function applyScan(payload, source) {
  form.raw_payload = payload.raw || payload.text || '';
  form.source = source;
  form.id_document_number = payload.documentNumber || payload.text || form.id_document_number;
  form.first_name = payload.firstName || form.first_name;
  form.last_name = payload.lastName || form.last_name;
  form.birth_date = payload.birthDate || form.birth_date;
  form.nationality = payload.nationality || form.nationality;

  if (form.id_document_number || (form.first_name && form.last_name)) {
    lookupPerson();
  }
}
</script>

<template>
  <section class="grid gap-5 lg:grid-cols-[1fr,1fr]">
    <div class="card">
      <h1 class="mb-4 text-2xl font-bold">Register denied access</h1>

      <div v-if="lookup.loading" class="mb-4 rounded-xl border border-sky-500/40 bg-sky-500/10 p-3 text-sm text-sky-100">
        Checking blacklist…
      </div>
      <div v-else-if="lookup.checked && lookup.active_found" class="mb-4 rounded-xl border border-red-500/50 bg-red-500/10 p-3 text-sm text-red-100">
        <strong>Active blacklist match found.</strong> Deny access and verify the match below before saving a duplicate.
      </div>
      <div v-else-if="lookup.checked && lookup.found" class="mb-4 rounded-xl border border-amber-500/50 bg-amber-500/10 p-3 text-sm text-amber-100">
        Existing inactive/expired match found. Verify before creating a new entry.
      </div>
      <div v-else-if="lookup.checked" class="mb-4 rounded-xl border border-emerald-500/50 bg-emerald-500/10 p-3 text-sm text-emerald-100">
        No existing blacklist match found for this ID/person.
      </div>
      <div v-if="lookupError" class="mb-4 rounded-xl border border-red-500/50 bg-red-500/10 p-3 text-sm text-red-100">
        {{ lookupError }}
      </div>

      <div v-if="lookup.matches?.length" class="mb-4 space-y-2">
        <div v-for="match in lookup.matches" :key="match.id" class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-sm">
          <div class="font-semibold text-slate-100">{{ match.first_name }} {{ match.last_name }}</div>
          <div class="text-slate-400">{{ match.id_document_number }} · {{ match.status }} · {{ match.location }}</div>
          <div class="text-slate-500">{{ match.reason }}</div>
        </div>
      </div>

      <form @submit.prevent="form.post('/blacklist')" class="space-y-3">
        <div class="grid gap-3 sm:grid-cols-2">
          <div><label class="label">First name</label><input v-model="form.first_name" class="input"></div>
          <div><label class="label">Last name</label><input v-model="form.last_name" class="input"></div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div><label class="label">Birth date</label><input v-model="form.birth_date" class="input" type="date"></div>
          <div><label class="label">Nationality</label><input v-model="form.nationality" class="input"></div>
        </div>
        <div><label class="label">ID / document number</label><input v-model="form.id_document_number" class="input" required></div>
        <div><label class="label">Denied at location</label><input v-model="form.location" class="input" placeholder="Door / venue / event" required></div>
        <div><label class="label">Reason</label><input v-model="form.reason" class="input" placeholder="Policy breach / ban / security decision" required></div>
        <div><label class="label">Notes</label><textarea v-model="form.notes" class="input min-h-24"></textarea></div>
        <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-100">Do not store more data than needed. Verify local privacy, GDPR, ID scanning and retention rules before production use.</div>
        <button class="btn btn-muted w-full" type="button" @click="lookupPerson" :disabled="lookup.loading">Check existing blacklist entry</button>
        <button class="btn btn-primary w-full" :disabled="form.processing">Save blacklist entry</button>
        <p v-for="(err,k) in form.errors" :key="k" class="text-sm text-red-300">{{err}}</p>
      </form>
    </div>

    <ScannerPanel @scanned="applyScan" />
  </section>
</template>
