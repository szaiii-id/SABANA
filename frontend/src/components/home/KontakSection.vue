<template>
  <section id="kontak" class="py-24 relative z-10 overflow-hidden">
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
              <p class="text-xl font-black text-gray-800 break-all leading-none">halo@sabana.kalsel.go.id</p>
              <p class="text-xs font-bold text-[#D4A373] mt-2 uppercase tracking-widest">Korespondensi Resmi</p>
            </div>
          </div>
        </div>

        <div class="lg:col-span-7 bg-white/60 backdrop-blur-2xl p-10 md:p-14 rounded-[4rem] border border-white/80 shadow-2xl relative">
          <form @submit.prevent="kirimAspirasi" class="space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
              <div class="relative">
                <input v-model.trim="formData.nama" type="text" required placeholder=" " class="peer w-full bg-transparent border-b-2 border-gray-300 py-2 outline-none focus:border-[#2D6A4F] transition-all font-bold text-gray-800"/>
                <label class="absolute left-0 -top-4 text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-4 peer-focus:text-[10px] peer-focus:text-[#2D6A4F]">Nama Lengkap</label>
              </div>
              <div class="relative">
                <input v-model.trim="formData.email" type="email" required placeholder=" " class="peer w-full bg-transparent border-b-2 border-gray-300 py-2 outline-none focus:border-[#2D6A4F] transition-all font-bold text-gray-800"/>
                <label class="absolute left-0 -top-4 text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-4 peer-focus:text-[10px] peer-focus:text-[#2D6A4F]">Alamat Email</label>
              </div>
            </div>

            <div class="relative">
              <select v-model="formData.subjek" required class="peer w-full bg-transparent border-b-2 border-gray-300 py-2 outline-none focus:border-[#2D6A4F] transition-all font-black text-gray-700 appearance-none uppercase text-xs">
                <option disabled value="">Pilih Kategori Aspirasi</option>
                <option value="Kendala Teknis Akses">Kendala Teknis Akses</option>
                <option value="Laporan Penyaluran">Laporan Penyaluran</option>
                <option value="Saran Pengembangan">Saran Pengembangan</option>
              </select>
              <label class="absolute left-0 -top-4 text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest">Kategori</label>
            </div>

            <div class="relative">
              <textarea v-model.trim="formData.pesan" required rows="3" placeholder=" " class="peer w-full bg-transparent border-b-2 border-gray-300 py-2 outline-none focus:border-[#2D6A4F] transition-all font-bold text-gray-800 resize-none"></textarea>
              <label class="absolute left-0 -top-4 text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-4 peer-focus:text-[10px] peer-focus:text-[#2D6A4F]">Pesan Anda</label>
            </div>

            <button :disabled="isSubmitting" type="submit" class="group flex items-center justify-between w-full p-2 bg-[#2D6A4F] rounded-3xl overflow-hidden hover:bg-[#1b4332] transition-all duration-300 shadow-xl shadow-[#2D6A4F]/20 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
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

            <div v-if="submitStatus" :class="submitStatus === 'success' ? 'text-green-600' : 'text-red-600'" class="text-xs font-black uppercase tracking-widest text-center mt-4 transition-all duration-300">
              {{ submitMessage }}
            </div>
            
          </form>
        </div>

      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AspirasiService, { type AspirasiPayload } from '../../services/AspirasiService';

const formData = ref<AspirasiPayload>({
  nama: '',
  email: '',
  subjek: '',
  pesan: ''
});

const isSubmitting = ref(false);
const submitStatus = ref<'success' | 'error' | ''>('');
const submitMessage = ref('');

const kirimAspirasi = async () => {
  isSubmitting.value = true;
  submitStatus.value = '';
  
  try {
    const response = await AspirasiService.kirimAspirasi(formData.value);
    
    submitStatus.value = 'success';
    submitMessage.value = response.data.message || 'Aspirasi berhasil dikirim ke sistem SABANA';
    
    formData.value = {
      nama: '',
      email: '',
      subjek: '',
      pesan: ''
    };
    
  } catch (error: any) {
    submitStatus.value = 'error';
    
    if (error.response?.data?.message) {
      submitMessage.value = error.response.data.message;
    } else {
      submitMessage.value = 'Gagal mengirim pesan. Pastikan server terhubung.';
    }
  } finally {
    isSubmitting.value = false;
    
    setTimeout(() => {
      submitStatus.value = '';
    }, 5000);
  }
};
</script>