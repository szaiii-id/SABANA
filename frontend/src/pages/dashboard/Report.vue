<template>
  <div class="h-full flex flex-col justify-center max-w-5xl mx-auto py-8 lg:py-12 px-4">
    <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
      
      <div class="lg:col-span-5 lg:sticky lg:top-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#D4A373]/10 text-[#D4A373] mb-6 border border-[#D4A373]/20">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
        </div>
        
        <h2 class="text-3xl lg:text-4xl font-[1000] text-gray-900 tracking-tight leading-tight mb-4">
          Layanan <br/>
          <span class="text-[#D4A373]">Aspirasi Warga.</span>
        </h2>
        
        <p class="text-gray-500 leading-relaxed font-medium mb-8">
          Sampaikan kendala atau keluhan Anda. Laporan akan diteruskan secara otomatis ke tim terkait.
        </p>

        <div class="flex items-start gap-4 p-4 rounded-2xl bg-[#D4A373]/5 border border-[#D4A373]/10 text-[#A67C52]">
          <svg class="w-5 h-5 shrink-0 mt-0.5 text-[#D4A373]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex flex-col">
            <span class="text-xs font-bold tracking-wide">Privasi Terjamin</span>
            <span class="text-[11px] font-medium mt-1">Data NIK Anda akan dikirimkan secara aman hanya kepada sistem internal SABANA.</span>
          </div>
        </div>
      </div>

      <div class="lg:col-span-7">
        <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100">
          
          <div class="flex p-1 mb-8 bg-gray-100/80 rounded-2xl">
            <button 
              @click="activeTab = 'whatsapp'"
              :class="activeTab === 'whatsapp' ? 'bg-white text-[#D4A373] shadow-sm font-bold' : 'text-gray-500 hover:text-gray-700 font-medium'"
              class="flex-1 py-3 text-sm rounded-xl transition-all duration-300 flex items-center justify-center gap-2"
            >
              WhatsApp
            </button>
            <button 
              @click="activeTab = 'email'"
              :class="activeTab === 'email' ? 'bg-white text-[#D4A373] shadow-sm font-bold' : 'text-gray-500 hover:text-gray-700 font-medium'"
              class="flex-1 py-3 text-sm rounded-xl transition-all duration-300 flex items-center justify-center gap-2"
            >
              Email
            </button>
          </div>

          <transition enter-active-class="transition duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="notification.message" :class="notification.type === 'success' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-600 border-red-100'" class="p-4 mb-6 rounded-xl border text-sm font-bold flex items-center gap-3">
              {{ notification.message }}
            </div>
          </transition>

          <form @submit.prevent="handleSubmit" class="flex flex-col gap-6">
            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pengirim</label>
                <div class="px-4 py-3 bg-gray-50 text-gray-500 rounded-xl text-sm font-bold border border-gray-100 uppercase">{{ citizenData.full_name }}</div>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">NIK</label>
                <div class="px-4 py-3 bg-gray-50 text-gray-500 rounded-xl text-sm font-bold border border-gray-100">{{ citizenData.nik }}</div>
              </div>
            </div>

            <div v-if="activeTab === 'email'" class="flex flex-col gap-5 animate-in fade-in slide-in-from-top-2">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-gray-700">Email Anda</label>
                <input 
                  v-model="form.email" 
                  @input="clearError('email')"
                  type="email" 
                  placeholder="nama@email.com"
                  :class="emailError ? 'border-red-300' : 'border-gray-200 focus:border-[#D4A373] focus:ring-[#D4A373]/10'"
                  class="w-full px-5 py-3.5 bg-white rounded-xl text-sm border-2 outline-none transition-all"
                />
                <span v-if="emailError" class="text-xs font-bold text-red-500">{{ emailError }}</span>
              </div>

              <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-gray-700">Subjek Laporan</label>
                <input 
                  v-model="form.subjek" 
                  @input="clearError('subjek')"
                  type="text" 
                  placeholder="Masukkan judul laporan..."
                  :class="subjekError ? 'border-red-300' : 'border-gray-200 focus:border-[#D4A373] focus:ring-[#D4A373]/10'"
                  class="w-full px-5 py-3.5 bg-white rounded-xl text-sm border-2 outline-none transition-all"
                />
                <span v-if="subjekError" class="text-xs font-bold text-red-500">{{ subjekError }}</span>
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-bold text-gray-700">Isi Laporan</label>
              <textarea 
                v-model="form.pesan" 
                @input="clearError('pesan')"
                rows="5"
                placeholder="Tuliskan keluhan secara lengkap..."
                :class="pesanError ? 'border-red-300' : 'border-gray-200 focus:border-[#D4A373] focus:ring-[#D4A373]/10'"
                class="w-full px-5 py-3.5 bg-white rounded-xl text-sm border-2 outline-none transition-all resize-none"
              ></textarea>
              <span v-if="pesanError" class="text-xs font-bold text-red-500">{{ pesanError }}</span>
            </div>

            <button 
              type="submit"
              :disabled="isSubmitting"
              class="w-full py-4 bg-gradient-to-r from-[#D4A373] to-[#BC8A5F] text-white rounded-xl font-extrabold text-sm tracking-widest shadow-lg shadow-[#D4A373]/20 hover:shadow-[#D4A373]/40 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 disabled:hover:translate-y-0 flex items-center justify-center gap-3 uppercase"
            >
              <svg v-if="isSubmitting" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isSubmitting ? 'Mengirim...' : (activeTab === 'whatsapp' ? 'Kirim via WhatsApp' : 'Kirim via Email') }}
            </button>
          </form>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { reportApi } from '../../api/reportApi';
