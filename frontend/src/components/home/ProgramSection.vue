<template>
  <section id="program" class="py-32 relative z-10 bg-gradient-to-b from-white to-[#f8fcf9]">
    <div class="container mx-auto px-6">
      
      <div class="flex flex-col md:flex-row justify-between items-end mb-20 gap-8">
        <div class="max-w-3xl">
          <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-[#2D6A4F]/10 border border-[#2D6A4F]/20 mb-6">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-[10px] font-black text-[#2D6A4F] uppercase tracking-widest">Katalog Bantuan Tervalidasi</span>
          </div>
          <h2 class="text-5xl md:text-6xl font-[1000] text-gray-900 uppercase tracking-tighter leading-[0.9]">
            Program Bantuan <br/> <span class="text-[#D4A373] italic">Tepat Sasaran.</span>
          </h2>
        </div>
        <p class="text-gray-600 font-bold text-lg md:text-right max-w-md">
          Enam pilar kesejahteraan SABANA. Setiap program terintegrasi dengan sistem validasi objektif untuk memastikan keadilan bagi seluruh Anak Banua.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="item in programs" 
          :key="item.title"
          class="group relative bg-white rounded-[2rem] p-8 border border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_40px_rgb(45,106,79,0.08)] hover:-translate-y-1 transition-all duration-500 flex flex-col h-full overflow-hidden"
        >
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-[#D4A373]/10 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

          <div class="flex justify-between items-start mb-8 relative z-10">
            <div class="w-16 h-16 bg-[#2D6A4F] rounded-[1.25rem] flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
              <span v-html="item.iconSvg" class="w-8 h-8 text-white"></span>
            </div>
            <div class="px-3 py-1 bg-gray-100 border border-gray-200 rounded-full">
              <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">{{ item.badge }}</span>
            </div>
          </div>

          <h3 class="text-2xl font-black text-gray-800 mb-4 tracking-tight group-hover:text-[#2D6A4F] transition-colors">{{ item.title }}</h3>
          
          <ul class="space-y-3 flex-grow mb-8 relative z-10">
            <li v-for="sub in item.subs" :key="sub" class="flex items-start gap-3">
              <div class="mt-2 w-1.5 h-1.5 rounded-full bg-[#D4A373] shrink-0"></div>
              <p class="text-sm font-bold text-gray-600 leading-relaxed">
                {{ sub }}
              </p>
            </li>
          </ul>

          <div class="pt-6 border-t border-gray-100 relative z-10 mt-auto">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Parameter Validasi Sistem:</p>
            <div class="flex flex-wrap gap-2">
              <span v-for="param in item.parameters" :key="param" class="px-2.5 py-1 bg-[#2D6A4F]/5 text-[#2D6A4F] text-[10px] font-bold rounded-md border border-[#2D6A4F]/10">
                {{ param }}
              </span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup lang="ts">
const programs = [
  {
    title: 'Bantuan Keluarga',
    badge: 'Siklus 6 Bulan',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
    subs: [
      'Bantuan Pangan Langsung (Sembako) rutin untuk keluarga prasejahtera.',
      'Dukungan ekonomi khusus masyarakat di bawah garis kemiskinan.'
    ],
    parameters: ['Penghasilan', 'Tanggungan', 'Daya Listrik']
  },
  {
    title: 'Beasiswa Banua',
    badge: 'Validasi Rapor',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>',
    subs: [
      'Beasiswa Prestasi bagi pelajar dari keluarga kurang mampu.',
      'Bantuan perlengkapan dasar sekolah (Seragam & Alat Tulis).'
    ],
    parameters: ['Nilai Akademik', 'Status Aktif', 'Ekonomi Orang Tua']
  },
  {
    title: 'Banua Sehat',
    badge: 'Verifikasi Medis',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>',
    subs: [
      'Subsidi iuran JKN/BPJS Kesehatan bagi warga prasejahtera.',
      'Program nutrisi tambahan balita untuk pencegahan stunting.'
    ],
    parameters: ['Data Stunting', 'Riwayat JKN', 'Penghasilan']
  },
  {
    title: 'Pemberdayaan UMKM',
    badge: 'Validasi Usaha',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/vue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
    subs: [
      'Bantuan modal usaha mikro dan alat kerja bagi kepala keluarga.',
      'Pelatihan keterampilan mandiri untuk penguatan ekonomi lokal.'
    ],
    parameters: ['Foto Tempat Usaha', 'Lama Usaha', 'Pendapatan Harian']
  },
  {
    title: 'Lansia Berdaya',
    badge: 'Validasi NIK',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
    subs: [
      'Paket sembako khusus bulanan bagi lansia non-produktif.',
      'Layanan kunjungan kesehatan rumah (Home Care) rutin.'
    ],
    parameters: ['Usia > 60 Tahun', 'Status Tanggungan', 'Kondisi Fisik']
  },
  {
    title: 'Banua Peduli',
    badge: 'Respons Cepat',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>',
    subs: [
      'Bantuan tanggap darurat musibah kebakaran pemukiman.',
      'Rehabilitasi sarana dasar pasca musibah banjir.'
    ],
    parameters: ['Koordinat Lokasi', 'Foto Kejadian', 'Verifikasi Lapangan']
  }
];
</script>