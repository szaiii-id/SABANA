<template>
  <div class="bg-white p-4 rounded-3xl border border-[#E8D5C4] shadow-sm">
    <div class="relative">
      
      <!-- Search Icon / Loading Spinner -->
      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
        <!-- Spinner saat searching -->
        <svg 
          v-if="isSearching"
          class="animate-spin h-5 w-5 text-[#1B4332]" 
          fill="none" 
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <!-- Default search icon -->
        <svg 
          v-else
          class="h-5 w-5 text-gray-400" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <!-- Search Input -->
      <input 
        :value="modelValue.search"
        @input="onSearchInput"
        type="text" 
        placeholder="Cari NIK atau Nama..." 
        class="w-full pl-11 pr-10 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors"
        autocomplete="off"
      />

      <!-- Clear Button (muncul hanya saat ada text) -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform scale-75 opacity-0"
        enter-to-class="transform scale-100 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform scale-100 opacity-100"
        leave-to-class="transform scale-75 opacity-0"
      >
        <button 
          v-if="modelValue.search"
          @click="clearSearch"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
          type="button"
          aria-label="Hapus pencarian"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </Transition>

    </div>
  </div>
</template>

<script setup lang="ts">
// ===== PROPS & EMITS =====

defineProps<{ 
  modelValue: { search: string };
  isSearching?: boolean; // ✅ Tambahan: loading state dari parent
}>();

const emit = defineEmits<{ 
  'update:modelValue': [value: { search: string }];
}>();

// ===== DEBOUNCE TIMER =====

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = (event: Event): void => {
  const value = (event.target as HTMLInputElement).value;
  
  // Clear previous timer
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  
  // Debounce 300ms untuk menghindari request berlebihan
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', { search: value });
  }, 300);
};

// ===== CLEAR SEARCH =====

const clearSearch = (): void => {
  // Clear pending debounce
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  
  // Emit kosong langsung (tanpa debounce)
  emit('update:modelValue', { search: '' });
};
</script>