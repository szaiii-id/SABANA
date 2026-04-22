<template>
  <section id="beranda" class="relative min-h-screen flex items-center pt-20 pb-10 overflow-hidden">
    <div class="container mx-auto px-6 relative z-10">
      
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
        
        <div class="lg:col-span-7 flex flex-col items-start text-left">
          
          <div class="inline-flex items-center gap-3 px-5 py-2.5 rounded-full bg-[#2D6A4F]/10 border border-[#2D6A4F]/20 mb-8 backdrop-blur-sm shadow-sm hover:bg-[#2D6A4F]/20 transition-colors cursor-default">
            <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse shadow-[0_0_8px_rgba(34,197,94,0.8)]"></span>
            <span class="text-xs font-black text-[#2D6A4F] uppercase tracking-[0.2em]">Portal Resmi Terintegrasi</span>
          </div>

          <h1 class="text-[4rem] md:text-[6rem] lg:text-[7rem] font-[1000] text-[#2D6A4F] uppercase tracking-tighter leading-[0.85] mb-6 drop-shadow-sm">
            Keadilan <br/>
            <span class="text-[#D4A373] italic">Untuk Banua.</span>
          </h1>

          <div class="border-l-4 border-[#D4A373] pl-6 mb-10">
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tight mb-2">
              Sarana Bantuan Anak Banua
            </h2>
            <p class="text-lg font-bold text-gray-600 leading-relaxed max-w-xl">
              Platform digital mandiri untuk penyaluran bantuan sosial di Kalimantan Selatan. Transparan, objektif, dan tepat sasaran tanpa perantara.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
            <router-link 
            :to="{ name: 'register' }" 
            class="group relative flex items-center justify-center gap-4 w-full sm:w-auto px-10 py-5 bg-[#2D6A4F] text-white rounded-full overflow-hidden shadow-2xl shadow-[#2D6A4F]/30 hover:bg-[#1b4332] hover:-translate-y-1 transition-all duration-300 active:scale-95"
          >
            <span class="font-black uppercase tracking-widest text-sm italic z-10">Daftar Sekarang</span>
            </router-link>

            <a 
              href="#panduan" 
              @click.prevent="scrollToSection('panduan')"
              class="flex items-center justify-center w-full sm:w-auto px-10 py-5 bg-white/40 backdrop-blur-md text-[#2D6A4F] font-black uppercase tracking-widest text-sm rounded-full border border-[#2D6A4F]/20 hover:bg-white hover:border-[#2D6A4F]/50 hover:-translate-y-1 transition-all duration-300 shadow-sm"
            >
              Pelajari Alur
            </a>
          </div>
        </div>

        <div class="lg:col-span-5 relative hidden md:block h-[500px]">
          
          <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 border-2 border-dashed border-[#2D6A4F]/20 rounded-full animate-[spin_60s_linear_infinite] -z-10"></div>
          <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 border border-[#D4A373]/30 rounded-full animate-[spin_40s_linear_infinite_reverse] -z-10"></div>

          <div class="absolute top-10 right-0 w-72 bg-white/60 backdrop-blur-2xl p-6 rounded-3xl border border-white/80 shadow-2xl z-20 hover:-translate-y-2 transition-transform duration-500">
            <div class="flex items-center gap-4 mb-3">
              <div class="w-12 h-12 bg-[#2D6A4F] rounded-xl flex items-center justify-center text-white text-xl shadow-lg">🛡️</div>
              <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Enkripsi Sistem</p>
                <p class="text-sm font-black text-[#2D6A4F] uppercase">Privasi NIK Aman</p>
              </div>
            </div>
            <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
              <div class="w-full h-full bg-[#2D6A4F]"></div>
            </div>
          </div>

          <div class="absolute top-1/3 -left-10 w-80 bg-[#2D6A4F] text-white p-6 rounded-3xl shadow-2xl shadow-[#2D6A4F]/30 z-30 hover:-translate-y-2 transition-transform duration-500">
            <div class="flex justify-between items-center mb-4">
              <span class="text-xs font-black uppercase tracking-[0.2em] opacity-70">Status Algoritma</span>
              <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
              </span>
            </div>
            <p class="text-xl font-black italic tracking-tight mb-1">Verifikasi Objektif</p>
            <p class="text-xs font-bold opacity-70">Menghitung kelayakan secara otomatis.</p>
          </div>

          <div class="absolute bottom-10 right-10 w-64 bg-white/60 backdrop-blur-2xl p-6 rounded-3xl border border-white/80 shadow-2xl z-10 hover:translate-y-2 transition-transform duration-500">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-[#D4A373] rounded-xl flex items-center justify-center text-white text-xl shadow-lg">🎯</div>
              <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Akurasi Data</p>
                <p class="text-sm font-black text-[#D4A373] uppercase">Tepat Sasaran</p>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// Fungsi untuk smooth scroll ke Panduan
const scrollToSection = (id: string) => {
  const element = document.getElementById(id);
  if (element) {
    const offset = 100;
    const bodyRect = document.body.getBoundingClientRect().top;
    const elementRect = element.getBoundingClientRect().top;
    const elementPosition = elementRect - bodyRect;
    const offsetPosition = elementPosition - offset;

    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    });
  }
};
</script>

<style scoped>
@keyframes shimmer {
  100% { transform: translateX(100%); }
}
</style>