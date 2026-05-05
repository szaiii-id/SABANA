<script setup lang="ts">
import { reactive, watch } from 'vue';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload } from '../../types/assistance';
import { CloudArrowUpIcon, PhotoIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  formData: AssistanceSubmissionPayload;
  selectedProgram: AssistanceProgramSchema;
  isLoading: boolean;
  existingEvidences?: Record<string, string>; 
}>();

const emit = defineEmits(['prev', 'submit', 'errorMsg']);

const previewUrls = reactive<Record<string, string>>({});
const fieldErrors = reactive<Record<string, string>>({});

// Sinkronisasi dinamis jika existingEvidences berubah (dari API)
watch(() => props.existingEvidences, (newVal) => {
  if (newVal) {
    Object.assign(previewUrls, newVal);
  }
}, { immediate: true, deep: true });

const handleFileUpload = (event: Event, key: string) => {
  const target = event.target as HTMLInputElement;
  fieldErrors[key] = ''; 

  if (target.files && target.files[0]) {
    const file = target.files[0];
    if (file.size > 2 * 1024 * 1024) {
      fieldErrors[key] = "Maksimal ukuran file 2MB.";
      target.value = ""; return;
    }
    
    props.formData.files[key] = file;
    
    // Revoke URL lama untuk memori
    if (previewUrls[key] && previewUrls[key].startsWith('blob:')) {
      URL.revokeObjectURL(previewUrls[key]); 
    }
    previewUrls[key] = URL.createObjectURL(file);
  }
};

const validateAndSubmit = () => {
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
  let isValid = true;

  props.selectedProgram.files.forEach(fileReq => {
    const isOptional = fileReq.label.toLowerCase().includes('(opsional)');
    const hasExistingFile = previewUrls[fileReq.key]; // Cek preview (baik dari API atau file baru)

    if (!isOptional && !hasExistingFile) {
      fieldErrors[fileReq.key] = `${fileReq.label} wajib diunggah`;
      isValid = false;
    }
  });

  if (isValid) {
    emit('submit');
  } else {
    emit('errorMsg', 'Mohon lengkapi berkas wajib sebelum mengirim.');
  }
};
</script>

<template>
  <div class="animate-fade-in">
    <div class="mb-10 text-center md:text-left flex items-center gap-4">
      <div class="w-12 h-12 bg-[#F3E5D8] rounded-2xl flex items-center justify-center text-[#2D6A4F]">
        <PhotoIcon class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-2xl font-black text-[#4A3728] tracking-tight leading-none uppercase">Verifikasi Berkas</h3>
        <p class="text-[#2D6A4F] text-[10px] font-black uppercase tracking-[0.2em] mt-1 italic">Langkah 3: Unggah Bukti Digital</p>
      </div>
    </div>

    <form @submit.prevent="validateAndSubmit" class="space-y-8" novalidate>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div v-for="fileReq in selectedProgram.files" :key="fileReq.key" class="space-y-3">
          <div class="flex justify-between items-center px-4">
            <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-widest">
              {{ fileReq.label }}
            </label>
            <span v-if="fileReq.label.toLowerCase().includes('(opsional)')" class="text-[8px] font-black text-[#DEB887] uppercase tracking-widest bg-[#Fdf8f1] px-2 py-0.5 rounded border border-[#F3E5D8]">
              Boleh Kosong
            </span>
          </div>
          
          <div :class="['relative h-72 border-4 border-dashed rounded-[3.5rem] flex flex-col items-center justify-center overflow-hidden transition-all duration-500', 
            previewUrls[fileReq.key] ? 'border-[#2D6A4F] bg-white shadow-xl shadow-green-900/5' : (fieldErrors[fileReq.key] ? 'border-red-300 bg-red-50' : 'border-[#F3E5D8] bg-[#FEF9F3] hover:bg-white hover:border-[#2D6A4F]')]">
            
            <input type="file" accept="image/*" @change="(e) => handleFileUpload(e, fileReq.key)" class="absolute inset-0 opacity-0 z-30 cursor-pointer" />

            <div v-if="previewUrls[fileReq.key]" class="absolute inset-0 w-full h-full p-4">
                <img :src="previewUrls[fileReq.key]" loading="lazy" alt="Preview" class="w-full h-full object-cover rounded-[2.5rem]" />
                <div class="absolute inset-0 bg-[#2D6A4F]/80 backdrop-blur-sm opacity-0 hover:opacity-100 flex flex-col items-center justify-center transition-all duration-500 rounded-[2.5rem]">
                    <CheckCircleIcon class="w-10 h-10 text-white mb-1" />
                    <p class="text-white font-black text-[10px] uppercase tracking-widest">Ganti Berkas</p>
                </div>
            </div>
            
            <div v-else class="text-center">
              <div class="w-20 h-20 bg-white rounded-[1.8rem] flex items-center justify-center mx-auto mb-4 shadow-sm text-[#F3E5D8]">
                <CloudArrowUpIcon class="w-10 h-10" />
              </div>
              <p class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-widest">Pilih Gambar</p>
              <p class="text-[8px] text-[#DEB887] font-bold mt-2 tracking-widest uppercase">Maks. 2MB</p>
            </div>
          </div>
          
          <p v-if="fieldErrors[fileReq.key]" class="text-[10px] font-black text-red-500 text-center mt-3 tracking-widest uppercase italic animate-pulse">
            ⚠ {{ fieldErrors[fileReq.key] }}
          </p>
        </div>
      </div>

      <div class="flex items-center justify-between pt-12 border-t-2 border-[#F3E5D8]">
        <button type="button" @click="emit('prev')" :disabled="isLoading" class="text-[#8B5E3C] font-black text-[10px] tracking-widest uppercase hover:text-[#2D6A4F]">Kembali</button>
        <button type="submit" :disabled="isLoading" 
          class="px-16 py-6 bg-[#2D6A4F] text-white font-black text-[11px] uppercase tracking-[0.4em] rounded-[2.5rem] shadow-2xl transition-all hover:scale-105 active:scale-95 flex items-center gap-4">
          <div v-if="isLoading" class="w-4 h-4 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
          {{ isLoading ? 'MENGIRIM...' : 'KIRIM PENGAJUAN' }}
        </button>
      </div>
    </form>
  </div>
</template>