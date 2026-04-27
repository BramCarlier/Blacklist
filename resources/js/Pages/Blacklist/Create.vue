<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import ScannerPanel from '@/Components/ScannerPanel.vue';
import axios from 'axios';
import { ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const form = useForm({
  first_name: '',
  last_name: '',
  birth_date: '',
  id_document_number: '',
  nationality: '',
  record_type: 'denied_access',
  incident_date: new Date().toISOString().slice(0, 10),
  incident_reference: '',
  reason: '',
  official_warning_given: false,
  official_warning_given_at: '',
  gas_fine_recommended: false,
  gas_fine_status: 'none',
  gas_fine_reference: '',
  location: '',
  source: 'manual',
  status: 'active',
  notes: '',
  raw_payload: '',
});

const lookup = ref({ loading: false, checked: false, found: false, active_found: false, warning_found: false, gas_fine_found: false, matches: [] });
const lookupError = ref('');

watch(() => form.record_type, value => {
  if (value === 'official_warning') {
    form.official_warning_given = true;
    if (!form.official_warning_given_at) form.official_warning_given_at = new Date().toISOString().slice(0, 16);
    if (form.gas_fine_status === 'recommended' && !form.gas_fine_recommended) form.gas_fine_status = 'none';
  }
  if (value === 'gas_fine_recommended') {
    form.gas_fine_recommended = true;
    form.gas_fine_status = 'recommended';
  }
  if (value === 'gas_fine_issued') {
    form.gas_fine_recommended = true;
    form.gas_fine_status = 'issued';
  }
});

async function lookupPerson() {
  lookupError.value = '';
  lookup.value = { loading: true, checked: false, found: false, active_found: false, warning_found: false, gas_fine_found: false, matches: [] };

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
      <h1 class="mb-1 text-2xl font-bold">Register access / warning / GAS record</h1>
      <p class="mb-4 text-sm text-slate-400">Use this to check denied access, official warnings and GAS fine follow-up for a person.</p>

      <div v-if="lookup.loading" class="mb-4 rounded-xl border border-sky-500/40 bg-sky-500/10 p-3 text-sm text-sky-100">
        Checking existing records…
      </div>
      <div v-else-if="lookup.checked && lookup.gas_fine_found" class="mb-4 rounded-xl border border-red-500/50 bg-red-500/10 p-3 text-sm text-red-100">
        <strong>GAS fine record found.</strong> Verify whether a GAS fine was already recommended, sent, issued, paid or appealed.
      </div>
      <div v-else-if="lookup.checked && lookup.warning_found" class="mb-4 rounded-xl border border-amber-500/50 bg-amber-500/10 p-3 text-sm text-amber-100">
        <strong>Official warning found.</strong> Review the previous warning before deciding whether to recommend a GAS fine.
      </div>
      <div v-else-if="lookup.checked && lookup.active_found" class="mb-4 rounded-xl border border-red-500/50 bg-red-500/10 p-3 text-sm text-red-100">
        <strong>Active denied-access record found.</strong> Verify the record below before saving a duplicate.
      </div>
      <div v-else-if="lookup.checked && lookup.found" class="mb-4 rounded-xl border border-amber-500/50 bg-amber-500/10 p-3 text-sm text-amber-100">
        Existing inactive/expired record found. Verify before creating a new entry.
      </div>
      <div v-else-if="lookup.checked" class="mb-4 rounded-xl border border-emerald-500/50 bg-emerald-500/10 p-3 text-sm text-emerald-100">
        No existing record found for this ID/person.
      </div>
      <div v-if="lookupError" class="mb-4 rounded-xl border border-red-500/50 bg-red-500/10 p-3 text-sm text-red-100">
        {{ lookupError }}
      </div>

      <div v-if="lookup.matches?.length" class="mb-4 space-y-2">
        <div v-for="match in lookup.matches" :key="match.id" class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-sm">
          <div class="font-semibold text-slate-100">{{ match.first_name }} {{ match.last_name }}</div>
          <div class="text-slate-400">{{ match.id_document_number }} · {{ match.record_type || 'denied_access' }} · {{ match.status }} · {{ match.location }}</div>
          <div class="text-slate-400" v-if="match.official_warning_given">Official warning given</div>
          <div class="text-slate-400" v-if="match.gas_fine_status && match.gas_fine_status !== 'none'">GAS: {{ match.gas_fine_status }}</div>
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

        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="label">Record type</label>
            <select v-model="form.record_type" class="input">
              <option value="denied_access">Denied access</option>
              <option value="official_warning">Official warning</option>
              <option value="gas_fine_recommended">GAS fine recommended</option>
              <option value="gas_fine_issued">GAS fine issued</option>
            </select>
          </div>
          <div><label class="label">Incident date</label><input v-model="form.incident_date" class="input" type="date"></div>
        </div>

        <div><label class="label">Incident / police / municipal reference</label><input v-model="form.incident_reference" class="input" placeholder="Optional reference number"></div>
        <div><label class="label">Location</label><input v-model="form.location" class="input" placeholder="Door / venue / event / municipality" required></div>
        <div><label class="label">Reason / offence</label><input v-model="form.reason" class="input" placeholder="Nuisance / policy breach / aggression / public order / ..." required></div>

        <div class="grid gap-3 rounded-xl border border-slate-700 bg-slate-950 p-3 sm:grid-cols-2">
          <label class="flex items-center gap-2 text-sm"><input v-model="form.official_warning_given" type="checkbox"> Official warning given</label>
          <div><label class="label">Warning given at</label><input v-model="form.official_warning_given_at" class="input" type="datetime-local"></div>
          <label class="flex items-center gap-2 text-sm"><input v-model="form.gas_fine_recommended" type="checkbox"> GAS fine should be recommended</label>
          <div>
            <label class="label">GAS fine status</label>
            <select v-model="form.gas_fine_status" class="input">
              <option value="none">None</option>
              <option value="recommended">Recommended</option>
              <option value="sent_to_authority">Sent to authority</option>
              <option value="issued">Issued</option>
              <option value="paid">Paid</option>
              <option value="cancelled">Cancelled</option>
              <option value="appealed">Appealed</option>
            </select>
          </div>
          <div class="sm:col-span-2"><label class="label">GAS fine reference</label><input v-model="form.gas_fine_reference" class="input" placeholder="Optional GAS file/reference number"></div>
        </div>

        <div><label class="label">Overall status</label><select v-model="form.status" class="input"><option>active</option><option>expired</option><option>appealed</option></select></div>
        <div><label class="label">Notes</label><textarea v-model="form.notes" class="input min-h-24"></textarea></div>
        <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-100">Only authorized staff should use this. Verify GDPR, municipal GAS procedure, retention rules and legal basis before production use.</div>
        <button class="btn btn-muted w-full" type="button" @click="lookupPerson" :disabled="lookup.loading">Check existing records</button>
        <button class="btn btn-primary w-full" :disabled="form.processing">Save record</button>
        <p v-for="(err,k) in form.errors" :key="k" class="text-sm text-red-300">{{err}}</p>
      </form>
    </div>

    <ScannerPanel @scanned="applyScan" />
  </section>
</template>
