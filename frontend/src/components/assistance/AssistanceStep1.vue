<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import type { AssistanceProgramSchema } from '../../types/assistance';
import AlreadySubmittedModal from '../common/AlreadySubmittedModal.vue';

defineProps<{ programs: AssistanceProgramSchema[] }>();
const emit = defineEmits<{
  (e: 'select', program: AssistanceProgramSchema): void;
  (e: 'refresh'): void;
}>();

const router = useRouter();
const showFull = reactive<Record<string, boolean>>({});
const showAlreadySubmittedModal = ref(false);
const submittedProgramTitle = ref('');

const toggleDescription = (id: string) => {
  showFull[id] = !showFull[id];
};

const handleCardClick = (item: AssistanceProgramSchema) => {
  if (item.has_submitted) {
    submittedProgramTitle.value = item.title;
    showAlreadySubmittedModal.value = true;
  } else {
    emit('select', item);
  }
};

const formatDate = (date: string | null) => {
  if (!date) return null;
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatCurrency = (amount: number | null) => {
  if (!amount) return null;
  return new Intl.NumberFormat('id-ID').format(amount);
};
</script>

<template>
  <div class="animate-fade-in">
    
    <div class="mb-10 text-center">
      <p class="text-[#2D6A4F] font-black text-[9px] uppercase tracking-[0.4em] mb-2">Program Bantuan Aktif</p>
      <h3 class="text-4xl font-black text-[#1B4332] tracking-tight leading-none mb-2">Pilih Bantuan</h3>
      <p class="text-[#8B5E3C]/50 text-[11px] font-medium max-w-md mx-auto leading-relaxed">
        Temukan program yang sesuai dengan kondisi Anda. Data dijamin aman.
      </p>
    </div>

    <div v-if="programs.length === 0" class="flex flex-col items-center py-20 px-8 text-center">
      <div class="w-20 h-20 bg-[#FDF8F1] rounded-full flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-[#D4A373]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <p class="text-[#4A3728] font-bold text-lg mb-1">Belum Ada Program</p>
      <p class="text-[#8B5E3C]/50 text-xs mb-6">Admin sedang menyiapkan program bantuan untuk Anda.</p>
      <button 
        @click="$emit('refresh')" 
        class="px-6 py-3 bg-[#2D6A4F] text-white text-xs font-bold rounded-xl hover:bg-[#1B4332] transition-all"
      >
        Muat Ulang
      </button>
    </div>

    <div v-else class="space-y-4">
      
      <div 
        v-for="item in programs" 
        :key="item.id" 
        @click="handleCardClick(item)"
        :class="[
          'group bg-white border-2 rounded-3xl p-5 transition-all duration-300 flex flex-col sm:flex-row gap-5 items-start',
          item.has_submitted 
            ? 'border-[#F3E5D8] opacity-75 cursor-default' 
            : 'border-[#F3E5D8] cursor-pointer hover:border-[#2D6A4F] hover:shadow-xl hover:shadow-[#2D6A4F]/5'
        ]"
      >
        
        <div class="relative w-full sm:w-48 h-36 sm:h-32 flex-shrink-0 rounded-2xl overflow-hidden bg-gradient-to-br from-[#1B4332]/5 to-[#D4A373]/10">
          <img 
            v-if="item.banner_url" 
            :src="item.banner_url" 
            :alt="item.title"
            class="w-full h-full object-cover" 
          />
          <div v-else class="w-full h-full flex items-center justify-center text-[#D4A373]/30">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          
          <div class="absolute top-2.5 left-2.5">
            <span :class="[
              'px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider shadow-sm',
              item.has_submitted 
                ? 'bg-amber-100/90 backdrop-blur-sm text-amber-700' 
                : 'bg-white/90 backdrop-blur-sm text-[#2D6A4F]'
            ]">
              {{ item.has_submitted ? 'Sudah Terdaftar' : item.badge }}
            </span>
          </div>
        </div>

        <div class="flex-1 min-w-0 flex flex-col justify-between self-stretch">
          
          <div>
            <h4 class="text-xl font-black text-[#1B4332] tracking-tight leading-tight">
              {{ item.title }}
            </h4>
            
            <p 
              class="text-[13px] text-[#6B705C] leading-relaxed mt-1.5"
              :class="showFull[item.id] ? '' : 'line-clamp-2'"
            >
              {{ item.description }}
            </p>
            <button 
              v-if="item.description && item.description.length > 100"
              @click.stop="toggleDescription(item.id)"
              class="text-[10px] font-bold text-[#D4A373] hover:text-[#2D6A4F] transition-colors mt-0.5"
            >
              {{ showFull[item.id] ? '▲ Sembunyikan' : '▼ Selengkapnya' }}
            </button>
          </div>

          <div class="mt-3">
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              
              <div v-if="item.start_date" class="flex items-center gap-2 p-2 bg-[#FDF8F1] rounded-xl">
                <div class="w-7 h-7 rounded-lg bg-[#2D6A4F]/10 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-[7px] font-black text-[#8B5E3C] uppercase tracking-wider">Periode</p>
                  <p class="text-[10px] font-bold text-[#4A3728] truncate">{{ formatDate(item.start_date) }} - {{ formatDate(item.end_date) }}</p>
                </div>
              </div>

              <div v-if="item.quota_total" class="flex items-center gap-2 p-2 bg-[#FDF8F1] rounded-xl">
                <div class="w-7 h-7 rounded-lg bg-[#2D6A4F]/10 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-[7px] font-black text-[#8B5E3C] uppercase tracking-wider">Kuota</p>
                  <p class="text-[10px] font-bold text-[#4A3728] truncate">{{ formatCurrency(item.quota_total) }}</p>
                </div>
              </div>

              <div v-if="item.benefit_amount" class="flex items-center gap-2 p-2 bg-[#FDF8F1] rounded-xl">
                <div class="w-7 h-7 rounded-lg bg-[#2D6A4F]/10 flex items-center justify-center flex-shrink-0">
                  <svg class="w-3 h-3 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-[7px] font-black text-[#8B5E3C] uppercase tracking-wider">Bantuan</p>
                  <p class="text-[10px] font-bold text-[#D4A373] truncate">Rp {{ formatCurrency(item.benefit_amount) }}</p>
                </div>
              </div>

            </div>

            <div class="flex justify-end mt-3 pt-2 border-t border-[#F3E5D8]">
              <span v-if="item.has_submitted" class="text-amber-600 font-bold text-[11px]">
                Lihat di Riwayat
              </span>
              <span v-else class="text-[#2D6A4F] font-bold text-[11px] group-hover:tracking-wider transition-all">
                Daftar Sekarang
              </span>
            </div>

          </div>

        </div>

      </div>

    </div>

    <AlreadySubmittedModal
      :open="showAlreadySubmittedModal"
      :program-title="submittedProgramTitle"
      @close="showAlreadySubmittedModal = false"
      @go-history="showAlreadySubmittedModal = false; router.push({ name: 'history' })"
    />
  </div>
</template>

<style scoped>
.animate-fade-in { 
  animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
}
@keyframes fadeIn { 
  from { opacity: 0; transform: translateY(12px); } 
  to { opacity: 1; transform: translateY(0); } 
}
</style>