<template>
  <div class="bg-[#FDF8F4] p-6 sm:p-8 rounded-[2.5rem] border-2 border-[#F3E5D8] space-y-5">
    <div role="radiogroup" aria-label="Metode penyaluran" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <label 
        :class="modelValue === 'village_cash' ? 'border-[#2D6A4F] bg-[#2D6A4F]/5 shadow-sm' : 'border-transparent bg-white'"
        class="border-2 rounded-2xl p-5 flex items-center gap-4 cursor-pointer transition-all hover:border-[#2D6A4F]"
      >
        <input 
          type="radio" 
          value="village_cash" 
          :checked="modelValue === 'village_cash'"
          aria-label="Tunai melalui balai desa"
          @change="$emit('update:modelValue', 'village_cash')"
          class="w-5 h-5 text-[#2D6A4F] accent-[#2D6A4F]"
        />
        <div>
          <span class="font-bold text-[#4A3728]">Tunai</span>
          <p class="text-[10px] text-[#8B5E3C]/60">Ambil di Balai Desa</p>
        </div>
      </label>

      <label 
        :class="modelValue === 'bpd_transfer' ? 'border-[#2D6A4F] bg-[#2D6A4F]/5 shadow-sm' : 'border-transparent bg-white'"
        class="border-2 rounded-2xl p-5 flex items-center gap-4 cursor-pointer transition-all hover:border-[#2D6A4F]"
      >
        <input 
          type="radio" 
          value="bpd_transfer" 
          :checked="modelValue === 'bpd_transfer'"
          aria-label="Transfer melalui Bank BPD"
          @change="$emit('update:modelValue', 'bpd_transfer')"
          class="w-5 h-5 text-[#2D6A4F] accent-[#2D6A4F]"
        />
        <div>
          <span class="font-bold text-[#4A3728]">Transfer</span>
          <p class="text-[10px] text-[#8B5E3C]/60">Via Bank BPD</p>
        </div>
      </label>
    </div>

    <!-- Rekening -->
    <div v-if="modelValue === 'bpd_transfer'" class="space-y-2">
      <label for="bank-account" class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-1">
        Nomor Rekening
      </label>
      <input 
        id="bank-account"
        type="text"
        :value="bankAccountNumber"
        :aria-label="'Nomor rekening BPD'"
        :aria-describedby="bankAccountError ? 'bank-error' : undefined"
        placeholder="Masukkan nomor rekening BPD"
        @input="$emit('update:bankAccountNumber', ($event.target as HTMLInputElement).value); $emit('clearError', 'bank_account_number')"
        class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold font-mono tracking-wider"
      />
      <p v-if="bankAccountError" id="bank-error" role="alert" class="text-[9px] font-bold text-red-500 px-1">{{ bankAccountError }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: 'village_cash' | 'bpd_transfer';
  bankAccountNumber?: string;
  bankAccountError?: string;
}>();

defineEmits<{
  'update:modelValue': [value: 'village_cash' | 'bpd_transfer'];
  'update:bankAccountNumber': [value: string];
  clearError: [key: string];
}>();
</script>