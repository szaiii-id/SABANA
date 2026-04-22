<template>
  <section id="kontak" class="py-24 relative z-10 overflow-hidden">
    
    <transition 
      enter-active-class="transition duration-500 ease-out" 
      enter-from-class="transform -translate-y-10 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100" 
      leave-active-class="transition duration-300 ease-in" 
      leave-from-class="transform translate-y-0 opacity-100" 
      leave-to-class="transform -translate-y-10 opacity-0"
    >
      <div v-if="toastMessage" class="fixed top-8 left-1/2 transform -translate-x-1/2 z-50 w-[90%] max-w-md">
        <div :class="['border-l-4 p-4 rounded-r-2xl shadow-[0_15px_30px_rgba(0,0,0,0.15)] flex items-start gap-3 backdrop-blur-xl', isSuccessToast ? 'bg-green-50/95 border-green-500' : 'bg-red-50/95 border-red-500']">
          
          <svg v-if="isSuccessToast" class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <svg v-else class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>

          <div>
            <h3 :class="['text-[11px] font-black uppercase tracking-widest mb-1', isSuccessToast ? 'text-green-800' : 'text-red-800']">
              {{ isSuccessToast ? 'Berhasil' : 'Pemberitahuan Keamanan' }}
            </h3>
            <p :class="['text-xs font-bold leading-relaxed', isSuccessToast ? 'text-green-700' : 'text-red-700']">
              {{ toastMessage }}
            </p>
          </div>

          <button @click="toastMessage = ''" :class="['ml-auto transition-colors p-1', isSuccessToast ? 'text-green-400 hover:text-green-600' : 'text-red-400 hover:text-red-600']">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </transition>

    <div class="container mx-auto px-6">
      
      <div class="flex flex-col lg:flex-row lg:items-end justify-between mb-20 gap-8">
        <div class="max-w-3xl">
          <h2 class="text-6xl md:text-8xl font-[1000] text-[#2D6A4F] uppercase italic tracking-tighter leading-[0.85] mb-6">
            Layanan <br/> <span class="text-[#D4A373]">Aspirasi</span>
          </h2>
          <p class="text-gray-700 font-bold text-xl leading-relaxed">
            Tim SABANA siap mendengarkan setiap masukan Anda untuk pengembangan layanan bantuan yang lebih baik bagi seluruh Anak Banua.
          </p>
        </div>
        
        <div class="flex items-center gap-4 bg-[#2D6A4F]/10 backdrop-blur-md px-6 py-4 rounded-3xl border border-[#2D6A4F]/20">
          <div class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(34,197,94,0.8)]"></div>
          <span class="text-xs font-black text-[#2D6A4F] uppercase tracking-[0.15em]">Layanan Digital Aktif 24 Jam</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-stretch">
        
        <div class="lg:col-span-5 space-y-6 flex flex-col">
          <a href="https://wa.me/6287749445356?text=Halo%20Admin%20SABANA" target="_blank" 
             class="group flex-1 flex items-center p-10 rounded-[3.5rem] bg-[#2D6A4F] text-white shadow-2xl shadow-[#2D6A4F]/20 hover:-translate-y-2 transition-all duration-500">
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-3xl group-hover:rotate-12 transition-transform duration-500">
              💬
            </div>
            <div class="ml-8">
              <p class="text-2xl font-[1000] italic leading-none">WhatsApp</p>
              <p class="text-xs font-bold opacity-60 mt-2 uppercase tracking-widest">Klik untuk Chat Langsung</p>
            </div>
          </a>

          <div class="flex-1 p-10 rounded-[3.5rem] bg-white/40 backdrop-blur-xl border border-white/80 shadow-xl flex items-center">
            <div class="w-16 h-16 bg-[#D4A373]/10 rounded-2xl flex items-center justify-center text-3xl">
              ✉️
            </div>
            <div class="ml-8">
              <p class="text-xl font-black text-gray-800 break-all leading-none">sabana63.id@gmail.com</p>
              <p class="text-xs font-bold text-[#D4A373] mt-2 uppercase tracking-widest">Korespondensi Resmi</p>
            </div>
          </div>
        </div>

        <div class="lg:col-span-7 bg-white/60 backdrop-blur-2xl p-10 md:p-14 rounded-[4rem] border border-white/80 shadow-2xl relative">
          <form @submit.prevent="onSubmit" class="space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
              
              <div class="relative mb-2">
                <input 
                  v-model.trim="formData.nama" 
                  @blur="v$.nama.$touch()"
                  type="text" 
                  placeholder=" " 
                  :class="[
                    'peer w-full bg-transparent border-b-2 py-2 outline-none transition-all font-bold text-gray-800',
                    v$.nama.$error ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-[#2D6A4F]'
                  ]"
                />
                <label :class="['absolute left-0 -top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-4 peer-focus:text-[10px]', v$.nama.$error ? 'text-red-500 peer-placeholder-shown:text-red-400 peer-focus:text-red-500' : 'text-[#2D6A4F] peer-placeholder-shown:text-gray-400 peer-focus:text-[#2D6A4F]']">Nama Lengkap</label>
                <span v-if="v$.nama.$error" class="absolute -bottom-5 left-0 text-[10px] font-bold text-red-500 italic">{{ v$.nama.$errors[0].$message }}</span>
              </div>
              
              <div class="relative mb-2">
                <input 
                  v-model.trim="formData.email" 
                  @blur="v$.email.$touch()"
                  type="email" 
                  placeholder=" " 
                  :class="[
                    'peer w-full bg-transparent border-b-2 py-2 outline-none transition-all font-bold text-gray-800',
                    v$.email.$error ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-[#2D6A4F]'
                  ]"
                />
                <label :class="['absolute left-0 -top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-4 peer-focus:text-[10px]', v$.email.$error ? 'text-red-500 peer-placeholder-shown:text-red-400 peer-focus:text-red-500' : 'text-[#2D6A4F] peer-placeholder-shown:text-gray-400 peer-focus:text-[#2D6A4F]']">Alamat Email</label>
                <span v-if="v$.email.$error" class="absolute -bottom-5 left-0 text-[10px] font-bold text-red-500 italic">{{ v$.email.$errors[0].$message }}</span>
              </div>
            </div>

            <div class="relative mb-2">
              <select 
                v-model="formData.subjek" 
                @blur="v$.subjek.$touch()"
                :class="[
                  'peer w-full bg-transparent border-b-2 py-2 outline-none transition-all font-black text-gray-700 appearance-none uppercase text-xs',
                  v$.subjek.$error ? 'border-red-500 text-red-500 focus:border-red-500' : 'border-gray-300 focus:border-[#2D6A4F]'
                ]"
              >
                <option disabled value="">Pilih Kategori Aspirasi</option>
                <option value="Pengaduan Penyalahgunaan">Pengaduan Penyalahgunaan</option>
                <option value="Laporan Kendala Penyaluran">Laporan Kendala Penyaluran</option>
                <option value="Pertanyaan Syarat Bantuan">Pertanyaan Syarat Bantuan</option>
                <option value="Saran & Masukan Sistem">Saran & Masukan Sistem</option>
              </select>
              <label :class="['absolute left-0 -top-4 text-[10px] font-black uppercase tracking-widest', v$.subjek.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Kategori</label>
              <span v-if="v$.subjek.$error" class="absolute -bottom-5 left-0 text-[10px] font-bold text-red-500 italic">{{ v$.subjek.$errors[0].$message }}</span>
            </div>

            <div class="relative mb-2">
              <textarea 
                v-model.trim="formData.pesan" 
                @blur="v$.pesan.$touch()"
                rows="3" 
                placeholder=" " 
                :class="[
                  'peer w-full bg-transparent border-b-2 py-2 outline-none transition-all font-bold text-gray-800 resize-none',
                  v$.pesan.$error ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-[#2D6A4F]'
                ]"
              ></textarea>
              <label :class="['absolute left-0 -top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-4 peer-focus:text-[10px]', v$.pesan.$error ? 'text-red-500 peer-placeholder-shown:text-red-400 peer-focus:text-red-500' : 'text-[#2D6A4F] peer-placeholder-shown:text-gray-400 peer-focus:text-[#2D6A4F]']">Pesan Anda</label>
              <span v-if="v$.pesan.$error" class="absolute -bottom-5 left-0 text-[10px] font-bold text-red-500 italic">{{ v$.pesan.$errors[0].$message }}</span>
            </div>

            <button :disabled="isSubmitting" type="submit" class="group flex items-center justify-between w-full p-2 bg-[#2D6A4F] rounded-3xl overflow-hidden hover:bg-[#1b4332] transition-all duration-300 shadow-xl shadow-[#2D6A4F]/20 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed mt-6">
              <span class="ml-10 text-white font-black uppercase tracking-[0.2em] italic text-sm">
                {{ isSubmitting ? 'MENGIRIM...' : 'KIRIM ASPIRASI' }}
              </span>
              <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center group-hover:translate-x-1 transition-transform">
                <svg v-if="!isSubmitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
                <svg v-else class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
            </button>
            
          </form>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
