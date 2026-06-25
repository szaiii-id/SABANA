<template>
  <AuthLayout maxWidth="xl" :extraPadding="true">
    
    <!-- Header -->
    <div class="text-center mb-10">
      <router-link to="/" class="inline-block mb-4" aria-label="Kembali ke halaman utama">
        <span class="text-3xl font-black italic tracking-tighter text-[#2D6A4F]">SABANA</span>
      </router-link>
      <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">
        {{ isEditMode ? 'Perbaiki Nomor WhatsApp' : 'Registrasi Warga' }}
      </h1>
      <p class="text-sm font-bold text-gray-500">
        {{ isEditMode ? 'Perbaiki nomor WhatsApp yang salah.' : 'Gunakan data kependudukan yang valid.' }}
      </p>
    </div>

    <!-- Edit Mode Alert -->
    <Transition 
      enter-active-class="transition duration-300 ease-out" 
      enter-from-class="transform -translate-y-2 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100"
    >
      <div v-if="isEditMode" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3" role="alert">
        <span class="text-amber-500 font-black text-lg leading-none mt-0.5" aria-hidden="true">✎</span>
        <div>
          <h3 class="text-[10px] font-black text-amber-800 uppercase tracking-widest mb-1">Perbaiki Nomor WhatsApp</h3>
          <p class="text-xs font-bold text-amber-700 leading-relaxed">OTP sebelumnya gagal terkirim. Silakan perbaiki nomor WhatsApp Anda.</p>
        </div>
      </div>
    </Transition>

    <!-- Loading Prefill -->
    <Transition 
      enter-active-class="transition duration-300 ease-out" 
      enter-from-class="transform -translate-y-2 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100"
    >
      <div v-if="isLoadingPrefill" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-2xl flex items-center justify-center gap-3" role="status">
        <svg class="animate-spin h-5 w-5 text-[#2D6A4F]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs font-bold text-gray-500">Memuat data pendaftaran...</span>
      </div>
    </Transition>

    <!-- Server Error Alert -->
    <Transition 
      enter-active-class="transition duration-300 ease-out" 
      enter-from-class="transform -translate-y-2 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100"
    >
      <div v-if="safeAuthError" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-3" role="alert">
        <span class="text-red-500 font-black text-lg leading-none mt-0.5" aria-hidden="true">⚠</span>
        <div>
          <h3 class="text-[10px] font-black text-red-800 uppercase tracking-widest mb-1">Gagal Registrasi</h3>
          <p class="text-xs font-bold text-red-600 leading-relaxed">{{ safeAuthError }}</p>
        </div>
      </div>
    </Transition>

    <form @submit.prevent="onSubmit" class="space-y-2" novalidate>
      
      <!-- NIK & No. KK -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
        <FormField 
          v-model="formData.nik"
          label="NIK (16 Digit)"
          :maxlength="16"
          :disabled="isEditMode"
          :error="errors.nik.isError"
          :error-message="errors.nik.message"
          @update:model-value="(val: string) => formatNumeric('nik', 16, val)"
          @blur="v$.nik.$touch()"
        />
        <FormField 
          v-model="formData.family_card_number"
          label="Nomor KK"
          :maxlength="16"
          :disabled="isEditMode"
          :error="errors.family_card_number.isError"
          :error-message="errors.family_card_number.message"
          @update:model-value="(val: string) => formatNumeric('family_card_number', 16, val)"
          @blur="v$.family_card_number.$touch()"
        />
      </div>

      <!-- Nama Lengkap -->
      <FormField 
        v-model="formData.full_name"
        label="Nama Lengkap (Sesuai KTP)"
        :disabled="isEditMode"
        :error="errors.full_name.isError"
        :error-message="errors.full_name.message"
        @blur="v$.full_name.$touch()"
      />

      <!-- Nomor WhatsApp -->
      <FormField 
        v-model="formData.whatsapp_number"
        label="Nomor WhatsApp Aktif"
        type="tel"
        :maxlength="15"
        :error="errors.whatsapp_number.isError"
        :error-message="errors.whatsapp_number.message"
        @update:model-value="(val: string) => formatNumeric('whatsapp_number', 15, val)"
        @blur="v$.whatsapp_number.$touch()"
      />

      <!-- PIN & Konfirmasi -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 pb-2">
        <FormField 
          v-model="formData.pin"
          label="Buat PIN (6 Digit)"
          type="password"
          :maxlength="6"
          input-class="text-center tracking-[0.5em] text-xl"
          label-color="text-[#D4A373]"
          focus-color="focus:border-[#D4A373]"
          :error="errors.pin.isError"
          :error-message="errors.pin.message"
          @update:model-value="(val: string) => formatNumeric('pin', 6, val)"
          @blur="v$.pin.$touch()"
        />
        <FormField 
          v-model="formData.pin_confirmation"
          label="Konfirmasi PIN"
          type="password"
          :maxlength="6"
          input-class="text-center tracking-[0.5em] text-xl"
          label-color="text-[#D4A373]"
          focus-color="focus:border-[#D4A373]"
          :error="errors.pin_confirmation.isError"
          :error-message="errors.pin_confirmation.message"
          @update:model-value="(val: string) => formatNumeric('pin_confirmation', 6, val)"
          @blur="v$.pin_confirmation.$touch()"
        />
      </div>

      <!-- Agreement Checkbox (hanya saat registrasi baru) -->
      <div 
        v-if="!isEditMode"
        :class="[
          'mb-6 rounded-2xl p-4 flex items-start gap-4 transition-all duration-300',
          v$.agree_terms.$error 
            ? 'bg-red-50/50 border-2 border-red-200' 
            : 'bg-gray-50/50 border border-gray-100'
        ]"
      >
        <div class="relative flex items-center justify-center mt-0.5 shrink-0">
          <input 
            id="agree-terms"
            type="checkbox" 
            v-model="formData.agree_terms" 
            @change="v$.agree_terms.$touch()"
            class="peer appearance-none w-5 h-5 border-2 border-gray-300 rounded-lg checked:bg-[#2D6A4F] checked:border-[#2D6A4F] transition-all cursor-pointer"
            aria-describedby="agree-terms-error"
          />
          <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4" aria-hidden="true">
            <path d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div class="flex-1">
          <label 
            for="agree-terms"
            class="text-[10px] sm:text-[11px] font-medium text-gray-500 leading-relaxed cursor-pointer select-none block" 
          >
            Saya menyatakan bahwa data kependudukan (NIK & No. KK) yang saya masukkan adalah <strong class="text-gray-700">benar dan milik saya pribadi</strong>. Saya mengizinkan SABANA untuk memprosesnya sesuai dengan 
            <a href="#" class="text-[#2D6A4F] hover:underline font-black whitespace-nowrap" @click.stop>Kebijakan Privasi</a>.
          </label>
          <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform -translate-y-1 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
            <p v-if="v$.agree_terms.$error" id="agree-terms-error" class="text-[10px] font-bold text-red-500 mt-2" role="alert">
              ⚠ Anda harus menyetujui syarat & ketentuan untuk melanjutkan.
            </p>
          </Transition>
        </div>
      </div>

      <!-- Submit Button -->
      <SubmitButton 
        :is-submitting="isSubmitting || isLoadingPrefill"
        :label="isEditMode ? 'SIMPAN & KIRIM OTP BARU' : 'DAFTAR SEKARANG'"
        loading-label="MEMPROSES..." 
      />
    </form>

    <div v-if="isEditMode" class="mt-6 text-center">
      <button 
        type="button"
        @click="goBackToVerify"
        class="text-xs font-bold text-gray-400 hover:text-[#D4A373] transition-colors uppercase tracking-wider"
      >
        Kembali ke Verifikasi
      </button>
    </div>

    <!-- Login Link (hanya saat registrasi baru) -->
    <div v-if="!isEditMode" class="mt-8 text-center">
      <p class="text-xs font-bold text-gray-500">
        Sudah terdaftar di SABANA? 
        <router-link 
          :to="{ name: 'login' }" 
          class="text-[#D4A373] hover:text-[#2D6A4F] transition-colors ml-1 uppercase tracking-wider font-black"
        >
          Masuk di sini
        </router-link>
      </p>
    </div>

  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, sameAs, helpers } from '@vuelidate/validators';
