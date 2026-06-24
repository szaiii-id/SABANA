<template>
  <div class="min-h-screen bg-[#FDFBF9] font-sans text-[#4A3728]">
    
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="text-center">
        <svg class="animate-spin h-8 w-8 text-[#1B4332] mx-auto mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        <p class="text-[#6B705C] font-bold text-sm">Memuat detail pengajuan...</p>
      </div>
    </div>

    <div v-else-if="!detail" class="flex items-center justify-center h-64">
      <div class="text-center">
        <p class="text-[#6B705C] font-bold">Data tidak ditemukan.</p>
        <button @click="$router.push({ name: 'admin.verifications' })" class="mt-2 text-sm font-bold text-[#1B4332] hover:underline">Kembali ke daftar</button>
      </div>
    </div>

    <div v-else class="max-w-5xl mx-auto space-y-4 animate-fade-in">
      
      <div class="flex items-center justify-between">
        <button @click="$router.push({ name: 'admin.verifications' })" class="flex items-center gap-1.5 text-[#8B5E3C] hover:text-[#1B4332] font-black text-[10px] uppercase tracking-widest">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Antrean Verifikasi
        </button>
        <span class="text-[10px] font-black text-[#8B5E3C]/40 uppercase tracking-[0.3em]">Detail</span>
      </div>
      
      <!-- CARD IDENTITAS -->
      <div class="bg-white rounded-2xl p-4 border border-[#E8D5C4] shadow-sm">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-xl font-black text-[#1B4332] leading-tight">{{ detail.citizen?.full_name }}</h1>
            <div class="flex items-center gap-2 mt-1 text-xs text-[#6B705C] font-mono">
              <span>{{ detail.citizen?.nik }}</span>
              <span class="w-1 h-1 rounded-full bg-[#D4A373]"></span>
              <span>{{ detail.registration_number }}</span>
            </div>
          </div>
          <span :class="statusBadge(detail.status)" class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase shadow-sm flex-shrink-0">{{ formatStatus(detail.status) }}</span>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-[#E8D5C4]">
          <div class="flex items-center gap-2 p-2.5 bg-[#FAF6F0] rounded-xl">
            <div class="w-8 h-8 rounded-lg bg-[#1B4332]/10 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <div><p class="text-[8px] font-black text-[#8B5E3C] uppercase">Program</p><p class="text-xs font-bold text-[#1B4332]">{{ detail.program?.name }}</p></div>
          </div>
          <div class="flex items-center gap-2 p-2.5 bg-[#FAF6F0] rounded-xl">
            <div class="w-8 h-8 rounded-lg bg-[#1B4332]/10 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
            <div class="flex-1"><p class="text-[8px] font-black text-[#8B5E3C] uppercase">Skor SMART</p><div class="flex items-center gap-1.5"><span class="text-xs font-black" :class="smartScoreClass(detail.smart_score)">{{ detail.smart_score ? (detail.smart_score * 100).toFixed(1) : '-' }}</span><span :class="recommendationBadge(detail.recommendation?.color)" class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase">{{ detail.recommendation?.label || '-' }}</span></div></div>
            <button @click="showSmartModal = true" class="px-2 py-1 rounded-lg text-[8px] font-bold text-[#1B4332] bg-white border border-[#E8D5C4] hover:bg-[#1B4332] hover:text-white">Rincian</button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-[#E8D5C4]">
          <div class="flex items-center gap-2 p-2.5 bg-[#FAF6F0] rounded-xl">
            <div class="w-8 h-8 rounded-lg bg-[#1B4332]/10 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg></div>
            <div><p class="text-[8px] font-black text-[#8B5E3C] uppercase">Metode</p><p class="text-xs font-bold text-[#1B4332]">{{ formatDisbursement(detail.disbursement_method) }}</p></div>
          </div>
          <div class="flex items-center gap-2 p-2.5 bg-[#FAF6F0] rounded-xl">
            <div class="w-8 h-8 rounded-lg bg-[#1B4332]/10 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
            <div><p class="text-[8px] font-black text-[#8B5E3C] uppercase">Lokasi</p><p class="text-xs font-bold text-[#1B4332]">{{ detail.wilayah?.village || '-' }}, {{ detail.wilayah?.district || '-' }}</p></div>
          </div>
        </div>
      </div>

      <SuspiciousActivity :anomalies="(detail.submission_data?.anomalies as any[])" />

      <!-- DATA PENGAJUAN + AKSI SEJAJAR -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        
        <div v-if="displayableData && Object.keys(displayableData).length" class="md:col-span-2 bg-white rounded-2xl p-4 border border-[#E8D5C4] shadow-sm">
          <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.15em] mb-3 flex items-center gap-2">
            <div class="w-7 h-7 bg-[#FAF6F0] rounded-lg flex items-center justify-center"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
            Data Pengajuan
          </h3>
          <div class="grid grid-cols-2 gap-1.5">
            <div v-for="(value, key) in displayableData" :key="key" class="flex justify-between items-center py-2 px-3 bg-[#FAF6F0] rounded-xl">
              <span class="text-[11px] font-bold text-[#6B705C]">{{ formatKey(key) }}</span>
              <span class="text-[11px] font-bold text-[#1B4332]">{{ formatValue(key, value) }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-[#E8D5C4] shadow-sm">
          <h3 class="text-[#8B5E3C] font-black uppercase text-[9px] tracking-[0.15em] mb-3">Aksi</h3>
          <VerificationActions v-if="detail.status === 'pending'" :disabled="!allRequiredDocumentsZoomed" :submitting="submitting" :documents="requiredDocuments.map(d => ({ key: getDocKey(d), label: formatKey(getDocKey(d)) }))" :inputs="programInputs" @approve="handleApprove" @reject="(notes: string) => handleReject(notes)" @revision="(notes: string, items: string[]) => handleRevision(notes, items)"/>
          <div v-else-if="detail.status === 'validated'" class="space-y-2">
            <div class="flex items-center gap-2 p-2 bg-green-50 rounded-xl border border-green-200"><svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><p class="text-[10px] font-bold text-green-700">Disetujui</p></div>
            <button v-if="!detail.disbursement" @click="openDisburseModal" :disabled="submitting" class="w-full px-3 py-2 rounded-xl text-xs font-bold text-white bg-[#1B4332] hover:bg-[#2D6A4F] disabled:opacity-50">Salurkan Bantuan</button>
            <div v-else class="flex items-center gap-2 p-2 bg-blue-50 rounded-xl border border-blue-200"><p class="text-[10px] font-bold text-blue-700">Sudah disalurkan</p></div>
            <button @click="handleUnvalidate" :disabled="submitting" class="w-full px-3 py-2 rounded-xl text-xs font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 border border-amber-200 disabled:opacity-50">Batalkan Persetujuan</button>
          </div>
          <div v-else class="text-center py-3"><p class="text-sm font-bold text-[#1B4332]">{{ formatStatus(detail.status) }}</p><p class="text-[10px] text-[#6B705C] mt-1">{{ statusDescription(detail.status) }}</p></div>
        </div>
      </div>

      <!-- DOKUMEN BUKTI — FULL WIDTH -->
      <EvidenceGallery :evidences="detail.evidences" :zoomedDocuments="zoomedDocuments" @zoom="handleImageClick"/>
    </div>

    <ConfirmModal :open="showCompleteConfirm" title="Tandai Selesai?" message="Bantuan akan ditandai sudah disalurkan." variant="warning" confirm-text="Ya" cancel-text="Batal" @close="showCompleteConfirm = false" @confirm="executeComplete"/>
    <Transition name="fade"><div v-if="showUnvalidateConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4"><div class="absolute inset-0 bg-black/40" @click="showUnvalidateConfirm = false"></div><div class="bg-white rounded-2xl p-5 max-w-sm w-full shadow-xl relative z-10 border border-[#E8D5C4]"><h3 class="text-base font-black mb-2">Batalkan Persetujuan?</h3><p class="text-[#8B5E3C] text-[10px] mb-3">Tulis alasan pembatalan (min. 10 karakter).</p><textarea v-model="unvalidateNotes" rows="3" class="w-full px-3 py-2 rounded-xl border border-[#E8D5C4] text-xs focus:border-amber-500 outline-none resize-none mb-2" placeholder="Alasan..."></textarea><p class="text-[9px] text-[#6B705C] mb-3">{{ unvalidateNotes.length }}/500</p><div class="grid grid-cols-2 gap-2"><button @click="showUnvalidateConfirm = false" class="py-2 bg-stone-100 text-[#8B5E3C] font-bold text-[10px] rounded-xl">Batal</button><button @click="executeUnvalidate" :disabled="unvalidateNotes.trim().length < 10" class="py-2 bg-amber-500 text-white font-bold text-[10px] rounded-xl disabled:opacity-50">Batalkan</button></div></div></div></Transition>
<Transition name="fade">
  <div v-if="previewImage" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80" @click="previewImage = null">
    <img :src="previewImage" :style="{ transform: `scale(${zoomLevel})` }" class="max-w-[90vw] max-h-[90vh] object-contain transition-transform duration-200 rounded-2xl shadow-2xl" @click.stop @wheel.prevent="handleWheel"/>
    <button @click="previewImage = null" class="absolute top-3 right-3 w-9 h-9 bg-black/50 rounded-full flex items-center justify-center">
      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
</Transition>
    <SuccessModal :show="showSuccess" :message="successMessage" @close="showSuccess = false"/>
  </div>
  <SmartScoreBreakdown v-if="detail" v-model="showSmartModal" :criteria="detail.program?.criteria?.inputs" :submissionData="detail.submission_data" :smartScore="detail.smart_score" :recommendation="detail.recommendation?.label"/>
  <DisburseSingleModal :open="showDisburseModal" :submission="{ id: detail?.id || '', registration_number: detail?.registration_number || '', status: detail?.status || '', citizen: { id: detail?.citizen?.id || '', full_name: detail?.citizen?.full_name || '', nik: detail?.citizen?.nik || '' }, program: { id: detail?.program?.id || '', name: detail?.program?.name || '', benefit_amount: detail?.program?.benefit_amount ?? 0 }, disbursement_method: detail?.disbursement_method || 'village_cash' }" :isSubmitting="submitting" @close="showDisburseModal = false" @confirm="handleDisburse"/>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import VerificationActions from './VerificationActions.vue';
import SuspiciousActivity from './detail/SuspiciousActivity.vue';
import EvidenceGallery from './detail/EvidenceGallery.vue';
import SmartScoreBreakdown from './detail/SmartScoreBreakdown.vue';
import ConfirmModal from '../common/ConfirmModal.vue';
import SuccessModal from '../common/SuccessModal.vue';
import { useVerification } from '../../composables/useVerification';
import { useDisbursement } from '../../composables/useDisbursement';
import DisburseSingleModal from '../../components/disbursement/DisburseSingleModal.vue';
import type { CriteriaInput, CriteriaDocument, ProgramCriteria } from '../../types/verification';

const route = useRoute();
const router = useRouter();
const { currentDetail: detail, loading, submitting, showSuccess, successMessage, fetchDetail, approve, reject, requestRevision, complete, unvalidate, showSuccessMessage } = useVerification();
const previewImage = ref<string | null>(null);
const showCompleteConfirm = ref(false);
const showUnvalidateConfirm = ref(false);
const unvalidateNotes = ref('');

const showSmartModal = ref(false);
const zoomedDocuments = ref<Record<string, boolean>>({});

const zoomLevel = ref(1);

const zoomIn = () => { zoomLevel.value = Math.min(zoomLevel.value + 0.25, 5); };
const zoomOut = () => { zoomLevel.value = Math.max(zoomLevel.value - 0.25, 0.25); };
const zoomReset = () => { zoomLevel.value = 1; };

const handleWheel = (e: WheelEvent) => {
  e.deltaY < 0 ? zoomIn() : zoomOut();
};

// ===== DOCUMENT HELPERS =====
const getDocKey = (doc: CriteriaDocument | string): string => {
  return typeof doc === 'string' ? doc : doc.key;
};

const getDocRequired = (doc: CriteriaDocument | string): boolean => {
  if (typeof doc === 'string') return true;
  return doc.required !== false;
};

const requiredDocuments = computed<CriteriaDocument[]>(() => {
  const docs = detail.value?.program?.criteria?.documents;
  if (!docs) return [];
  
  return docs
    .map((d: string | CriteriaDocument) => {
      if (typeof d === 'string') {
        return { key: d, required: true } as CriteriaDocument;
      }
      return d;
    })
    .filter((d: CriteriaDocument) => getDocRequired(d));
});
const allRequiredDocumentsZoomed = computed(() => {
  if (requiredDocuments.value.length === 0) return true;
  return requiredDocuments.value.every((d: CriteriaDocument) => zoomedDocuments.value[getDocKey(d)]);
});

const programInputs = computed<Array<{ key: string; label: string }>>(() => {
  const inputs = detail.value?.program?.criteria?.inputs;
  if (!inputs) return [];
  return inputs.map((i: CriteriaInput) => ({ key: i.key, label: i.label }));
});

// ===== EVENT HANDLERS =====
const handleImageClick = (imageUrl: string, imageType: string) => {
  previewImage.value = imageUrl;
  zoomedDocuments.value[imageType] = true;
};

onMounted(async () => {
  const id = route.params.id as string;
  if (id) await fetchDetail(id);
});

const handleApprove = async () => {
  if (!detail.value) return;
  const s = await approve(detail.value.id);
  if (s) {
    showSuccessMessage('Pengajuan berhasil disetujui.');
    setTimeout(() => router.push({ name: 'admin.verifications' }), 1000);
  }
};

const handleReject = async (notes: string) => {
  if (!detail.value) return;
  const s = await reject(detail.value.id, notes);
  if (s) {
    showSuccessMessage('Pengajuan berhasil ditolak.');
    setTimeout(() => router.push({ name: 'admin.verifications' }), 1000);
  }
};

const handleRevision = async (notes: string, items?: string[]) => {
  if (!detail.value) return;
  const s = await requestRevision(detail.value.id, notes, items);
  if (s) {
    showSuccessMessage('Perbaikan diminta.');
    setTimeout(() => router.push({ name: 'admin.verifications' }), 1000);
  }
};

const handleComplete = () => { showCompleteConfirm.value = true; };

const executeComplete = async () => {
  showCompleteConfirm.value = false;
  if (!detail.value) return;
  const s = await complete(detail.value.id);
  if (s) {
    showSuccessMessage('Pengajuan berhasil ditandai selesai.');
    setTimeout(() => router.push({ name: 'admin.verifications' }), 1000);
  }
};

const handleUnvalidate = () => {
  unvalidateNotes.value = '';
  showUnvalidateConfirm.value = true;
};

const executeUnvalidate = async () => {
  if (!unvalidateNotes.value.trim() || unvalidateNotes.value.trim().length < 10) return;
  showUnvalidateConfirm.value = false;
  if (!detail.value) return;
  const s = await unvalidate(detail.value.id, unvalidateNotes.value);
  if (s) {
    showSuccessMessage('Persetujuan berhasil dibatalkan.');
    setTimeout(() => router.push({ name: 'admin.verifications' }), 1000);
  }
};

// ===== FORMAT HELPERS =====
const formatStatus = (s: string) => ({ pending: 'Pending', validated: 'Disetujui', rejected: 'Ditolak', needs_revision: 'Revisi', completed: 'Selesai' }[s] || s);
const statusBadge = (s: string) => ({ pending: 'bg-yellow-100 text-yellow-700', validated: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700', needs_revision: 'bg-blue-100 text-blue-700', completed: 'bg-gray-100 text-gray-700' }[s] || 'bg-gray-100 text-gray-700');
const statusIconBg = (s: string) => ({ validated: 'bg-green-50', rejected: 'bg-red-50', needs_revision: 'bg-blue-50', completed: 'bg-gray-50' }[s] || 'bg-gray-50');
const statusIconColor = (s: string) => ({ validated: 'text-green-600', rejected: 'text-red-600', needs_revision: 'text-blue-600', completed: 'text-gray-600' }[s] || 'text-gray-600');
const statusDescription = (s: string) => ({ validated: 'Menunggu penyaluran bantuan.', rejected: 'Pengajuan ini telah ditolak.', needs_revision: 'Warga sedang memperbaiki data.', completed: 'Bantuan telah disalurkan.' }[s] || '');
const smartScoreClass = (sc: number | null) => { if (!sc) return 'text-gray-400'; if (sc >= 0.70) return 'text-green-600'; if (sc >= 0.50) return 'text-yellow-600'; if (sc >= 0.30) return 'text-orange-600'; return 'text-red-600'; };
const recommendationBadge = (c: string | undefined) => ({ green: 'bg-green-100 text-green-700', yellow: 'bg-yellow-100 text-yellow-700', orange: 'bg-orange-100 text-orange-700', red: 'bg-red-100 text-red-700', gray: 'bg-gray-100 text-gray-500' }[c || 'gray'] || 'bg-gray-100 text-gray-500');
const formatKey = (k: string | undefined) => (k || '').replace(/_/g, ' ');
const formatValue = (key: string, value: unknown): string => {
  if (typeof value === 'object' && value !== null) return JSON.stringify(value);
  const inputs = detail.value?.program?.criteria?.inputs || [];
  const input = inputs.find((i: CriteriaInput) => i.key === key);
  if (input?.type === 'currency') {
    const num = Number(value);
    return isNaN(num) ? String(value) : 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
  }
  if (input?.type === 'number' || input?.type === 'decimal') {
    const num = Number(value);
    return isNaN(num) ? String(value) : new Intl.NumberFormat('id-ID').format(num);
  }
  return String(value);
};
const formatDisbursement = (m: string) => ({ village_cash: 'Tunai (Kantor Desa)', bpd_transfer: 'Transfer Bank (BPD)' }[m] || m);
const displayableData = computed(() => {
  if (!detail.value?.submission_data) return {};
  const hidden = ['_method', 'id', 'created_at', 'updated_at', 'catatan_pencairan', '_idempotency_key', 'revision_notes', 'revision_items', 'revision_by', 'revision_at', 'verification_notes', 'verified_by', 'verified_at', 'nik', 'full_name', 'family_card_number', 'anomalies'];
  return Object.fromEntries(Object.entries(detail.value.submission_data).filter(([key]) => !hidden.includes(key)));
});

// ===== DISBURSEMENT =====
const { disburse: disburseSubmission } = useDisbursement();
const showDisburseModal = ref(false);

const openDisburseModal = () => {
  showDisburseModal.value = true;
};

const handleDisburse = async () => {
  if (!detail.value) return;
  const success = await disburseSubmission(detail.value.id);
  if (success) {
    showDisburseModal.value = false;
    showSuccessMessage('Bantuan berhasil disalurkan.');
    await fetchDetail(route.params.id as string);
  }
};
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>