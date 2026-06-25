<template>
  <div class="min-h-screen bg-[#FDFBF9] p-4 md:p-8 font-sans antialiased text-[#4A3728]">
    
    <!-- Delete Confirmation Modal -->
    <transition name="modal">
      <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#4A3728]/30 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full text-center shadow-2xl relative z-10 border border-[#F3E5D8]">
          <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <ExclamationCircleIcon class="w-10 h-10" />
          </div>
          <h3 class="text-xl font-black mb-2 tracking-tight">Batalkan Berkas?</h3>
          <p class="text-[#8B5E3C] text-xs mb-8 leading-relaxed font-medium">Data pendaftaran akan dihapus permanen dari sistem <span class="font-black text-[#2D6A4F]">SABANA</span>.</p>
          <div class="grid grid-cols-2 gap-3">
            <button @click="showDeleteModal = false" class="py-3 px-4 bg-stone-100 text-[#8B5E3C] font-black uppercase tracking-widest text-[9px] rounded-xl hover:bg-stone-200 transition-colors">Batal</button>
            <button @click="executeDelete" :disabled="isDeleting" class="py-3 px-4 bg-red-500 text-white font-black uppercase tracking-widest text-[9px] rounded-xl shadow-lg shadow-red-200 hover:bg-red-600 transition-all flex items-center justify-center gap-2">
              <ArrowPathIcon v-if="isDeleting" class="w-3 h-3 animate-spin" />
              {{ isDeleting ? 'Menghapus' : 'Ya, Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <DisbursementReceiptModal
      :visible="showReceiptModal"
      :data="receiptData"
      :submission-id="selectedSubmissionId"
      @close="showReceiptModal = false"
      @download="handleDownloadPdf"
    />

    <div class="max-w-4xl mx-auto">
      <header class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
        <div class="space-y-1">
          <p class="text-[#2D6A4F] font-black text-[9px] uppercase tracking-[0.4em]">Sistem Informasi Bantuan</p>
          <h1 class="text-3xl font-black tracking-tighter leading-none">Riwayat Pendaftaran</h1>
        </div>
        <div class="bg-white px-5 py-3 rounded-2xl border border-[#F3E5D8] flex items-center gap-4 shadow-sm">
          <div class="text-right">
            <p class="text-[8px] font-black text-[#8B5E3C] uppercase tracking-widest">Total Program</p>
            <p class="text-lg font-black text-[#2D6A4F] leading-none">{{ submissions.length }}</p>
          </div>
          <div class="w-10 h-10 bg-[#2D6A4F] rounded-xl flex items-center justify-center text-white">
            <InboxStackIcon class="w-5 h-5" />
          </div>
        </div>
      </header>

      <!-- Empty State -->
      <div v-if="!isLoading && submissions.length === 0" class="py-20 text-center">
         <div class="w-20 h-20 bg-stone-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-stone-200 border border-stone-100">
           <FingerPrintIcon class="w-10 h-10" />
         </div>
         <p class="text-[#8B5E3C] font-black uppercase tracking-[0.2em] text-[10px]">Belum ada pendaftaran yang terekam</p>
      </div>

      <!-- Skeleton Loader -->
      <div class="space-y-4">
        <div v-if="isLoading && submissions.length === 0" v-for="i in 3" :key="i" class="h-24 bg-stone-50 rounded-3xl animate-pulse border border-stone-100"></div>

        <!-- Card per Program -->
        <div v-for="item in submissions" :key="item.program_id" 
          class="group bg-white border border-[#F3E5D8] rounded-[2rem] hover:shadow-xl hover:shadow-[#2D6A4F]/5 transition-all duration-500 overflow-hidden">
          
          <!-- Header Card -->
          <div class="p-5 md:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div @click="handleAction(item)" class="flex items-center gap-5 cursor-pointer flex-1 overflow-hidden">
              <div class="w-16 h-16 bg-[#FDF8F5] text-[#2D6A4F] rounded-2xl flex items-center justify-center border border-[#F3E5D8] group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-500 shrink-0">
                <HomeModernIcon v-if="item.program_name?.toLowerCase().includes('rumah') || item.program_name?.toLowerCase().includes('masjid')" class="w-8 h-8" />
                <AcademicCapIcon v-else-if="item.program_name?.toLowerCase().includes('pintar') || item.program_name?.toLowerCase().includes('beasiswa')" class="w-8 h-8" />
                <InboxStackIcon v-else class="w-8 h-8" />
              </div>
              
              <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#2D6A4F]/40 group-hover:bg-emerald-500 transition-colors"></span>
                  <p class="text-[8px] font-black text-[#8B5E3C] uppercase tracking-[0.3em] truncate">Resmi Sabana</p>
                  <span v-if="item.current_status === 'evaluation_pending'" 
                    class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-[8px] font-black uppercase tracking-wider animate-pulse">
                    Evaluasi
                  </span>
                  <span v-if="item.current_status === 'needs_revision'" 
                    class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[8px] font-black uppercase tracking-wider">
                    Revisi
                  </span>
                  <span v-if="item.current_status === 'completed'" 
                    class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[8px] font-black uppercase tracking-wider">
                    Disalurkan
                  </span>
                </div>
                <h3 class="text-lg font-black tracking-tight group-hover:text-[#2D6A4F] transition-colors truncate">
                  {{ item.program_name || 'Program Bantuan' }}
                </h3>
                <p class="font-mono text-[9px] font-bold text-[#8B5E3C]/60 tracking-widest uppercase">
                  {{ item.registration_number }}
                </p>
              </div>
            </div>

            <div class="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 pt-4 md:pt-0 border-stone-50">
              <div class="text-left md:text-right shrink-0">
                <p class="text-[8px] font-black text-[#8B5E3C]/50 uppercase tracking-widest mb-1">Status</p>
                <div :class="getStatusConfig(item.current_status).color" class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border transition-all">
                  {{ item.current_status_label }}
                </div>
              </div>
              
              <div class="flex items-center gap-2 shrink-0">
                  <!-- Bukti Penyaluran -->
                  <button 
                    v-if="item.current_status === 'completed' && item.latest_submission_id"
                    @click.stop="openReceipt(item.latest_submission_id)"
                    class="px-4 py-2.5 bg-[#1B4332] text-white font-black uppercase tracking-widest text-[9px] rounded-xl hover:bg-[#2D6A4F] transition-all flex items-center gap-2 shadow-md">
                    <CheckBadgeIcon class="w-4 h-4" />
                    Bukti
                  </button>

                  <!-- Action Button -->
                  <button @click="handleAction(item)" 
                    :class="[
                      'w-11 h-11 rounded-xl transition-all flex items-center justify-center shadow-md',
                      item.current_status === 'evaluation_pending'
                        ? 'bg-orange-500 text-white hover:bg-orange-600' 
                        : item.current_status === 'needs_revision'
                          ? 'bg-blue-500 text-white hover:bg-blue-600'
                          : 'bg-[#4A3728] text-white hover:bg-[#2D6A4F]'
                    ]">
                    <svg v-if="item.current_status === 'evaluation_pending'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                    </svg>
                    <svg v-else-if="item.current_status === 'needs_revision'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    <ChevronRightIcon v-else class="w-5 h-5" />
                  </button>
              </div>
            </div>
          </div>

          <!-- ===== TIMELINE BAR ===== -->
          <div class="px-5 md:px-6 pb-4">
            <div class="flex items-center gap-1 overflow-x-auto py-2">
              <template v-for="(step, index) in item.timeline" :key="step.id || index">
                <div 
                  :class="[
                    'w-3 h-3 rounded-full flex-shrink-0 border-2',
                    getTimelineDotColor(step.color)
                  ]"
                  :title="step.label + ' — ' + step.date"
                ></div>
                <div 
                  v-if="index < item.timeline.length - 1"
                  :class="[
                    'h-0.5 flex-1 min-w-[16px] rounded-full',
                    getTimelineLineColor(step.color, item.timeline[index + 1].color)
                  ]"
                ></div>
              </template>
            </div>
            <div class="flex justify-between text-[8px] font-bold text-[#8B5E3C]/50 uppercase tracking-wider mt-1">
              <span>{{ item.timeline[0]?.label || '' }}</span>
              <span>{{ item.timeline[item.timeline.length - 1]?.label || '' }}</span>
            </div>
          </div>

          <!-- Needs Revision Notice -->
          <div v-if="item.current_status === 'needs_revision'" class="bg-blue-50/60 p-4 border-t border-blue-100 space-y-3">
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" />
              <div class="min-w-0 flex-1">
                <p class="text-[8px] font-black text-blue-500 uppercase tracking-widest mb-2">Perlu Perbaikan</p>
                
                <!-- Revision Items -->
                <div v-if="item.revision_items?.length" class="flex flex-wrap gap-1.5 mb-2">
                  <span v-for="ri in item.revision_items" :key="ri" class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded-lg text-[10px] font-bold">
                    {{ formatRevisionItem(ri) }}
                  </span>
                </div>
                
                <!-- Admin Note -->
                <p v-if="item.admin_note" class="text-[11px] text-blue-800 font-medium italic leading-relaxed">
                  "{{ item.admin_note }}"
                </p>
                
                <p v-else class="text-[11px] text-blue-800 font-medium">
                  Pengajuan Anda perlu diperbaiki. Silakan klik untuk melihat detail dan mengunggah ulang dokumen.
                </p>
              </div>
            </div>
          </div>

          <!-- Evaluation Pending Notice -->
          <div v-if="item.current_status === 'evaluation_pending'" class="bg-orange-50/60 p-4 border-t border-orange-100 space-y-2">
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" />
              <div class="min-w-0 flex-1">
                <p class="text-[8px] font-black text-orange-600 uppercase tracking-widest mb-1">Evaluasi 6 Bulan</p>
                <p class="text-[11px] text-orange-800 font-medium">
                  Program Anda telah memasuki masa evaluasi. Silakan perbarui data Anda untuk melanjutkan bantuan.
                </p>
              </div>
            </div>
          </div>

          <!-- Completed Notice -->
          <div v-if="item.current_status === 'completed'" class="bg-green-50/60 p-4 border-t border-green-100 space-y-2">
            <div class="flex items-start gap-3">
              <CheckBadgeIcon class="w-4 h-4 text-green-600 shrink-0 mt-0.5" />
              <div class="min-w-0 flex-1">
                <p class="text-[8px] font-black text-green-600 uppercase tracking-widest mb-1">Bantuan Disalurkan</p>
                <p class="text-[11px] text-green-800 font-medium">
                  Dana bantuan telah disalurkan. 
                  <span v-if="item.disbursement?.reference_number" class="font-bold">No. Ref: {{ item.disbursement.reference_number }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAssistance } from '../../composables/useAssistance';