import type { RegisterPayload } from '../../types/auth';
import { useAuth } from '../../composables/useAuth';
import { getSafeErrorMessage } from '../../utils/errorHandler';
import AuthLayout from '../../layouts/AuthLayout.vue';
import FormField from '../../components/auth/FormField.vue';
import SubmitButton from '../../components/auth/SubmitButton.vue';
import api from '../../api/axios';

// ===== TYPES =====
type NumericFields = 'nik' | 'family_card_number' | 'whatsapp_number' | 'pin' | 'pin_confirmation';

interface ErrorState {
  isError: boolean;
  message: string;
}

interface FormErrors {
  nik: ErrorState;
  family_card_number: ErrorState;
  full_name: ErrorState;
  whatsapp_number: ErrorState;
  pin: ErrorState;
  pin_confirmation: ErrorState;
}

// ===== COMPOSABLES =====
const route = useRoute();
const router = useRouter();
const { isSubmitting, authError, submitRegistration } = useAuth();

// ===== STATE =====
const isEditMode = computed<boolean>(() => route.query.edit === 'true');
const isLoadingPrefill = ref<boolean>(false);
const abortController = ref<AbortController | null>(null);

const getInitialFormData = (): RegisterPayload & { agree_terms: boolean } => ({
  nik: '',
  family_card_number: '',
  full_name: '',
  whatsapp_number: '',
  pin: '',
  pin_confirmation: '',
  agree_terms: false,
});

