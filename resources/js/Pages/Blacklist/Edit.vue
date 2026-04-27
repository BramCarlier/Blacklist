<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });
const props = defineProps({ entry: Object });
const form = useForm({
  ...props.entry,
  birth_date: props.entry.birth_date?.substring?.(0, 10) || props.entry.birth_date || '',
  incident_date: props.entry.incident_date?.substring?.(0, 10) || props.entry.incident_date || '',
  official_warning_given_at: props.entry.official_warning_given_at?.substring?.(0, 16) || props.entry.official_warning_given_at || '',
  record_type: props.entry.record_type || 'denied_access',
  official_warning_given: Boolean(props.entry.official_warning_given),
  gas_fine_recommended: Boolean(props.entry.gas_fine_recommended),
  gas_fine_status: props.entry.gas_fine_status || 'none',
});
const del = () => { if (confirm('Remove this record?')) router.delete(`/blacklist/${props.entry.id}`); };
</script>

<template>
  <section class="card max-w-2xl">
    <h1 class="mb-1 text-2xl font-bold">Edit access / enforcement record</h1>
    <p class="mb-4 text-sm text-slate-400">Update denied access, official warning or GAS fine follow-up details.</p>

    <form @submit.prevent="form.put(`/blacklist/${entry.id}`)" class="space-y-3">
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="label">First name</label><input v-model="form.first_name" class="input"></div>
        <div><label class="label">Last name</label><input v-model="form.last_name" class="input"></div>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div><label class="label">Birth date</label><input v-model="form.birth_date" class="input" type="date"></div>
        <div><label class="label">Nationality</label><input v-model="form.nationality" class="input"></div>
      </div>
      <div><label class="label">ID/document number</label><input v-model="form.id_document_number" class="input"></div>

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

      <div><label class="label">Incident / police / municipal reference</label><input v-model="form.incident_reference" class="input"></div>
      <div><label class="label">Location</label><input v-model="form.location" class="input"></div>
      <div><label class="label">Reason / offence</label><input v-model="form.reason" class="input"></div>

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
        <div class="sm:col-span-2"><label class="label">GAS fine reference</label><input v-model="form.gas_fine_reference" class="input"></div>
      </div>

      <div><label class="label">Overall status</label><select v-model="form.status" class="input"><option>active</option><option>expired</option><option>appealed</option></select></div>
      <div><label class="label">Notes</label><textarea v-model="form.notes" class="input min-h-24"></textarea></div>
      <div class="flex gap-2"><button class="btn btn-primary">Save</button><button type="button" @click="del" class="btn bg-red-500 text-white">Delete</button></div>
      <p v-for="(err,k) in form.errors" :key="k" class="text-sm text-red-300">{{err}}</p>
    </form>
  </section>
</template>