// Import minLength di sini
import { required, email, minLength, helpers } from '@vuelidate/validators'; 
import type { AspirasiPayload } from '../../types/aspirasi';
import { useAspirasi } from '../../composables/useAspirasi';

const { isSubmitting, submitStatus, submitMessage, kirimAspirasiData } = useAspirasi();

const formData = ref<AspirasiPayload>({
  nama: '',
  email: '',
  subjek: '',
  pesan: ''
});

const toastMessage = ref('');
const isSuccessToast = ref(false);

const showToast = (status: 'success' | 'error', rawMessage: string) => {
  isSuccessToast.value = status === 'success';
  
  if (status === 'error') {
    const err = rawMessage.toLowerCase();
    if (err.includes('sql') || err.includes('exception') || err.includes('typeerror') || err.includes('server error') || err.includes('undefined') || rawMessage.length > 80) {
      toastMessage.value = 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
    } else {
      toastMessage.value = rawMessage || 'Terjadi kesalahan saat memproses permintaan.';
    }
  } else {
    toastMessage.value = rawMessage || 'Data berhasil dikirim.';
  }

  setTimeout(() => {
    toastMessage.value = '';
  }, 10000);
};

const wajibIsi = helpers.withMessage('Bagian ini tidak boleh kosong', required);
const formatEmail = helpers.withMessage('Format email tidak valid (contoh: budi@gmail.com)', email);
// Tambahkan pesan custom untuk minimal karakter
const minimalPesan = helpers.withMessage('Pesan terlalu singkat (minimal 10 karakter)', minLength(10));

const rules = computed(() => ({
  nama: { required: wajibIsi },
  email: { required: wajibIsi, email: formatEmail },
  subjek: { required: wajibIsi },
  // Terapkan aturan minimalPesan ke field pesan
  pesan: { required: wajibIsi, minLength: minimalPesan } 
}));

const v$ = useVuelidate(rules, formData);

const onSubmit = async () => {
  const isFormValid = await v$.value.$validate();
  
  if (!isFormValid) return;

  const isSuccess = await kirimAspirasiData(formData.value);
  
  if (isSuccess) {
    showToast('success', submitMessage.value || 'Aspirasi Anda berhasil dikirim!');
    formData.value = { nama: '', email: '', subjek: '', pesan: '' };
    v$.value.$reset();
  } else {
    showToast('error', submitMessage.value || 'Gagal mengirim aspirasi.');
  }
};
</script>