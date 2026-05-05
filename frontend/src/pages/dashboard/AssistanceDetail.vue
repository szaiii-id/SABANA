<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAssistance } from '../../composables/useAssistance';
import { 
  ArrowLeftIcon, PhotoIcon, IdentificationIcon, 
  CreditCardIcon, ArrowDownTrayIcon, CheckBadgeIcon,
  MapPinIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { fetchDetail, downloadPdf, isLoading } = useAssistance();
const submission = ref<any>(null);

onMounted(async () => {
  const id = route.params.id as string;
  try {
    submission.value = await fetchDetail(id);
  } catch (err) {
    router.push({ name: 'history' });
  }
});

const isDisplayable = (key: string) => {
  const hiddenKeys = ['_method', 'id', 'created_at', 'updated_at', 'catatan_pencairan'];
  return !hiddenKeys.includes(key);
};

const formatDisbursement = (method: string) => {
  return method === 'village_cash' ? 'Tunai (Balai Desa)' : 'Transfer Bank (BPD)';
};

const handleDownload = async () => {
  if (!submission.value) return;
  const fileName = `SABANA_${submission.value.registration_number}`;
  await downloadPdf(submission.value.id, fileName);
};

const getStatusStyle = (status: string) => {
  const styles: Record<string, string> = {
    'pending': 'text-amber-700 bg-amber-50 border-amber-200/50',
    'needs_revision': 'text-red-700 bg-red-50 border-red-200/50',
    'validated': 'text-[#2D6A4F] bg-green-50 border-green-200/50',
    'approved': 'text-[#2D6A4F] bg-green-50 border-green-200/50',
    'rejected': 'text-stone-500 bg-stone-50 border-stone-200'
  };
  return styles[status] || 'bg-stone-50 text-stone-400';
};
</script>

<template>
  <div class="min-h-screen bg-[#FDFBF9] p-4 md:p-8 font-sans text-[#4A3728]">
    <div v-if="submission" class="max-w-4xl mx-auto space-y-6 animate-fade-in">
      
      <button @click="router.back()" class="flex items-center gap-2 text-[#8B5E3C] hover:text-[#2D6A4F] font-black text-[10px] uppercase tracking-widest transition-all group">
        <ArrowLeftIcon class="w-4 h-4 group-hover:-translate-x-1 transition-transform" /> Kembali Ke Riwayat
      </button>

      <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border-2 border-[#F3E5D8] shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-10 opacity-[0.03] pointer-events-none">
          <CheckBadgeIcon class="w-40 h-40" />
        </div>
        
        <div class="relative z-10">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 bg-[#2D6A4F] rounded-full animate-pulse"></div>
            <p class="text-[#2D6A4F] font-black text-[9px] uppercase tracking-[0.4em]">Detail Pengajuan Sah</p>
          </div>
          <h1 class="text-3xl font-black tracking-tighter leading-tight mb-2">
            {{ submission.program?.name || submission.program?.title }}
          </h1>
          <div class="flex items-center gap-3">
            <span class="font-mono text-[11px] font-bold text-[#8B5E3C] tracking-[0.2em] bg-[#FDF8F5] px-4 py-1 rounded-full border border-[#F3E5D8]">
              {{ submission.registration_number }}
            </span>
          </div>
        </div>

        <div :class="getStatusStyle(submission.status)" class="px-6 py-3 rounded-2xl border-2 text-[10px] font-black uppercase tracking-widest relative z-10">
          {{ String(submission.status).replace('_', ' ') }}
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border-2 border-[#F3E5D8] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-10 flex items-center gap-3">
              <div class="w-8 h-8 bg-[#FDF8F5] rounded-xl flex items-center justify-center text-[#2D6A4F]"><IdentificationIcon class="w-5 h-5" /></div>
              Data Identitas
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
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
                    {{ String(key).replace(/_/g, ' ') }}
                  </label>
                  <p class="font-bold text-lg leading-tight">{{ value }}</p>
                </div>
              </template>
            </div>
          </div>

          <div class="bg-white rounded-[2.5rem] p-8 md:p-10 border-2 border-[#F3E5D8] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-10 flex items-center gap-3">
              <div class="w-8 h-8 bg-[#FDF8F5] rounded-xl flex items-center justify-center text-[#2D6A4F]"><PhotoIcon class="w-5 h-5" /></div>
              Dokumen Terlampir
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
              <div v-for="img in submission.evidences" :key="img.id" class="group relative aspect-[4/5] rounded-[2rem] overflow-hidden border-2 border-[#F3E5D8] bg-[#FDF8F5]">
                <img :src="img.image_url" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
                <div class="absolute inset-0 bg-[#2D6A4F]/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center p-4">
                  <span class="text-white text-[9px] font-black uppercase tracking-widest text-center">{{ String(img.image_type).replace(/_/g, ' ') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white rounded-[2.5rem] p-8 border-2 border-[#F3E5D8] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-8 flex items-center gap-3">
              <CreditCardIcon class="w-5 h-5" /> Penyaluran
            </h3>
            <div class="space-y-4">
              <div class="p-5 bg-[#FDF8F5] rounded-[1.5rem] border border-[#F3E5D8]">
                <p class="text-[8px] font-black text-[#DEB887] uppercase tracking-widest mb-1">Metode Terpilih</p>
                <p class="text-xs font-black">{{ formatDisbursement(submission.disbursement_method) }}</p>
              </div>
              <div v-if="submission.bank_account_number" class="p-5 bg-[#FDF8F5] rounded-[1.5rem] border border-[#F3E5D8]">
                <p class="text-[8px] font-black text-[#DEB887] uppercase tracking-widest mb-1">Nomor Rekening</p>
                <p class="text-xs font-black font-mono tracking-widest">{{ submission.bank_account_number }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-[2.5rem] p-8 border-2 border-[#F3E5D8] shadow-sm">
            <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.2em] mb-6 flex items-center gap-3">
              <MapPinIcon class="w-5 h-5" /> Lokasi Banua
            </h3>
            <div class="space-y-2">
              <p class="text-[10px] font-bold text-[#4A3728]">
                {{ submission.village?.name || 'Memuat Desa...' }}, 
                {{ submission.district?.name || 'Memuat Kecamatan...' }}
              </p>
              <p class="text-[9px] text-[#8B5E3C] uppercase tracking-widest">
                {{ submission.regency?.name || 'Kabupaten' }}
              </p>
            </div>
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
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Custom Scrollbar */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #EADDCD; border-radius: 10px; }
</style>