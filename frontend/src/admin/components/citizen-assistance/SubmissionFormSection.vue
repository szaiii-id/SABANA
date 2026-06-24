<template>
  <div class="bg-white rounded-[2rem] p-6 shadow-sm ring-1 ring-slate-200/60 relative">
    
    <!-- Loading Overlay -->
    <div v-if="isSubmitting" class="absolute inset-0 bg-white/90 backdrop-blur-sm z-50 flex flex-col items-center justify-center rounded-[2rem]">
      <div class="flex flex-col items-center animate-pulse">
        <div class="relative w-20 h-20 mb-5">
          <div class="absolute inset-0 border-4 border-slate-200 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-[#1B4332] rounded-full border-t-transparent animate-spin"></div>
        </div>
        <h3 class="text-lg font-black text-slate-800 mb-1">MENGUNGGAH BERKAS...</h3>
        <p class="text-sm text-slate-500">Server sedang memproses data. Jangan tutup halaman.</p>
      </div>
    </div>

    <!-- Notifikasi Error -->
    <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3">
      <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm font-bold text-red-700">{{ error }}</p>
    </div>

    <!-- Step Indicator -->
    <div class="flex items-center gap-2 mb-8">
      <div :class="['flex items-center gap-2', currentStep >= 2 ? 'text-[#1B4332]' : 'text-slate-300']">
        <div :class="['w-8 h-8 rounded-xl flex items-center justify-center text-xs font-black', currentStep >= 2 ? 'bg-[#1B4332] text-white' : 'bg-slate-100 text-slate-400']">1</div>
        <span class="text-xs font-bold hidden sm:inline">Data</span>
      </div>
      <div :class="['flex-1 h-1 rounded-full', currentStep >= 3 ? 'bg-[#1B4332]' : 'bg-slate-200']"></div>
      <div :class="['flex items-center gap-2', currentStep >= 3 ? 'text-[#1B4332]' : 'text-slate-300']">
        <div :class="['w-8 h-8 rounded-xl flex items-center justify-center text-xs font-black', currentStep >= 3 ? 'bg-[#1B4332] text-white' : 'bg-slate-100 text-slate-400']">2</div>
        <span class="text-xs font-bold hidden sm:inline">Berkas</span>
      </div>
    </div>

    <!-- Form Steps -->
    <AssistanceStep2 
      v-if="currentStep === 2"
      :formData="formData" 
      :selectedProgram="selectedProgram" 
      :regencies="regencies" 
      :districts="districts" 
      :villages="villages"
      @regencyChange="$emit('regencyChange')"
      @districtChange="$emit('districtChange')"
      @prev="$emit('cancel')"
      @next="currentStep = 3"
      @errorMsg="$emit('update:error', $event)"
    />
    
    <AssistanceStep3 
      v-if="currentStep === 3"
      :formData="formData" 
      :selectedProgram="selectedProgram" 
      :isLoading="isSubmitting"
      @prev="currentStep = 2"
      @submit="$emit('submit')"
      @errorMsg="$emit('update:error', $event)"
    />

    <!-- Success State -->
    <div v-if="success" class="text-center py-8">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h3 class="text-xl font-black text-[#1B4332] mb-2">{{ success }}</h3>
      <p class="text-sm text-slate-500 mb-6">{{ registrationNumber }}</p>
      <button @click="$emit('reset')" class="px-6 py-3 bg-[#1B4332] text-white font-bold rounded-xl hover:bg-[#2D6A4F] transition-all">
        Ajukan Lagi
      </button>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import AssistanceStep2 from '../../../components/assistance/AssistanceStep2.vue';
import AssistanceStep3 from '../../../components/assistance/AssistanceStep3.vue';
import type { AssistanceSubmissionPayload, AssistanceProgramSchema } from '../../../types/assistance';
import type { Region } from '../../composables/useRegion';

const props = defineProps<{
  formData: AssistanceSubmissionPayload;
  selectedProgram: AssistanceProgramSchema;
  regencies: Region[];
  districts: Region[];
  villages: Region[];
  isSubmitting: boolean;
  error: string;
  success: string;
  registrationNumber: string;
}>();

defineEmits<{
  submit: [];
  regencyChange: [];
  districtChange: [];
  cancel: [];
  reset: [];
  'update:error': [msg: string];
}>();

const currentStep = ref(2);

watch(() => props.selectedProgram, () => {
  currentStep.value = 2;
});
</script>