<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <div>
      <label for="program-name" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Nama Program</label>
      <div class="relative group">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg class="h-5 w-5 transition-colors" :class="errors.name ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <input 
          id="program-name"
          v-model="form.name" 
          type="text" 
          placeholder="Nama program"
          aria-label="Nama program"
          :aria-invalid="!!errors.name"
          :aria-describedby="errors.name ? 'error-name' : undefined"
          :class="['w-full pl-12 pr-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.name ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
          @input="errors.name = ''"
        >
      </div>
      <p v-if="errors.name" id="error-name" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.name }}</p>
    </div>

    <div>
      <label for="program-status" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Status</label>
      <select 
        id="program-status"
        v-model="form.status" 
        aria-label="Status program"
        class="w-full pl-4 pr-10 py-3.5 rounded-xl border-2 border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10 outline-none transition-all font-medium text-sm appearance-none bg-white"
      >
        <option value="draft">Draft</option>
        <option value="active">Aktif</option>
      </select>
    </div>

    <div>
      <label for="program-quota" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Kuota Total</label>
      <input 
        id="program-quota"
        v-model.number="form.quota_total" 
        type="number" 
        placeholder="Jumlah maksimal"
        aria-label="Kuota total penerima"
        min="1"
        :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.quota_total ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
        @input="errors.quota_total = ''"
      >
      <p v-if="errors.quota_total" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.quota_total }}</p>
    </div>
  </div>

  <div class="md:col-span-3">
    <label for="program-description" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Deskripsi</label>
    <textarea 
      id="program-description"
      v-model="form.description" 
      rows="2" 
      placeholder="Deskripsi program..."
      aria-label="Deskripsi program"
      :aria-invalid="!!errors.description"
      :aria-describedby="errors.description ? 'error-description' : undefined"
      :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm resize-none', errors.description ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']" 
      @input="errors.description = ''"
    ></textarea>
    <p v-if="errors.description" id="error-description" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.description }}</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <div>
      <label for="program-start-date" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Tanggal Mulai</label>
      <input 
        id="program-start-date"
        v-model="form.start_date" 
        type="date"
        aria-label="Tanggal mulai program"
        :aria-invalid="!!errors.start_date"
        :aria-describedby="errors.start_date ? 'error-start-date' : undefined"
        :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.start_date ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']" 
        @change="errors.start_date = ''"
      >
      <p v-if="errors.start_date" id="error-start-date" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.start_date }}</p>
    </div>

    <div>
      <label for="program-end-date" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Tanggal Selesai</label>
      <input 
        id="program-end-date"
        v-model="form.end_date" 
        type="date"
        aria-label="Tanggal selesai program"
        :aria-invalid="!!errors.end_date"
        :aria-describedby="errors.end_date ? 'error-end-date' : undefined"
        :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.end_date ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']" 
        @change="errors.end_date = ''"
      >
      <p v-if="errors.end_date" id="error-end-date" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.end_date }}</p>
    </div>

    <div>
      <label for="program-benefit" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Nilai Bantuan (Rp)</label>
      <input 
        id="program-benefit"
        v-model.number="form.benefit_amount" 
        type="number" 
        placeholder="Nilai per penerima"
        aria-label="Nilai bantuan per penerima"
        min="0"
        :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.benefit_amount ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
        @input="errors.benefit_amount = ''"
      >
      <p v-if="errors.benefit_amount" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.benefit_amount }}</p>
    </div>
  </div>

  <div>
    <label for="program-banner" class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Banner Program</label>
    
    <div v-if="bannerPreview" class="relative mb-3">
      <img :src="bannerPreview" alt="Preview banner program" class="h-32 w-full object-cover rounded-xl border border-[#E8D5C4]" />
      <button 
        @click="removeBanner" 
        aria-label="Hapus banner"
        class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors shadow-lg"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>
    
    <div v-else-if="mode === 'edit' && existingBannerUrl" class="relative mb-3">
      <img :src="existingBannerUrl" alt="Banner program saat ini" class="h-32 w-full object-cover rounded-xl border border-[#E8D5C4]" />
      <span class="absolute bottom-2 left-2 text-[10px] font-bold text-white bg-black/50 px-2 py-1 rounded-lg">Banner Saat Ini</span>
    </div>
    
    <input 
      id="program-banner"
      type="file" 
      accept="image/*"
      aria-label="Upload banner program"
      @change="handleBannerChange"
      class="w-full text-sm text-[#6B705C] file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#FAF6F0] file:text-[#1B4332] hover:file:bg-[#1B4332] hover:file:text-white file:transition-colors file:cursor-pointer"
    >
    <p v-if="bannerError" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ bannerError }}</p>
    <p v-else-if="errors.banner" role="alert" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.banner }}</p>
    <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">Format JPG/PNG, maksimal 2MB</p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import type { ProgramPayload } from '../../types/program';

const MAX_BANNER_SIZE = 2 * 1024 * 1024;

defineProps<{ 
  form: ProgramPayload;
  mode: 'add' | 'edit'; 
  errors: Record<string, string>;
  existingBannerUrl?: string | null;
}>();

const emit = defineEmits<{ bannerChange: [file: File | null] }>();

const bannerPreview = ref<string | null>(null);
const bannerError = ref('');

watch(bannerPreview, (newUrl, oldUrl) => {
  if (oldUrl) URL.revokeObjectURL(oldUrl);
});

const handleBannerChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  
  bannerError.value = '';
  
  if (file) {
    if (file.size > MAX_BANNER_SIZE) {
      target.value = '';
      bannerError.value = 'Ukuran banner maksimal 2MB.';
      emit('bannerChange', null);
      return;
    }
    bannerPreview.value = URL.createObjectURL(file);
    emit('bannerChange', file);
  } else {
    removeBanner();
  }
};

const removeBanner = () => {
  bannerPreview.value = null;
  bannerError.value = '';
  const fileInput = document.getElementById('program-banner') as HTMLInputElement;
  if (fileInput) fileInput.value = '';
  emit('bannerChange', null);
};

onUnmounted(() => {
  if (bannerPreview.value) {
    URL.revokeObjectURL(bannerPreview.value);
    bannerPreview.value = null;
  }
});
</script>