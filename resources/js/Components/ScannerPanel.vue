<script setup>
import { ref } from 'vue';

const emit = defineEmits(['scanned']);

const message = ref('Tap NFC and hold a supported NFC/NDEF ID tag near the phone.');
const status = ref('idle');
const lastPayload = ref(null);

function decodeNdefTextRecord(record) {
  const bytes = new Uint8Array(record.data);

  if (record.recordType === 'text' && bytes.length > 0) {
    const languageCodeLength = bytes[0] & 0x3f;
    const encoding = (bytes[0] & 0x80) ? 'utf-16' : 'utf-8';
    return new TextDecoder(encoding).decode(bytes.slice(1 + languageCodeLength));
  }

  return new TextDecoder(record.encoding || 'utf-8').decode(record.data);
}

function normalizeDate(value) {
  if (!value) return '';
  const text = String(value).trim();

  let match = text.match(/^(\d{4})[-/.](\d{2})[-/.](\d{2})$/);
  if (match) return `${match[1]}-${match[2]}-${match[3]}`;

  match = text.match(/^(\d{2})[-/.](\d{2})[-/.](\d{4})$/);
  if (match) return `${match[3]}-${match[2]}-${match[1]}`;

  match = text.match(/^(\d{6})$/);
  if (match) {
    const yy = Number(text.slice(0, 2));
    const mm = text.slice(2, 4);
    const dd = text.slice(4, 6);
    const currentYY = Number(String(new Date().getFullYear()).slice(2));
    const century = yy > currentYY ? 1900 : 2000;
    return `${century + yy}-${mm}-${dd}`;
  }

  return '';
}

function cleanMrz(value) {
  return String(value || '').replace(/</g, ' ').replace(/\s+/g, ' ').trim();
}

function parseMrz(text) {
  const lines = text
    .split(/\r?\n/)
    .map(line => line.trim())
    .filter(Boolean);

  const payload = {};
  const mrzLines = lines.filter(line => /^[A-Z0-9<]{25,}$/.test(line));

  if (mrzLines.length >= 2) {
    const first = mrzLines[0];
    const second = mrzLines[1];
    const third = mrzLines[2] || '';

    if (first.startsWith('ID') || first.startsWith('I<') || first.startsWith('P<')) {
      payload.documentNumber = cleanMrz(second.slice(0, 9));
      payload.birthDate = normalizeDate(second.slice(13, 19));
      payload.nationality = cleanMrz(second.slice(10, 13)) || 'BEL';

      const nameLine = third || first.slice(5);
      if (nameLine.includes('<<')) {
        const [lastName, firstNames] = nameLine.split('<<');
        payload.lastName = cleanMrz(lastName);
        payload.firstName = cleanMrz(firstNames);
      }
    }
  }

  return payload;
}

function parseKeyValueText(text) {
  const payload = {};
  const map = {
    first_name: 'firstName', firstname: 'firstName', firstnames: 'firstName', voornaam: 'firstName', prenom: 'firstName', givenname: 'firstName', given_names: 'firstName',
    last_name: 'lastName', lastname: 'lastName', naam: 'lastName', nom: 'lastName', surname: 'lastName', familyname: 'lastName',
    birth_date: 'birthDate', birthdate: 'birthDate', geboortedatum: 'birthDate', dateofbirth: 'birthDate', dob: 'birthDate',
    documentnumber: 'documentNumber', document_number: 'documentNumber', cardnumber: 'documentNumber', card_number: 'documentNumber', rijksregisternummer: 'documentNumber', nationalnumber: 'documentNumber', national_number: 'documentNumber', id: 'documentNumber',
    nationality: 'nationality', nationaliteit: 'nationality', nationalite: 'nationality',
  };

  for (const line of text.split(/\r?\n|;/)) {
    const match = line.match(/^\s*([^:=]+)\s*[:=]\s*(.+?)\s*$/);
    if (!match) continue;
    const key = match[1].toLowerCase().replace(/[^a-z0-9_]/g, '');
    const target = map[key];
    if (!target) continue;
    payload[target] = target === 'birthDate' ? normalizeDate(match[2]) : match[2].trim();
  }

  return payload;
}

