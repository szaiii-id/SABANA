<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="submitting ? null : $emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden border border-[#E8D5C4]">
        
        <div class="px-10 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
          <div>
            <h3 class="text-xl font-black text-[#1B4332]">Edit Data Warga</h3>
            <p class="text-[10px] text-[#6B705C] font-medium mt-0.5">Perbarui informasi warga yang sudah terdaftar</p>
          </div>
          <button @click="$emit('close')" :disabled="submitting" class="text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-10 space-y-5">
          <div class="grid grid-cols-1 gap-5">
            <div>
              <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">NIK (16 Digit)</label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 transition-colors" :class="errors.nik ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4z" />
                  </svg>
                </div>
                <input 
                  v-model="form.nik" 
                  type="text" 
                  inputmode="numeric" 
                  maxlength="16"
                  placeholder="6312345678901234"
                  :class="['w-full pl-12 pr-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.nik ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] bg-white focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                  @input="validateNik"
                >
              </div>
              <p v-if="errors.nik" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.nik }}</p>
              <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">NIK dapat diubah jika terjadi kesalahan input</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Nama Lengkap</label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 transition-colors" :class="errors.full_name ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <input 
                  v-model="form.full_name" 
                  type="text"
                  placeholder="Nama lengkap warga"
                  :class="['w-full pl-12 pr-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm bg-white', errors.full_name ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                  @input="validateName"
                >
              </div>
              <p v-if="errors.full_name" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.full_name }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">No. KK (16 Digit)</label>
                <input 
                  v-model="form.family_card_number" 
                  type="text" 
                  inputmode="numeric" 
                  maxlength="16"
                  placeholder="1111111111111111"
                  :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm bg-white', errors.family_card_number ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                  @input="validateKK"
                >
                <p v-if="errors.family_card_number" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.family_card_number }}</p>
              </div>
              <div>
                <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">No. WhatsApp</label>
                <input 
                  v-model="form.whatsapp_number" 
                  type="text" 
                  inputmode="numeric"
                  placeholder="082158935167"
                  :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm bg-white', errors.whatsapp_number ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                  @input="validateWA"
                >
                <p v-if="errors.whatsapp_number" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.whatsapp_number }}</p>
                <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">Opsional, untuk kirim PIN akses</p>
              </div>
            </div>
          </div>

          <p v-if="error" class="text-sm text-red-600 bg-red-50 p-3 rounded-xl border border-red-200">{{ error }}</p>

          <div class="flex gap-3 pt-4 border-t border-[#E8D5C4]">
            <button type="button" @click="$emit('close')" class="flex-1 px-5 py-3.5 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
            <button type="submit" :disabled="submitting" class="flex-1 px-5 py-3.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70 shadow-sm shadow-[#1B4332]/20">
              <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import type { RegisteredCitizen } from '../../types/registration-citizen';

const props = defineProps<{
  open: boolean;
  submitting: boolean;
  error: string;
  citizen: RegisteredCitizen | null;
}>();

const emit = defineEmits<{
  close: [];
  submit: [data: { nik: string; full_name: string; family_card_number: string; whatsapp_number: string }];
}>();

const form = reactive({
  nik: '',
  full_name: '',
  family_card_number: '',
  whatsapp_number: '',
});

const errors = reactive({
  nik: '',
  full_name: '',
  family_card_number: '',
  whatsapp_number: '',
});

watch(() => props.open, (isOpen) => {
  if (isOpen && props.citizen) {
    form.nik = props.citizen.nik || '';
    form.full_name = props.citizen.full_name || '';
    form.family_card_number = props.citizen.family_card_number || '';
    form.whatsapp_number = props.citizen.whatsapp_number || '';
    errors.nik = '';
    errors.full_name = '';
    errors.family_card_number = '';
    errors.whatsapp_number = '';
  }
});

const validateNik = (): void => {
  errors.nik = '';
  const val = form.nik.replace(/\D/g, '');
  form.nik = val;
  if (!val) errors.nik = 'NIK wajib diisi.';
  else if (val.length !== 16) errors.nik = 'NIK harus 16 digit.';
};

const validateName = (): void => {
  errors.full_name = '';
  if (!form.full_name.trim()) errors.full_name = 'Nama lengkap wajib diisi.';
};

const validateKK = (): void => {
  errors.family_card_number = '';
  const val = form.family_card_number.replace(/\D/g, '');
  form.family_card_number = val;
  if (!val) errors.family_card_number = 'No. KK wajib diisi.';
  else if (val.length !== 16) errors.family_card_number = 'No. KK harus 16 digit.';
};

const validateWA = (): void => {
  errors.whatsapp_number = '';
  if (form.whatsapp_number) {
    const val = form.whatsapp_number.replace(/\D/g, '');
    form.whatsapp_number = val;
  }
};

const handleSubmit = (): void => {
  validateNik();
  validateName();
  validateKK();
  if (errors.nik || errors.full_name || errors.family_card_number) return;
  
  emit('submit', {
    nik: form.nik,
    full_name: form.full_name.trim(),
    family_card_number: form.family_card_number,
    whatsapp_number: form.whatsapp_number,
  });
};
</script>