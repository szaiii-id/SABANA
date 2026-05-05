<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAssistance } from '../../composables/useAssistance';
import { 
  ChevronRightIcon, 
  ExclamationTriangleIcon,
  TrashIcon, 
  ExclamationCircleIcon,
  FingerPrintIcon,
  InboxStackIcon,
  AcademicCapIcon,
  HomeModernIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const { fetchMySubmissions, deleteAssistance, isLoading } = useAssistance();
const submissions = ref<any[]>([]);

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const selectedRegNumber = ref('');

const loadData = async () => {
  try {
    const response = await fetchMySubmissions();
    if (response && response.data && Array.isArray(response.data)) {
        submissions.value = response.data;
    } else if (Array.isArray(response)) {
        submissions.value = response;
    }
  } catch (error) {
    console.error("Gagal mengambil riwayat:", error);
  }
};

onMounted(loadData);

/**
 * LOGIKA NAVIGASI PROFESIONAL
 * Mengarahkan user berdasarkan status dokumen
 */
const handleAction = (item: any) => {
  // Status yang memperbolehkan user melakukan edit/revisi
  const isEditable = ['pending', 'needs_revision'].includes(item.status);

  if (isEditable) {
    // Simpan ke localStorage hanya jika statusnya bisa diedit
    localStorage.setItem('SABANA_DRAFT', JSON.stringify(item));
    router.push({ name: 'assistance.edit', params: { id: item.id } });
  } else {
    // Jika status validated/approved/rejected, arahkan ke detail (Read Only)
    // PASTIKAN nama route 'assistance.detail' sesuai dengan konfigurasi router Anda
    router.push({ name: 'assistance.detail', params: { id: item.id } });
  }
};

const openDeleteModal = (regNumber: string) => {
  selectedRegNumber.value = regNumber;
  showDeleteModal.value = true;
};

const executeDelete = async () => {
  isDeleting.value = true;
  try {
    await deleteAssistance(selectedRegNumber.value);
    showDeleteModal.value = false;
    await loadData();
  } catch (error) {
    console.error("Gagal hapus:", error);
  } finally {
    isDeleting.value = false;
  }
};

const getStatusConfig = (status: string) => {
  const configs: Record<string, any> = {
    'pending': { label: 'Proses Verifikasi', color: 'text-amber-700 bg-amber-50 border-amber-200/50' },
    'needs_revision': { label: 'Perlu Revisi', color: 'text-red-700 bg-red-50 border-red-200/50 pulse-subtle' },
    'validated': { label: 'Tervalidasi', color: 'text-[#2D6A4F] bg-green-50 border-green-200/50' },
    'approved': { label: 'Disetujui', color: 'text-[#2D6A4F] bg-green-50 border-green-200/50' },
    'rejected': { label: 'Ditolak', color: 'text-stone-500 bg-stone-100 border-stone-200' }
  };
  return configs[status] || { label: status, color: 'text-stone-400 bg-stone-50' };
};
</script>

<template>
  <div class="min-h-screen bg-[#FDFBF9] p-4 md:p-8 font-sans antialiased text-[#4A3728]">
    
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

    <div class="max-w-4xl mx-auto">
      <header class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
        <div class="space-y-1">
          <p class="text-[#2D6A4F] font-black text-[9px] uppercase tracking-[0.4em]">Sistem Informasi Bantuan</p>
          <h1 class="text-3xl font-black tracking-tighter leading-none">Riwayat Pendaftaran</h1>
        </div>
        <div class="bg-white px-5 py-3 rounded-2xl border border-[#F3E5D8] flex items-center gap-4 shadow-sm">
          <div class="text-right">
            <p class="text-[8px] font-black text-[#8B5E3C] uppercase tracking-widest">Total Berkas</p>
            <p class="text-lg font-black text-[#2D6A4F] leading-none">{{ submissions.length }}</p>
          </div>
          <div class="w-10 h-10 bg-[#2D6A4F] rounded-xl flex items-center justify-center text-white">
            <InboxStackIcon class="w-5 h-5" />
          </div>
        </div>
      </header>

      <div v-if="!isLoading && submissions.length === 0" class="py-20 text-center">
         <div class="w-20 h-20 bg-stone-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-stone-200 border border-stone-100">
           <FingerPrintIcon class="w-10 h-10" />
         </div>
         <p class="text-[#8B5E3C] font-black uppercase tracking-[0.2em] text-[10px]">Belum ada pendaftaran yang terekam</p>
      </div>

      <div class="space-y-4">
        <div v-if="isLoading && submissions.length === 0" v-for="i in 3" :key="i" class="h-24 bg-stone-50 rounded-3xl animate-pulse border border-stone-100"></div>

        <div v-for="item in submissions" :key="item.id" 
          class="group bg-white border border-[#F3E5D8] rounded-[2rem] hover:shadow-xl hover:shadow-[#2D6A4F]/5 transition-all duration-500 overflow-hidden">
          
          <div class="p-5 md:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div @click="handleAction(item)" class="flex items-center gap-5 cursor-pointer flex-1 overflow-hidden">
              <div class="w-16 h-16 bg-[#FDF8F5] text-[#2D6A4F] rounded-2xl flex items-center justify-center border border-[#F3E5D8] group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-500 shrink-0">
                <HomeModernIcon v-if="item.program?.name?.toLowerCase().includes('rumah') || item.program?.name?.toLowerCase().includes('masjid')" class="w-8 h-8" />
                <AcademicCapIcon v-else-if="item.program?.name?.toLowerCase().includes('pintar') || item.program?.name?.toLowerCase().includes('beasiswa')" class="w-8 h-8" />
                <InboxStackIcon v-else class="w-8 h-8" />
              </div>
              
              <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#2D6A4F]/40 group-hover:bg-emerald-500 transition-colors"></span>
                  <p class="text-[8px] font-black text-[#8B5E3C] uppercase tracking-[0.3em] truncate">Resmi Sabana</p>
                </div>
                <h3 class="text-lg font-black tracking-tight group-hover:text-[#2D6A4F] transition-colors truncate">
                  {{ item.program?.name || 'Program Bantuan' }}
                </h3>
                <p class="font-mono text-[9px] font-bold text-[#8B5E3C]/60 tracking-widest uppercase">
                  {{ item.registration_number }}
                </p>
              </div>
            </div>

            <div class="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 pt-4 md:pt-0 border-stone-50">
              <div class="text-left md:text-right shrink-0">
                <p class="text-[8px] font-black text-[#8B5E3C]/50 uppercase tracking-widest mb-1">Status Berkas</p>
                <div :class="getStatusConfig(item.status).color" class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border transition-all">
                  {{ getStatusConfig(item.status).label }}
                </div>
              </div>
              
              <div class="flex items-center gap-2 shrink-0">
                  <button 
                    v-if="['pending', 'needs_revision'].includes(item.status)"
                    @click.stop="openDeleteModal(item.registration_number)"
                    class="w-11 h-11 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all flex items-center justify-center border border-red-100"
                  >
                    <TrashIcon class="w-5 h-5" />
                  </button>

                  <button @click="handleAction(item)" class="w-11 h-11 bg-[#4A3728] text-white rounded-xl hover:bg-[#2D6A4F] transition-all flex items-center justify-center shadow-md">
                    <ChevronRightIcon class="w-5 h-5" />
                  </button>
              </div>
            </div>
          </div>

          <div v-if="item.status === 'needs_revision' && item.admin_note" class="bg-red-50/40 p-4 border-t border-red-100 flex gap-3">
             <ExclamationTriangleIcon class="w-4 h-4 text-red-500 shrink-0" />
             <div class="min-w-0">
                <p class="text-[8px] font-black text-red-400 uppercase tracking-widest mb-0.5">Catatan Perbaikan:</p>
                <p class="text-[11px] text-red-800 font-bold italic truncate">"{{ item.admin_note }}"</p>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.pulse-subtle { animation: pulse-red 2s infinite; }
@keyframes pulse-red {
  0% { border-color: rgba(239, 68, 68, 0.2); }
  50% { border-color: rgba(239, 68, 68, 0.5); }
  100% { border-color: rgba(239, 68, 68, 0.2); }
}
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #EADDCD; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #DEB887; }
</style>