function parseJsonPayload(text) {
  try {
    const json = JSON.parse(text);
    return {
      documentNumber: json.id_document_number || json.documentNumber || json.document_number || json.cardNumber || json.nationalNumber || json.id || '',
      firstName: json.first_name || json.firstName || json.givenName || json.given_names || '',
      lastName: json.last_name || json.lastName || json.surname || json.familyName || '',
      birthDate: normalizeDate(json.birth_date || json.birthDate || json.dateOfBirth || json.dob || ''),
      nationality: json.nationality || '',
    };
  } catch {
    return {};
  }
}

function parsePayload(text, serialNumber = '') {
  const raw = String(text || '').trim();
  const json = parseJsonPayload(raw);
  const keyValue = parseKeyValueText(raw);
  const mrz = parseMrz(raw);

  const payload = {
    text: raw || serialNumber,
    raw: raw || serialNumber,
    serialNumber,
    ...json,
    ...keyValue,
    ...mrz,
  };

  payload.birthDate = normalizeDate(payload.birthDate);
  payload.sourceType = 'nfc';

  return payload;
}

async function scanNfc() {
  status.value = 'scanning';
  lastPayload.value = null;

  if (!('NDEFReader' in window)) {
    status.value = 'unsupported';
    message.value = 'Web NFC is not available in this browser. Use Android Chrome over HTTPS with an NDEF-compatible tag.';
    return;
  }

  try {
    const reader = new NDEFReader();
    await reader.scan();
    message.value = 'Ready. Hold the NFC tag/card near the phone.';

    reader.onreadingerror = () => {
      status.value = 'error';
      message.value = 'The NFC tag was detected, but the browser could not read an NDEF payload from it.';
    };

    reader.onreading = event => {
      const chunks = [];

      for (const record of event.message.records) {
        try {
          if (record.recordType === 'text' || record.recordType === 'mime' || record.recordType === 'unknown') {
            chunks.push(decodeNdefTextRecord(record));
          } else if (record.recordType === 'url') {
            chunks.push(decodeNdefTextRecord(record));
          }
        } catch (error) {
          chunks.push('');
        }
      }

      const text = chunks.filter(Boolean).join('\n').trim();
      const payload = parsePayload(text, event.serialNumber || '');
      lastPayload.value = payload;
      emit('scanned', payload, 'nfc');
      status.value = 'captured';

      if (payload.documentNumber || payload.firstName || payload.lastName) {
        message.value = 'NFC data captured and form filled. Checking blacklist…';
      } else if (payload.serialNumber) {
        message.value = 'Only the NFC serial number was readable. Belgian eID personal data is not exposed as Web NFC NDEF data.';
      } else {
        message.value = 'NFC tag read, but no supported ID fields were found.';
      }
    };
  } catch (error) {
    status.value = 'error';
    message.value = error?.message || 'Could not start NFC scan.';
  }
}
</script>

<template>
  <div class="card">
    <div class="mb-4 flex items-start justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">NFC ID input</h2>
        <p class="mt-1 text-sm text-slate-400">{{ message }}</p>
      </div>
      <span class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-wide text-slate-300">{{ status }}</span>
    </div>

    <button class="btn btn-primary w-full" type="button" @click="scanNfc">Read NFC</button>

    <div class="mt-4 rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs leading-5 text-slate-400">
      <p class="font-semibold text-slate-200">Supported in this PWA</p>
      <p>NDEF text/JSON/MRZ-style payloads containing fields like firstName, lastName, birthDate, documentNumber and nationality.</p>
      <p class="mt-2 font-semibold text-amber-200">Belgian eID limitation</p>
      <p>Belgian eID chip data is protected smart-card data and is not exposed through browser Web NFC. A native Android app or external eID/PCSC reader is needed for true Belgian eID chip reading.</p>
    </div>

    <pre v-if="lastPayload" class="mt-4 max-h-48 overflow-auto rounded-xl bg-black/40 p-3 text-xs text-slate-300">{{ lastPayload }}</pre>
  </div>
</template>
