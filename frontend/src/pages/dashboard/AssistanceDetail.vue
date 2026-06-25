<template>
  <div class="min-h-screen bg-[#FDFBF9] p-4 md:p-8 font-sans text-[#4A3728]">
    <div v-if="submission" class="max-w-4xl mx-auto space-y-6 animate-fade-in">
      
      <button @click="router.back()" class="flex items-center gap-2 text-[#8B5E3C] hover:text-[#2D6A4F] font-black text-[10px] uppercase tracking-widest transition-all group">
        <ArrowLeftIcon class="w-4 h-4 group-hover:-translate-x-1 transition-transform" /> Kembali Ke Riwayat
      </button>

      <!-- HEADER -->
      <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border-2 border-[#F3E5D8] shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-10 opacity-[0.03] pointer-events-none"><CheckBadgeIcon class="w-40 h-40" /></div>
        <div class="relative z-10">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 rounded-full" :class="statusDot(submission.status)"></div>
            <p class="text-[#2D6A4F] font-black text-[9px] uppercase tracking-[0.4em]">Detail Pengajuan</p>
          </div>
          <h1 class="text-3xl font-black tracking-tighter leading-tight mb-2">{{ submission.program?.name || submission.program?.title }}</h1>
          <div class="flex items-center gap-3">
            <span class="font-mono text-[11px] font-bold text-[#8B5E3C] tracking-[0.2em] bg-[#FDF8F5] px-4 py-1 rounded-full border border-[#F3E5D8]">{{ submission.registration_number }}</span>
            <span class="text-[10px] text-[#8B5E3C]">·</span>
            <span class="text-[11px] font-bold text-[#8B5E3C]">{{ formatDate(submission.submitted_at) }}</span>
          </div>
        </div>
        <div :class="getStatusStyle(submission.status)" class="px-6 py-3 rounded-2xl border-2 text-[10px] font-black uppercase tracking-widest relative z-10">{{ getStatusLabel(submission.status) }}</div>
      </div>

      <!-- INFO VERIFIKATOR / EVALUASI -->
      <div 
        v-if="submission.verified_by_name || submission.revision_by_name || submission.status === 'pending' || submission.status === 'rejected' || submission.evaluation_notes"
        :class="verificationBoxClass"
        class="rounded-[2.5rem] p-6 md:p-8 border-2 space-y-3"
      >
        <template v-if="submission.status === 'pending'">
          <h3 class="text-amber-600 text-xs font-black uppercase tracking-widest">Menunggu Verifikasi</h3>
          <p class="text-amber-700 text-sm font-medium">Pengajuan Anda sedang dalam antrean verifikasi oleh Admin SABANA.</p>
        </template>

        <template v-else-if="submission.status === 'validated' || submission.status === 'completed'">
          <h3 class="text-green-600 text-xs font-black uppercase tracking-widest">
            {{ submission.status === 'completed' ? 'Selesai Disalurkan' : 'Disetujui' }}
          </h3>
          <div class="flex items-center gap-4 text-xs">
            <span class="text-green-700 font-bold">Oleh: {{ submission.verified_by_name || 'Admin SABANA' }}</span>
            <span class="w-1 h-1 rounded-full bg-current opacity-30"></span>
            <span class="text-green-600 font-medium">{{ formatDateTime(submission.verified_at) }}</span>
          </div>
          <p v-if="submission.status === 'completed'" class="text-green-700 text-sm font-medium">Bantuan telah disalurkan.</p>
        </template>

        <template v-else-if="submission.status === 'rejected'">
          <h3 class="text-red-600 text-xs font-black uppercase tracking-widest">
            {{ submission.evaluation_notes ? 'Dihentikan (Evaluasi)' : 'Ditolak' }}
          </h3>
          <div v-if="submission.verified_by_name" class="flex items-center gap-4 text-xs">
            <span class="text-red-700 font-bold">Oleh: {{ submission.verified_by_name }}</span>
            <span class="w-1 h-1 rounded-full bg-current opacity-30"></span>
            <span class="text-red-600 font-medium">{{ formatDateTime(submission.verified_at) }}</span>
          </div>
          <div v-if="submission.rejection_note" class="pt-2 border-t border-current/10">
            <p class="text-red-800 text-sm font-medium italic leading-relaxed">"{{ submission.rejection_note }}"</p>
          </div>
          <div v-if="submission.evaluation_notes" class="pt-2 border-t border-current/10">
            <p class="text-red-800 text-sm font-medium italic leading-relaxed">"{{ submission.evaluation_notes }}"</p>
          </div>
        </template>

        <template v-else-if="submission.status === 'needs_revision'">
          <h3 class="text-blue-600 text-xs font-black uppercase tracking-widest">Diminta Perbaikan</h3>
          <div class="flex items-center gap-4 text-xs">
            <span class="text-blue-700 font-bold">Oleh: {{ submission.revision_by_name || 'Admin SABANA' }}</span>
            <span class="w-1 h-1 rounded-full bg-current opacity-30"></span>
            <span class="text-blue-600 font-medium">{{ formatDateTime(submission.revision_at) }}</span>
          </div>
          <div v-if="submission.revision_items?.length" class="flex flex-wrap gap-2 pt-1">
            <span v-for="item in submission.revision_items" :key="item" class="px-3 py-1 bg-white border border-blue-200 text-blue-700 rounded-lg text-[10px] font-bold">{{ formatRevisionItem(item) }}</span>
          </div>
          <div v-if="submission.revision_note" class="pt-2 border-t border-current/10">
            <p class="text-blue-800 text-sm font-medium italic leading-relaxed">"{{ submission.revision_note }}"</p>
          </div>
        </template>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
          
          <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border-2 border-[#F3E5D8] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-8 flex items-center gap-3">
              <div class="w-8 h-8 bg-[#FDF8F5] rounded-xl flex items-center justify-center text-[#2D6A4F]"><IdentificationIcon class="w-5 h-5" /></div>Data Pengajuan
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-[9px] font-black text-[#DEB887] uppercase tracking-widest">Nama Lengkap</label>
                <p class="font-bold text-lg leading-tight">{{ submission.citizen?.full_name ?? '---' }}</p>
              </div>
              <div class="space-y-1">
                <label class="block text-[9px] font-black text-[#DEB887] uppercase tracking-widest">NIK Warga</label>
                <p class="font-bold text-lg leading-tight tracking-wider">{{ submission.citizen?.nik ?? '---' }}</p>
              </div>
              <template v-for="(value, key) in submission.submission_data" :key="key">
                <div v-if="isDisplayable(String(key))" class="space-y-1">
                  <label class="block text-[9px] font-black text-[#DEB887] uppercase tracking-widest">
                    {{ formatKey(String(key)) }}
                  </label>
                  <p class="font-bold text-lg leading-tight">{{ formatValue(String(key), value) }}</p>
                </div>
              </template>
            </div>
          </div>

          <div v-if="submission.evidences?.length" class="bg-white rounded-[2.5rem] p-8 md:p-10 border-2 border-[#F3E5D8] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-8 flex items-center gap-3">
              <div class="w-8 h-8 bg-[#FDF8F5] rounded-xl flex items-center justify-center text-[#2D6A4F]"><PhotoIcon class="w-5 h-5" /></div>Dokumen Bukti
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div v-for="img in submission.evidences" :key="img.id" class="relative group cursor-pointer rounded-2xl overflow-hidden border-2 border-[#E8D5C4] bg-[#FAF6F0] hover:border-[#1B4332] transition-all" @click="previewImage = img.image_url">
                <img :src="img.image_url" :alt="img.image_type" class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-500" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                  <span class="text-white text-[10px] font-bold uppercase tracking-wider">{{ formatKey(String(img.image_type)) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white rounded-[2.5rem] p-6 border-2 border-[#E8D5C4] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-4 flex items-center gap-2"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg> Penyaluran</h3>
            <p class="text-sm font-bold text-[#1B4332]">{{ formatDisbursement(submission.disbursement_method) }}</p>
            <p v-if="submission.bank_account_number" class="text-xs text-[#6B705C] mt-1 font-mono tracking-wider">{{ submission.bank_account_number }}</p>
          </div>

          <div class="bg-white rounded-[2.5rem] p-6 border-2 border-[#E8D5C4] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-4 flex items-center gap-2"><svg class="w-4 h-4 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg> Lokasi</h3>
            <p class="text-sm font-bold text-[#4A3728] leading-relaxed">{{ submission.village?.name || '-' }}, {{ submission.district?.name || '-' }}</p>
            <p class="text-xs text-[#8B5E3C] uppercase tracking-wider mt-1">{{ submission.regency?.name || '-' }}</p>
          </div>

          <button 
            v-if="['validated', 'approved', 'completed'].includes(submission.status)"
            @click="handleDownload"
            :disabled="isLoading"
            class="w-full py-5 bg-[#2D6A4F] text-white rounded-[2rem] font-black uppercase text-[10px] tracking-[0.3em] shadow-xl shadow-green-900/20 hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-3"
          >
            <ArrowDownTrayIcon v-if="!isLoading" class="w-4 h-4" />
            <div v-else class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            {{ isLoading ? 'Menyiapkan...' : 'Unduh Bukti Sah' }}
          </button>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="previewImage" class="fixed inset-0 z-[60] flex items-center justify-center p-8 bg-[#1B4332]/95 backdrop-blur-sm" @click="previewImage = null">
        <img :src="previewImage" class="max-w-[600px] max-h-[75vh] rounded-2xl shadow-2xl object-contain" />
        <button @click="previewImage = null" class="absolute top-4 right-4 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-all"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAssistance } from '../../composables/useAssistance';
import type { SubmissionDetailData } from '../../types/assistance';
import { ArrowLeftIcon, PhotoIcon, IdentificationIcon, ArrowDownTrayIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { fetchDetail, downloadPdf, isLoading } = useAssistance();
const submission = ref<SubmissionDetailData | null>(null);
const previewImage = ref<string | null>(null);

onMounted(async () => {
  const id = route.params.id as string;
  try { submission.value = await fetchDetail(id); }
  catch { router.push({ name: 'history' }); }
});

const verificationBoxClass = computed(() => {
  switch (submission.value?.status) {
    case 'pending': return 'bg-amber-50 border-amber-200';
    case 'validated': case 'approved': case 'completed': return 'bg-green-50 border-green-200';
    case 'rejected': return 'bg-red-50 border-red-200';
    case 'needs_revision': return 'bg-blue-50 border-blue-200';
    default: return 'bg-gray-50 border-gray-200';
  }
});

const isDisplayable = (key: string): boolean => {
  const hiddenKeys = [
    '_method', 'id', 'created_at', 'updated_at', 'catatan_pencairan',
    '_idempotency_key', 'revision_notes', 'revision_items', 'revision_by', 'revision_by_name', 'revision_at',
    'verification_notes', 'verified_by', 'verified_by_name', 'verified_at',
    'nik', 'full_name', 'family_card_number'
  ];
  return !hiddenKeys.includes(key);
};

const formatDate = (date: string | null | undefined): string => date ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
const formatDateTime = (date: string | null | undefined): string => date ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
const formatDisbursement = (m?: string): string => m === 'village_cash' ? 'Tunai (Balai Desa)' : 'Transfer Bank (BPD)';

const handleDownload = async (): Promise<void> => {
  if (!submission.value) return;
  await downloadPdf(submission.value.id);
};

const getStatusLabel = (s: string): string => ({ pending: 'Proses Verifikasi', needs_revision: 'Perlu Revisi', validated: 'Tervalidasi', approved: 'Disetujui', rejected: 'Ditolak', completed: 'Selesai' }[s] || s);
const getStatusStyle = (s: string): string => ({ pending: 'text-amber-700 bg-amber-50 border-amber-200/50', needs_revision: 'text-blue-700 bg-blue-50 border-blue-200/50', validated: 'text-[#2D6A4F] bg-green-50 border-green-200/50', approved: 'text-[#2D6A4F] bg-green-50 border-green-200/50', rejected: 'text-red-700 bg-red-50 border-red-200/50', completed: 'text-gray-500 bg-gray-50 border-gray-200' }[s] || 'bg-stone-50 text-stone-400');
const statusDot = (s: string): string => ({ pending: 'bg-amber-500', needs_revision: 'bg-blue-500', validated: 'bg-green-500', approved: 'bg-green-500', rejected: 'bg-red-500', completed: 'bg-gray-400' }[s] || 'bg-gray-400');
const formatRevisionItem = (k: string): string => k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

const formatKey = (key: string): string => key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());

const formatValue = (key: string, value: unknown): string => {
  const inputs = submission.value?.program?.criteria?.inputs as Array<{ key: string; type: string }> || [];
  const input = inputs.find((i) => i.key === key);
  if (input?.type === 'currency') { const num = Number(value); return isNaN(num) ? String(value) : 'Rp ' + new Intl.NumberFormat('id-ID').format(num); }
  if (input?.type === 'number' || input?.type === 'decimal') { const num = Number(value); return isNaN(num) ? String(value) : new Intl.NumberFormat('id-ID').format(num); }
  return String(value);
};
</script>