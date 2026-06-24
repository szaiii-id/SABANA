<template>
  <transition name="modal">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-[#4A3728]/30 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full text-center shadow-2xl relative z-10 border border-[#F3E5D8]">
        <div :class="[
          'w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5',
          variant === 'danger' ? 'bg-red-50 text-red-500' : 'bg-amber-100 text-amber-600'
        ]">
          <ExclamationCircleIcon class="w-10 h-10" />
        </div>
        <h3 class="text-xl font-black mb-2 tracking-tight">{{ title }}</h3>
        <p class="text-[#8B5E3C] text-xs mb-8 leading-relaxed font-medium">{{ message }}</p>
        <div class="grid grid-cols-2 gap-3">
          <button @click="$emit('close')" class="py-3 px-4 bg-stone-100 text-[#8B5E3C] font-black uppercase tracking-widest text-[9px] rounded-xl hover:bg-stone-200 transition-colors">
            {{ cancelText }}
          </button>
          <button @click="$emit('confirm')" :class="[
            'py-3 px-4 text-white font-black uppercase tracking-widest text-[9px] rounded-xl shadow-lg transition-all flex items-center justify-center gap-2',
            variant === 'danger' ? 'bg-red-500 hover:bg-red-600 shadow-red-200' : 'bg-amber-500 hover:bg-amber-600 shadow-amber-200'
          ]">
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline';

defineProps<{
  open: boolean;
  title: string;
  message: string;
  variant?: 'danger' | 'warning';
  confirmText?: string;
  cancelText?: string;
}>();

defineEmits<{
  close: [];
  confirm: [];
}>();
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>