import DisbursementReceiptModal from '../../components/common/DisbursementReceiptModal.vue';
import type { DisbursementReceipt, SubmissionHistoryItem } from '../../types/assistance';
import { 
  ChevronRightIcon, 
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  FingerPrintIcon,
  InboxStackIcon,
  AcademicCapIcon,
  HomeModernIcon,
  ArrowPathIcon,
  CheckBadgeIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const { 
  fetchMySubmissions, 
  isLoading,
  fetchDisbursementReceipt,
  downloadDisbursementPdf
} = useAssistance();

const submissions = ref<SubmissionHistoryItem[]>([]);

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const selectedRegNumber = ref('');

const showReceiptModal = ref(false);
const receiptData = ref<DisbursementReceipt | null>(null);
const selectedSubmissionId = ref('');

const loadData = async () => {
  try {
    const response = await fetchMySubmissions();
    if (response && response.data && Array.isArray(response.data)) {
        submissions.value = response.data as SubmissionHistoryItem[];
    }
  } catch (error) {
    console.error("Gagal mengambil riwayat:", error);
  }
};

onMounted(loadData);

const handleAction = (item: SubmissionHistoryItem) => {
  if (item.current_status === 'evaluation_pending') {
    localStorage.setItem('SABANA_DRAFT', JSON.stringify({ 
      id: item.latest_submission_id,
      status: item.current_status 
    }));
    router.push({ name: 'assistance.edit', params: { id: item.latest_submission_id } });
    return;
  }

  if (item.current_status === 'needs_revision') {
    localStorage.setItem('SABANA_DRAFT', JSON.stringify({ 
      id: item.latest_submission_id,
      status: item.current_status 
    }));
    router.push({ name: 'assistance.edit', params: { id: item.latest_submission_id } });
    return;
  }

  router.push({ name: 'assistance.detail', params: { id: item.latest_submission_id } });
};

const openReceipt = async (submissionId: string) => {
  selectedSubmissionId.value = submissionId;
  try {
    const response = await fetchDisbursementReceipt(submissionId);
    receiptData.value = response.data;
    showReceiptModal.value = true;
  } catch (err) {
    console.error('Gagal memuat bukti:', err);
  }
};

const handleDownloadPdf = async (submissionId: string) => {
  try {
    await downloadDisbursementPdf(submissionId);
  } catch (err) {
    console.error('Gagal unduh PDF:', err);
  }
};

const executeDelete = async () => {
  isDeleting.value = true;
  try {
    showDeleteModal.value = false;
    await loadData();
  } catch (error) {
    console.error("Gagal hapus:", error);
  } finally {
    isDeleting.value = false;
  }
};

const formatRevisionItem = (key: string): string => {
  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (l) => l.toUpperCase());
};

