<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-4xl max-h-[85vh] flex overflow-hidden border border-[#E8D5C4]">
        
        <div class="flex flex-col flex-1">
          <div class="flex-shrink-0 px-10 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
            <h3 class="text-xl font-black text-[#1B4332]">{{ mode === 'add' ? 'Tambah Akun Pegawai' : 'Edit Akun Pegawai' }}</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-red-500 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <form @submit.prevent="$emit('save')" class="flex-1 overflow-y-auto p-10 space-y-6">
            <slot></slot>
            <p v-if="error" class="text-sm text-red-600 bg-red-50 p-3 rounded-xl border border-red-200">{{ error }}</p>
          </form>

          <div class="flex-shrink-0 px-10 py-5 border-t border-[#E8D5C4] bg-gray-50 flex justify-end gap-3">
            <button type="button" @click="$emit('close')" class="px-6 py-3 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
            <button @click="$emit('save')" :disabled="submitting" class="px-6 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-70">
              <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submitting ? 'Menyimpan...' : 'Simpan Akun' }}
            </button>
          </div>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{ open: boolean; mode: 'add' | 'edit'; submitting: boolean; error: string }>();
defineEmits<{ close: []; save: [] }>();
</script>