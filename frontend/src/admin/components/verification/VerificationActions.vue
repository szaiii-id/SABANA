<template>
  <div>
    <div class="space-y-3">
      
      <div>
        <button 
          v-if="!showRejectForm && !showRevisionForm"
          @click="$emit('approve')"
          :disabled="disabled || submitting"
          class="w-full px-6 py-4 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-emerald-200/50 hover:shadow-xl hover:shadow-emerald-300/50 active:scale-[0.98]"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
          {{ submitting ? 'Memproses...' : 'Setujui Pengajuan' }}
        </button>
        <p v-if="disabled && !showRejectForm && !showRevisionForm" class="text-[11px] text-amber-600 font-semibold text-center mt-2 bg-amber-50 py-2 rounded-xl border border-amber-100">
          Periksa semua dokumen terlebih dahulu
        </p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <button 
          v-if="!showRejectForm && !showRevisionForm"
          @click="openReject"
          :disabled="submitting"
          class="px-5 py-3.5 rounded-2xl text-sm font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 hover:border-rose-300 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 active:scale-[0.98]"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          Tolak
        </button>

        <button 
          v-if="!showRejectForm && !showRevisionForm"
          @click="openRevision"
          :disabled="submitting"
          class="px-5 py-3.5 rounded-2xl text-sm font-bold text-blue-600 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 active:scale-[0.98]"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
          Revisi
        </button>
      </div>

      <button 
        v-if="showRejectForm"
        @click="submitReject"
        :disabled="submitting || !rejectNotes.trim()"
        class="w-full px-6 py-4 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-700 hover:to-rose-600 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-rose-200/50 active:scale-[0.98]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        {{ submitting ? 'Memproses...' : 'Konfirmasi Tolak' }}
      </button>

      <button 
        v-if="showRevisionForm"
        @click="submitRevision"
        :disabled="submitting || !revisionNotes.trim()"
        class="w-full px-6 py-4 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-blue-200/50 active:scale-[0.98]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        {{ submitting ? 'Memproses...' : 'Kirim Perbaikan' }}
      </button>
    </div>

    <!-- Revision Form -->
    <Transition name="slide-down">
      <div v-if="showRevisionForm" class="mt-4 p-5 bg-blue-50 rounded-2xl border border-blue-200 space-y-4">
        <div class="flex items-center gap-2 pb-2 border-b border-blue-100">
          <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
          <h4 class="text-xs font-black text-blue-700 uppercase tracking-wider">Minta Perbaikan</h4>
        </div>

        <div v-if="revisionItems.length" class="bg-white rounded-xl border border-blue-200 p-3">
          <p class="text-[10px] font-bold text-[#6B705C] uppercase tracking-wider mb-3">Item yang perlu diperbaiki:</p>
          <div class="flex flex-wrap gap-2">
            <button 
              v-for="item in revisionItems" :key="item.key"
              @click="toggleRevisionItem(item.key)"
              :class="[
                'px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all',
                isItemSelected(item.key)
                  ? 'bg-blue-500 text-white shadow-sm'
                  : 'bg-white text-[#6B705C] border border-gray-200 hover:border-blue-300 hover:text-blue-600'
              ]"
            >
              {{ item.label }}
            </button>
          </div>
          <p class="text-[9px] text-[#6B705C] mt-2">{{ selectedItems.length }} item dipilih</p>
        </div>

        <textarea 
          v-model="revisionNotes"
          rows="3"
          class="w-full px-4 py-3 rounded-xl border border-blue-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none resize-none bg-white"
          placeholder="Tulis catatan perbaikan untuk warga..."
        ></textarea>
        
        <div class="flex items-center justify-between pt-1">
          <p class="text-[10px] text-[#6B705C]">{{ revisionNotes.length }}/500 karakter (min. 10)</p>
          <button @click="cancelForm" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 transition-colors">Batal</button>
        </div>
      </div>
    </Transition>

    <!-- Reject Form -->
    <Transition name="slide-down">
      <div v-if="showRejectForm" class="mt-4 p-5 bg-red-50 rounded-2xl border border-red-200 space-y-4">
        <div class="flex items-center gap-2 pb-2 border-b border-red-100">
          <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          <h4 class="text-xs font-black text-red-700 uppercase tracking-wider">Tolak Pengajuan</h4>
        </div>
        <textarea 
          v-model="rejectNotes"
          rows="3"
          class="w-full px-4 py-3 rounded-xl border border-red-200 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/10 outline-none resize-none bg-white"
          placeholder="Tulis alasan penolakan..."
        ></textarea>
        <div class="flex items-center justify-between pt-1">
          <p class="text-[10px] text-[#6B705C]">{{ rejectNotes.length }}/500 karakter (min. 10)</p>
          <button @click="cancelForm" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 transition-colors">Batal</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
  disabled: boolean;
  submitting: boolean;
  documents?: Array<{ key: string; label: string }>;
  inputs?: Array<{ key: string; label: string }>;
}>();

const emit = defineEmits<{
  approve: [];
  reject: [notes: string];
  revision: [notes: string, revisionItems: string[]];
}>();

const showRejectForm = ref(false);
const showRevisionForm = ref(false);
const rejectNotes = ref('');
const revisionNotes = ref('');
const selectedItems = ref<string[]>([]);

const revisionItems = computed(() => [
  ...(props.documents || []).map(d => ({ key: d.key, label: `${d.label}` })),
  ...(props.inputs || []).map(i => ({ key: i.key, label: `✏️ ${i.label}` })),
]);

const openReject = () => {
  showRevisionForm.value = false;
  showRejectForm.value = true;
  rejectNotes.value = '';
};

const openRevision = () => {
  showRejectForm.value = false;
  showRevisionForm.value = true;
  revisionNotes.value = '';
  selectedItems.value = [];
};

const toggleRevisionItem = (key: string) => {
  const idx = selectedItems.value.indexOf(key);
  idx === -1 ? selectedItems.value.push(key) : selectedItems.value.splice(idx, 1);
};

const isItemSelected = (key: string) => selectedItems.value.includes(key);

const submitReject = () => {
  if (rejectNotes.value.trim().length < 10) return;
  emit('reject', rejectNotes.value.trim());
  showRejectForm.value = false;
  rejectNotes.value = '';
};

const submitRevision = () => {
  if (revisionNotes.value.trim().length < 10) return;
  emit('revision', revisionNotes.value.trim(), [...selectedItems.value]);
  showRevisionForm.value = false;
  revisionNotes.value = '';
  selectedItems.value = [];
};

const cancelForm = () => {
  showRejectForm.value = false;
  showRevisionForm.value = false;
  rejectNotes.value = '';
  revisionNotes.value = '';
  selectedItems.value = [];
};
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>