const getStatusConfig = (status: string): { label: string; color: string } => {
  const configs: Record<string, { label: string; color: string }> = {
    'pending': { label: 'Proses Verifikasi', color: 'text-amber-700 bg-amber-50 border-amber-200/50' },
    'needs_revision': { label: 'Perlu Revisi', color: 'text-blue-700 bg-blue-50 border-blue-200/50' },
    'evaluation_pending': { label: 'Perlu Diperbarui', color: 'text-orange-700 bg-orange-50 border-orange-200/50' },
    'validated': { label: 'Tervalidasi', color: 'text-[#2D6A4F] bg-green-50 border-green-200/50' },
    'approved': { label: 'Disetujui', color: 'text-[#2D6A4F] bg-green-50 border-green-200/50' },
    'completed': { label: 'Disalurkan', color: 'text-[#2D6A4F] bg-green-50 border-green-200/50' },
    'rejected': { label: 'Ditolak', color: 'text-stone-500 bg-stone-100 border-stone-200' },
    'revoked': { label: 'Dihentikan', color: 'text-red-700 bg-red-50 border-red-200/50' },
    'evaluation_approved': { label: 'Dilanjutkan', color: 'text-green-700 bg-green-50 border-green-200/50' },
    'evaluation_revoked': { label: 'Dihentikan', color: 'text-red-700 bg-red-50 border-red-200/50' },
  };
  return configs[status] || { label: status, color: 'text-stone-400 bg-stone-50' };
};

const getTimelineDotColor = (color: string): string => {
  const map: Record<string, string> = {
    blue: 'bg-blue-500 border-blue-300',
    yellow: 'bg-yellow-500 border-yellow-300',
    green: 'bg-green-500 border-green-300',
    red: 'bg-red-500 border-red-300',
    orange: 'bg-orange-500 border-orange-300',
    gray: 'bg-gray-400 border-gray-300',
  };
  return map[color] || 'bg-gray-400 border-gray-300';
};

const getTimelineLineColor = (from: string, to: string): string => {
  if (from === 'green' && to === 'green') return 'bg-green-400';
  if (from === 'green' && to === 'orange') return 'bg-gradient-to-r from-green-400 to-orange-400';
  if (from === 'orange' && to === 'green') return 'bg-gradient-to-r from-orange-400 to-green-400';
  if (from === 'orange' && to === 'red') return 'bg-gradient-to-r from-orange-400 to-red-400';
  return 'bg-gray-300';
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #EADDCD; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #DEB887; }
</style>