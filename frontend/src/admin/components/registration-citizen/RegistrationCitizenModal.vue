<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="submitting ? null : $emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden border border-[#E8D5C4]">
        
        <div class="px-10 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
          <h3 class="text-xl font-black text-[#1B4332]">Daftarkan Warga Baru</h3>
          <button @click="$emit('close')" :disabled="submitting" class="text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent class="p-10 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">NIK (16 Digit)</label>
              <input 
                v-model="form.nik" 
                type="text" 
                inputmode="numeric" 
                maxlength="16" 
                placeholder="6312345678901234"
                :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.nik ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                @input="validateNik"
              >
              <p v-if="errors.nik" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.nik }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Nama Lengkap</label>
              <input 
                v-model="form.full_name" 
                type="text" 
                placeholder="Nama lengkap warga"
                :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.full_name ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                @input="validateName"
              >
              <p v-if="errors.full_name" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.full_name }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">No. KK (16 Digit)</label>
              <input 
                v-model="form.family_card_number" 
                type="text" 
                inputmode="numeric" 
                maxlength="16" 
                placeholder="6309876543212345"
                :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.family_card_number ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
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
                placeholder="081234567890"
                :class="['w-full px-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm', errors.whatsapp_number ? 'border-[#FCA5A5] bg-[#FEF2F2]' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10']"
                @input="validateWA"
              >
              <p v-if="errors.whatsapp_number" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.whatsapp_number }}</p>
              <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">Wajib diisi untuk kirim PIN akses</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 pt-4 border-t border-[#E8D5C4]">
            <button 
              type="button"
              @click="submitForm(true)"
              :disabled="submitting"
              class="p-5 rounded-2xl text-sm font-bold transition-all duration-300 flex flex-col items-center gap-2 text-center bg-[#1B4332] text-white hover:bg-[#2D6A4F] shadow-lg shadow-[#1B4332]/20"
            >
              <span class="text-base">DAFTARKAN + KIRIM PIN AKSES</span>
              <span class="text-[10px] font-medium opacity-80">Warga bisa login sendiri dengan PIN via WhatsApp</span>
            </button>
            <button 
              type="button"
              @click="submitForm(false)"
              :disabled="submitting"
              class="p-5 rounded-2xl text-sm font-bold transition-all duration-300 flex flex-col items-center gap-2 text-center bg-[#FAF6F0] text-[#1B4332] border-2 border-[#E8D5C4] hover:bg-gray-100"
            >
              <span class="text-base">DAFTARKAN</span>
              <span class="text-[10px] font-medium opacity-80">Tanpa kirim PIN, admin yang akan mengajukan bantuan</span>
            </button>
          </div>

          <p v-if="error" class="text-sm text-red-600 bg-red-50 p-3 rounded-xl border border-red-200">{{ error }}</p>
        </form>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import type { RegisterCitizenPayload } from '../../types/registration-citizen';

const props = defineProps<{ open: boolean; submitting: boolean; error: string }>();
const emit = defineEmits<{ close: []; submit: [payload: RegisterCitizenPayload] }>();

const initialForm = (): RegisterCitizenPayload => ({
  nik: '',
  full_name: '',
  family_card_number: '',
  whatsapp_number: '',
  with_pin: false,
});

const initialErrors = () => ({
  nik: '',
  full_name: '',
  family_card_number: '',
  whatsapp_number: '',
});

const form = reactive<RegisterCitizenPayload>(initialForm());
const errors = reactive(initialErrors());

// ✅ Reset form setiap kali modal dibuka
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    Object.assign(form, initialForm());
    Object.assign(errors, initialErrors());
  }
});

const validateNik = () => {
  errors.nik = '';
  const val = form.nik.replace(/\D/g, '');
  form.nik = val;
  if (!val) errors.nik = 'NIK wajib diisi.';
  else if (val.length !== 16) errors.nik = 'NIK harus 16 digit.';
};

const validateName = () => {
  errors.full_name = '';
  if (!form.full_name.trim()) errors.full_name = 'Nama lengkap wajib diisi.';
};

const validateKK = () => {
  errors.family_card_number = '';
  const val = form.family_card_number.replace(/\D/g, '');
  form.family_card_number = val;
  if (!val) errors.family_card_number = 'No. KK wajib diisi.';
  else if (val.length !== 16) errors.family_card_number = 'No. KK harus 16 digit.';
};

const validateWA = () => {
  errors.whatsapp_number = '';
  if (form.whatsapp_number) {
    const val = form.whatsapp_number.replace(/\D/g, '');
    form.whatsapp_number = val;
  }
};

const validateForm = (withPin: boolean): boolean => {
  validateNik();
  validateName();
  validateKK();
  if (withPin && !form.whatsapp_number) {
    errors.whatsapp_number = 'No. WA wajib diisi untuk kirim PIN.';
    return false;
  }
  return !errors.nik && !errors.full_name && !errors.family_card_number && !errors.whatsapp_number;
};

const submitForm = (withPin: boolean) => {
  if (!validateForm(withPin)) return;
  emit('submit', { ...form, with_pin: withPin });
  
  // ✅ Reset form setelah submit
  Object.assign(form, initialForm());
  Object.assign(errors, initialErrors());
};
</script>