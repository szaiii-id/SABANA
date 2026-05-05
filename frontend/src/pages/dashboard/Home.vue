<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAssistance } from '../../composables/useAssistance';
import { 
  CheckBadgeIcon,
  UserIcon, 
  ExclamationTriangleIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const { fetchMySubmissions, isLoading } = useAssistance();
const submissions = ref<any[]>([]);

// COMPUTED: Pastikan reactive state konsisten di seluruh template
const hasSubmissions = computed(() => submissions.value.length > 0);
const latestSubmission = computed(() => {
  return submissions.value.length > 0 ? submissions.value[0] : null;
});
const submissionCount = computed(() => submissions.value.length);

onMounted(async () => {
  try {
    const response = await fetchMySubmissions();
    submissions.value = response?.data || response || [];
  } catch (error) {
    console.error("Gagal memuat data dashboard:", error);
  }
});
</script>

<template>
  <div class="min-h-screen bg-white p-6 md:p-12 antialiased text-gray-900 animate-fade-in">
    <div class="max-w-6xl mx-auto">
      
      <header class="mb-16">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] mb-6 border border-[#2D6A4F]/20">
          <ShieldCheckIcon class="w-7 h-7" />
        </div>
        <h2 class="text-xs font-[1000] text-[#2D6A4F] uppercase tracking-[0.4em] mb-4">Portal Warga Banua</h2>
        <h1 class="text-6xl md:text-7xl font-[1000] text-gray-900 tracking-tighter mb-6 leading-none">Selamat Datang</h1>
        <p class="text-gray-500 font-medium max-w-lg leading-relaxed text-base">
          Kelola data bantuan dan pantau status pendaftaran Anda melalui sistem keamanan <span class="text-[#2D6A4F] font-black">SABANA</span> yang terintegrasi.
        </p>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <div class="lg:col-span-8">
          <div class="p-10 md:p-14 bg-white border-2 border-gray-100 rounded-[3rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] h-full flex flex-col justify-between group hover:border-[#2D6A4F]/30 transition-all duration-500 relative overflow-hidden">
            
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-16">
                <div class="flex items-center space-x-4">
                  <span class="w-12 h-[2px] bg-[#2D6A4F]"></span>
                  <div class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Status Pengajuan</div>
                </div>
                <!-- PERBAIKAN: Gunakan !isLoading.value untuk unwrap ref -->
                <div v-if="!isLoading" :class="[hasSubmissions ? 'bg-green-500' : 'bg-amber-400']" class="h-3 w-3 rounded-full animate-pulse shadow-lg"></div>
              </div>

              <!-- PERBAIKAN: Gunakan isLoading.value -->
              <div v-if="isLoading" class="space-y-6 animate-pulse">
                <div class="h-16 bg-gray-50 rounded-2xl w-full"></div>
                <div class="h-16 bg-gray-50 rounded-2xl w-2/3"></div>
              </div>

              <!-- PERBAIKAN: Gunakan hasSubmissions (computed) -->
              <div v-else-if="hasSubmissions" class="animate-fade-in">
                <h4 class="text-5xl md:text-6xl font-[1000] text-gray-900 tracking-tight leading-[1.15] mb-10">
                  Terdapat 
                  <span class="text-[#2D6A4F] mx-1">{{ submissionCount }} Berkas</span> 
                  <br/>
                  dalam antrean sistem.
                </h4>
                <!-- PERBAIKAN: Gunakan latestSubmission (computed) -->
                <div v-if="latestSubmission?.program?.name" class="flex items-center gap-3 p-4 rounded-2xl bg-green-50 border border-green-100/50 text-green-800 max-w-fit">
                  <CheckBadgeIcon class="w-5 h-5 shrink-0" />
                  <span class="text-xs font-bold tracking-wide">Terakhir: {{ latestSubmission.program.name }}</span>
                </div>
              </div>

              <div v-else class="animate-fade-in">
                <h3 class="text-5xl md:text-6xl font-[1000] text-gray-200 italic tracking-tighter leading-[1] mb-12">
                  Belum ada <br/>
                  riwayat pengajuan <br/>
                  <span class="text-gray-100">yang tersedia.</span>
                </h3>
              </div>
            </div>
            
            <div class="relative z-10 mt-16">
              <!-- PERBAIKAN: Gunakan router.push, bukan $router.push -->
              <button 
                v-if="hasSubmissions"
                @click="router.push({ name: 'history' })" 
                class="group/btn inline-flex items-center gap-4 px-12 py-5 bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white rounded-xl font-bold text-xs uppercase tracking-widest transition-all hover:scale-105 shadow-lg shadow-[#2D6A4F]/25"
              >
                BUKA RIWAYAT BERKAS
              </button>

              <button 
                v-else
                @click="router.push({ name: 'assistance' })" 
                class="group/btn inline-flex items-center gap-4 px-12 py-5 bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white rounded-xl font-bold text-xs uppercase tracking-widest transition-all hover:scale-105 shadow-lg shadow-[#2D6A4F]/25"
              >
                MULAI DAFTAR BARU
              </button>
            </div>
          </div>
        </div>

        <div class="lg:col-span-4 grid grid-cols-1 gap-6">
          
          <!-- PERBAIKAN: Gunakan router.push, bukan $router.push -->
          <div @click="router.push({ name: 'profile' })" 
               class="p-10 bg-white border border-gray-100 rounded-[2.5rem] cursor-pointer hover:shadow-xl hover:border-[#2D6A4F]/20 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-14 h-14 bg-gray-50 text-[#2D6A4F] rounded-2xl flex items-center justify-center mb-10 group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-500">
              <UserIcon class="w-7 h-7" />
            </div>
            <h4 class="font-[1000] text-gray-900 tracking-tight text-xl leading-none">Data Saya</h4>
            <p class="text-[10px] text-gray-400 font-bold mt-3 uppercase tracking-widest">Identitas & Berkas</p>
          </div>

          <!-- PERBAIKAN: Gunakan router.push, bukan $router.push -->
          <div @click="router.push({ name: 'report' })" 
               class="p-10 bg-gradient-to-br from-[#2D6A4F] to-[#1B4332] rounded-[2.5rem] cursor-pointer hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group relative overflow-hidden text-white">
            
            <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center mb-10 group-hover:bg-red-500 transition-all duration-500">
              <ExclamationTriangleIcon class="w-7 h-7" />
            </div>
            <h4 class="font-[1000] tracking-tight text-xl leading-none">Lapor Warga</h4>
            <p class="text-[10px] text-white/60 font-bold mt-3 uppercase tracking-widest">Aspirasi & Keluhan</p>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>