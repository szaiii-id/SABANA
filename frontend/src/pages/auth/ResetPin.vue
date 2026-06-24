<template>
  <AuthLayout maxWidth="md">
    <div class="text-center mb-10">
      <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Atur PIN Baru</h1>
      <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Verifikasi OTP & Ubah PIN Keamanan</p>
    </div>

    <form @submit.prevent="handleReset" class="space-y-6">
      <div v-if="safeError" class="p-4 rounded-2xl bg-red-50 text-red-700 text-[10px] font-black border border-red-100 uppercase text-center">
        {{ safeError }}
      </div>

      <div class="relative pb-6">
        <input v-model="formData.otp" @input="formatNumeric('otp', 6)" type="text" inputmode="numeric" placeholder=" " class="peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl bg-gray-50 border-gray-200 focus:border-[#2D6A4F] focus:bg-white" />
        <label class="absolute left-4 top-4 text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:bg-white peer-valid:px-1">Kode OTP</label>
      </div>

      <div class="grid grid-cols-1 gap-6">
        <div class="relative pb-6">
          <input v-model="formData.new_pin" @input="formatNumeric('new_pin', 6)" type="password" inputmode="numeric" placeholder=" " class="peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl bg-gray-50 border-gray-200 focus:border-[#D4A373] focus:bg-white" />
          <label class="absolute left-4 top-4 text-[10px] font-black text-[#D4A373] uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:bg-white peer-valid:px-1">PIN Baru (6 Digit)</label>
        </div>

        <div class="relative pb-6">
          <input v-model="formData.new_pin_confirmation" @input="formatNumeric('new_pin_confirmation', 6)" type="password" inputmode="numeric" placeholder=" " class="peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl bg-gray-50 border-gray-200 focus:border-[#D4A373] focus:bg-white" />
          <label class="absolute left-4 top-4 text-[10px] font-black text-[#D4A373] uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:bg-white peer-valid:px-1">Konfirmasi PIN</label>
        </div>
      </div>

      <button :disabled="isSubmitting" type="submit" class="w-full py-5 bg-[#2D6A4F] text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-[#1b4332] transition-all">
        {{ isSubmitting ? 'MEMPROSES...' : 'SIMPAN PIN BARU' }}
      </button>
    </form>
    
    <SuccessModal :show="showSuccessModal" @confirm="handleSuccessConfirm" />
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useForgotPin } from '../../composables/useForgotPin';
import { getSafeErrorMessage } from '../../utils/errorHandler';
import AuthLayout from '../../layouts/AuthLayout.vue';
import SuccessModal from '../../components/auth/SuccessModal.vue';

// ===== TYPES =====
type NumericField = 'otp' | 'new_pin' | 'new_pin_confirmation';

// ===== COMPOSABLES =====
const route = useRoute();
const router = useRouter();
const { isSubmitting, errorMessage, executeReset } = useForgotPin();

// ===== STATE =====
const showSuccessModal = ref(false);
const formData = ref({
  nik: (route.query.nik as string) || '',
  whatsapp_number: (route.query.wa as string) || '',
  otp: '',
  new_pin: '',
  new_pin_confirmation: '',
});

// ===== COMPUTED =====
const safeError = computed(() => getSafeErrorMessage(errorMessage.value));

// ===== LIFECYCLE =====
onMounted(() => {
  if (!formData.value.nik) router.replace({ name: 'login' });
});

// ===== METHODS =====
const formatNumeric = (field: NumericField, maxLength: number): void => {
  formData.value[field] = formData.value[field].replace(/\D/g, '').substring(0, maxLength);
};

const handleReset = async (): Promise<void> => {
  const result = await executeReset(formData.value);
  if (result.success) showSuccessModal.value = true;
};

const handleSuccessConfirm = (): void => {
  showSuccessModal.value = false;
  router.push({ name: 'login' });
};
</script>