const formData = ref<RegisterPayload & { agree_terms: boolean }>(getInitialFormData());

// ===== LIFECYCLE =====
onMounted(() => {
  if (isEditMode.value) {
    loadPrefillData();
  }
});

onUnmounted(() => {
  if (abortController.value) {
    abortController.value.abort();
  }
  
  formData.value = getInitialFormData();
  v$.value.$reset();
});

// ===== PREFILL LOGIC =====
const loadPrefillData = async (): Promise<void> => {
  const nik = route.query.nik as string;
  
  if (!nik) {
    router.replace({ name: 'register' });
    return;
  }

  abortController.value = new AbortController();
  isLoadingPrefill.value = true;

  try {
    const response = await api.get(`auth/prefill-registration?nik=${nik}`, {
      signal: abortController.value.signal,
    });
    
    const data = response.data?.data || response.data;
    
    formData.value.nik = data.nik || nik;
    formData.value.family_card_number = data.family_card_number || '';
    formData.value.full_name = data.full_name || '';
    formData.value.whatsapp_number = data.whatsapp_number || (route.query.wa as string) || '';
    formData.value.agree_terms = true;
  } catch (error: unknown) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return;
    }
    
    const axiosError = error as { response?: { data?: { message?: string } } };
    authError.value = axiosError.response?.data?.message || 'Gagal memuat data pendaftaran.';
  } finally {
    isLoadingPrefill.value = false;
    abortController.value = null;
  }
};

// ===== COMPUTED =====
const safeAuthError = computed<string>(() => getSafeErrorMessage(authError.value));

const createErrorState = (fieldName: keyof typeof v$.value): ErrorState => {
  const field = v$.value[fieldName] as { $error: boolean; $errors: Array<{ $message?: string | { toString(): string } }> } | undefined;
  
  if (!field) {
    return { isError: false, message: '' };
  }
  
  const firstError = field.$errors[0];
  let message = '';
  
  if (firstError?.$message) {
    message = typeof firstError.$message === 'string' 
      ? firstError.$message 
      : firstError.$message.toString();
  }
  
  return {
    isError: field.$error,
    message,
  };
};

const errors = computed<FormErrors>(() => ({
  nik: createErrorState('nik'),
  family_card_number: createErrorState('family_card_number'),
  full_name: createErrorState('full_name'),
  whatsapp_number: createErrorState('whatsapp_number'),
  pin: createErrorState('pin'),
  pin_confirmation: createErrorState('pin_confirmation'),
}));

// ===== FIELD HELPERS =====
const formatNumeric = (field: NumericFields, maxLength: number, value: string): void => {
  const cleaned = value.replace(/\D/g, '').substring(0, maxLength);
  formData.value[field] = cleaned;
};

// ===== VALIDATION RULES =====
const rules = computed(() => ({
  nik: { 
    required: helpers.withMessage('Wajib diisi', required), 
    length: helpers.withMessage('Harus tepat 16 digit', (val: string) => val.length === 16),
  },
  family_card_number: { 
    required: helpers.withMessage('Wajib diisi', required), 
    length: helpers.withMessage('Harus tepat 16 digit', (val: string) => val.length === 16),
  },
  full_name: { 
    required: helpers.withMessage('Wajib diisi', required),
  },
  whatsapp_number: { 
    required: helpers.withMessage('Wajib diisi', required), 
    minLength: helpers.withMessage('Minimal 10 digit', (val: string) => val.length >= 10),
  },
  pin: { 
    required: helpers.withMessage('Wajib diisi', required), 
    length: helpers.withMessage('Harus tepat 6 digit', (val: string) => val.length === 6),
  },
  pin_confirmation: { 
    required: helpers.withMessage('Wajib diisi', required), 
    sameAs: helpers.withMessage('PIN tidak cocok', sameAs(formData.value.pin)),
  },
  agree_terms: isEditMode.value 
    ? {} 
    : { 
        sameAs: helpers.withMessage('Anda harus menyetujui syarat & ketentuan', sameAs(true)),
      },
}));

const v$ = useVuelidate(rules, formData);

// ===== NAVIGATION =====
const goBackToVerify = (): void => {
  router.push({ 
    name: 'verify-otp', 
    query: { 
      nik: formData.value.nik, 
      wa: formData.value.whatsapp_number,
    },
  });
};

// ===== SUBMIT =====
const onSubmit = async (): Promise<void> => {
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  const { agree_terms, ...payloadToSend } = formData.value;
  const result = await submitRegistration(payloadToSend as RegisterPayload);
  
  if (result.success) {
    await router.replace({ 
      name: 'verify-otp', 
      query: { 
        nik: formData.value.nik, 
        wa: formData.value.whatsapp_number,
      },
    });
  }
};
</script>