import api from '../../api/axios';

// ===== TYPES =====
interface ApiError {
  response?: {
    status?: number;
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

type TabType = 'whatsapp' | 'email';
type ErrorField = 'email' | 'subjek' | 'pesan';

// ===== STATE =====
const activeTab = ref<TabType>('whatsapp');
const isSubmitting = ref(false);

const citizenData = reactive({
  nik: '',
  full_name: '',
});

const form = reactive({
  email: '',
  subjek: '',
  pesan: '',
});

const errors = reactive({
  email: '',
  subjek: '',
  pesan: '',
});

const notification = reactive({
  type: '' as string,
  message: '',
});

// ===== REAL-TIME VALIDATION =====
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const emailError = computed<string>(() => {
  if (!form.email) return '';
  if (!emailRegex.test(form.email)) return 'Format email tidak valid';
  return '';
});

const subjekError = computed<string>(() => {
  if (!form.subjek) return '';
  if (form.subjek.length < 5) return 'Subjek minimal 5 karakter';
  return '';
});

const pesanError = computed<string>(() => {
  if (!form.pesan) return '';
  if (form.pesan.length < 10) return 'Pesan minimal 10 karakter';
  return '';
});

// ===== FETCH =====
onMounted(async () => {
  try {
    const response = await api.get('/citizen/profile');
    const data = response.data?.data || response.data;
    citizenData.nik = data.nik || '';
    citizenData.full_name = data.full_name || '';
  } catch {
    // Fallback — biarkan kosong
  }
});

// ===== WATCH =====
watch(activeTab, () => {
  form.email = '';
  form.subjek = '';
  form.pesan = '';
  errors.email = '';
  errors.subjek = '';
  errors.pesan = '';
  notification.message = '';
});

// ===== HELPERS =====
const clearError = (field: ErrorField): void => {
  errors[field] = '';
  notification.message = '';
};

const validate = (): boolean => {
  let valid = true;
  if (activeTab.value === 'email') {
    if (emailError.value) {
      errors.email = emailError.value;
      valid = false;
    }
    if (subjekError.value) {
      errors.subjek = subjekError.value;
      valid = false;
    }
  }
  if (pesanError.value) {
    errors.pesan = pesanError.value;
    valid = false;
  }
  return valid;
};

// ===== SUBMIT =====
const handleSubmit = async (): Promise<void> => {
  if (!validate()) return;
  isSubmitting.value = true;
  notification.message = '';

  try {
    const res = activeTab.value === 'whatsapp'
      ? await reportApi.sendWhatsapp({ pesan: form.pesan })
      : await reportApi.sendEmail({
          nama: citizenData.full_name,
          email: form.email,
          subjek: form.subjek,
          pesan: form.pesan,
        });

    notification.type = 'success';
    notification.message = res.message;
    form.pesan = '';
    form.subjek = '';
    form.email = '';
  } catch (err: unknown) {
    const apiError = err as ApiError;
    notification.type = 'error';

    if (apiError.response?.status === 422) {
      const serverErrors = apiError.response.data?.errors;
      if (serverErrors) {
        if (serverErrors.subjek) errors.subjek = serverErrors.subjek[0];
        if (serverErrors.pesan) errors.pesan = serverErrors.pesan[0];
        if (serverErrors.email) errors.email = serverErrors.email[0];
      }
      notification.message = apiError.response?.data?.message || 'Validasi gagal.';
    } else {
      notification.message = 'Gagal mengirim laporan.';
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>