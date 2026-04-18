<template>
  <section id="panduan" class="py-32 relative z-10 bg-[#fdfaf5] min-h-screen flex items-center">
    <div class="container mx-auto px-6">
      
      <div class="text-center mb-16">
        <span class="text-sm font-black text-[#D4A373] uppercase tracking-[0.3em] mb-4 block">
          Transparansi Proses
        </span>
        <h2 class="text-5xl md:text-7xl font-[1000] text-[#2D6A4F] uppercase tracking-tighter leading-none mb-6 drop-shadow-sm">
          ALUR PENGAJUAN
        </h2>
        <p class="text-gray-600 font-bold text-lg max-w-2xl mx-auto">
          Pilih kategori bantuan di bawah ini untuk melihat tahapan verifikasi yang harus Anda lalui secara transparan
        </p>
      </div>

      <div class="flex flex-wrap justify-center gap-4 mb-16">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'px-8 py-4 rounded-[2rem] font-black uppercase tracking-widest text-sm transition-all duration-500 border-2 shadow-sm',
            activeTab === tab.id 
              ? 'bg-[#2D6A4F] text-white border-[#2D6A4F] scale-105 shadow-[#2D6A4F]/30 shadow-xl' 
              : 'bg-white text-gray-500 border-gray-200 hover:border-[#2D6A4F]/50 hover:text-[#2D6A4F]'
          ]"
        >
          {{ tab.name }}
        </button>
      </div>

      <div class="max-w-6xl mx-auto relative min-h-[400px]">
        <transition name="fade-slide" mode="out-in">
          
          <div :key="activeTab" class="bg-white/60 backdrop-blur-2xl p-10 md:p-16 rounded-[4rem] border border-white/80 shadow-2xl">
            
            <div class="mb-12 text-center md:text-left">
              <span class="inline-block px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest mb-4" :class="currentPath.badgeClass">
                {{ currentPath.category }}
              </span>
              <h3 class="text-4xl font-[1000] uppercase tracking-tighter mb-4" :class="currentPath.titleClass">
                {{ currentPath.title }}
              </h3>
              <p class="text-gray-600 font-bold text-lg max-w-3xl">
                {{ currentPath.description }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div 
                v-for="(step, index) in currentPath.steps" 
                :key="index"
                class="p-8 rounded-[2.5rem] bg-white border border-gray-100 shadow-lg hover:-translate-y-2 transition-transform duration-300 group"
              >
                <div class="flex items-center gap-6 mb-6">
                  <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-[1000] text-xl shadow-md group-hover:scale-110 transition-transform" :class="currentPath.dotClass">
                    0{{ index + 1 }}
                  </div>
                  <h4 class="text-xl font-black text-gray-800 uppercase tracking-tight leading-tight">
                    {{ step.name }}
                  </h4>
                </div>
                <p class="text-sm font-bold text-gray-500 leading-relaxed">
                  {{ step.detail }}
                </p>
              </div>
            </div>

          </div>

        </transition>
      </div>

    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const activeTab = ref('reguler');

const tabs = [
  { id: 'reguler', name: 'Jalur Reguler' },
  { id: 'pendidikan', name: 'Jalur Pendidikan' },
  { id: 'darurat', name: 'Jalur Darurat' }
];

const applicationPaths = {
  reguler: {
    category: 'Jalur Utama',
    title: 'Bantuan Reguler Terpadu',
    description: 'Rangkaian verifikasi standar untuk bantuan Sembako Lansia dan Jaminan Kesehatan Dasar',
    badgeClass: 'bg-[#2D6A4F]/10 text-[#2D6A4F]',
    titleClass: 'text-[#2D6A4F]',
    dotClass: 'bg-[#2D6A4F] text-white',
    steps: [
      { name: 'Verifikasi Identitas', detail: 'Sistem mencocokkan NIK dan Nomor KK Anda secara otomatis dengan basis data kependudukan' },
      { name: 'Formulir Ekonomi', detail: 'Pendataan mandiri terkait tingkat penghasilan daya listrik dan jumlah tanggungan keluarga' },
      { name: 'Bukti Hunian', detail: 'Pengambilan gambar bagian depan rumah dan bukti tagihan listrik langsung melalui perangkat Anda' },
      { name: 'Validasi Sistem', detail: 'Algoritma menghitung skor kelayakan tanpa campur tangan manusia untuk hasil yang adil' }
    ]
  },
  pendidikan: {
    category: 'Prestasi Banua',
    title: 'Beasiswa Pendidikan Banua',
    description: 'Proses khusus pengajuan Beasiswa bagi pelajar dan mahasiswa yang memiliki prestasi akademik unggul',
    badgeClass: 'bg-[#D4A373]/10 text-[#D4A373]',
    titleClass: 'text-[#D4A373]',
    dotClass: 'bg-[#D4A373] text-white',
    steps: [
      { name: 'Data Akademik', detail: 'Pengisian nilai rapor atau Indeks Prestasi Kumulatif terakhir secara valid dan jujur' },
      { name: 'Sertifikat Prestasi', detail: 'Melampirkan bukti piagam penghargaan atau sertifikat lomba tingkat daerah hingga nasional' },
      { name: 'Status Aktif', detail: 'Dokumen legalisasi dari pihak sekolah atau kampus yang menyatakan status siswa masih aktif' },
      { name: 'Penyaluran Dana', detail: 'Bantuan dikirim langsung ke rekening Bank Kalsel atas nama penerima beasiswa terkait' }
    ]
  },
  darurat: {
    category: 'Respons Cepat',
    title: 'Penanganan Darurat Musibah',
    description: 'Penanganan instan khusus warga terdampak musibah kebakaran atau banjir parah yang membutuhkan aksi seketika',
    badgeClass: 'bg-red-100 text-red-600',
    titleClass: 'text-red-600',
    dotClass: 'bg-red-600 text-white',
    steps: [
      { name: 'Laporan Lokasi', detail: 'Input koordinat lokasi musibah agar petugas terkait segera meluncur ke tempat kejadian' },
      { name: 'Bukti Dokumentasi', detail: 'Foto kondisi terkini di lapangan sebagai bukti awal kejadian darurat yang dialami warga' },
      { name: 'Validasi Lapangan', detail: 'Verifikasi dilakukan langsung di tempat kejadian perkara setelah bantuan tahap pertama turun' },
      { name: 'Distribusi Bantuan', detail: 'Penyerahan logistik pakaian dan kebutuhan pokok secara langsung kepada para korban' }
    ]
  }
};

const currentPath = computed(() => {
  return applicationPaths[activeTab.value as keyof typeof applicationPaths];
});
</script>

<style scoped>
/* Transisi halus saat berganti tab */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>