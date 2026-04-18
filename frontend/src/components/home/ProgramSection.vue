<template>
  <section id="program" class="py-24 relative z-10">
    <div class="container mx-auto px-6">
      
      <div class="text-center mb-16">
        <h2 class="text-4xl md:text-5xl font-[1000] text-[#2D6A4F] mb-4 uppercase italic tracking-tighter">
          Program Unggulan
        </h2>
        <div class="w-24 h-1.5 bg-[#D4A373] mx-auto rounded-full mb-6"></div>
        <p class="text-gray-700 max-w-2xl mx-auto font-bold text-lg leading-relaxed">
          Inisiatif nyata percepatan penanggulangan kemiskinan dan dukungan kesejahteraan bagi masyarakat kurang mampu di Kalimantan Selatan.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="item in programs" 
          :key="item.title"
          class="group p-8 rounded-[2.5rem] bg-white/40 backdrop-blur-md border border-white/40 hover:border-[#D4A373] hover:bg-white/60 transition-all duration-500 shadow-xl shadow-black/5 flex flex-col h-full"
        >
          <div class="w-16 h-16 bg-[#2D6A4F] rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-all duration-500">
            <span v-html="item.iconSvg" class="w-8 h-8 text-white"></span>
          </div>

          <h3 class="text-2xl font-[1000] text-gray-800 mb-4 tracking-tight">{{ item.title }}</h3>
          
          <ul class="space-y-4 flex-grow">
            <li v-for="sub in item.subs" :key="sub" class="flex items-start gap-3">
              <div class="mt-1.5 w-2 h-2 rounded-full bg-[#D4A373] shrink-0 shadow-sm"></div>
              <p class="text-[15px] text-gray-700 font-bold leading-relaxed">
                {{ sub }}
              </p>
            </li>
          </ul>

          <div class="mt-8 w-12 h-1 bg-[#D4A373]/20 group-hover:w-full transition-all duration-700 rounded-full"></div>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup lang="ts">
const programs = [
  {
    title: 'Bantuan Keluarga',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
    subs: [
      'Bantuan Pangan Langsung (Sembako) rutin untuk keluarga prasejahtera.',
      'Dukungan ekonomi khusus masyarakat di bawah garis kemiskinan.'
    ]
  },
  {
    title: 'Beasiswa Banua',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>',
    subs: [
      'Beasiswa Prestasi bagi pelajar dari keluarga kurang mampu.',
      'Bantuan perlengkapan dasar sekolah (Seragam & Alat Tulis).'
    ]
  },
  {
    title: 'Banua Sehat',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>',
    subs: [
      'Subsidi iuran JKN/BPJS Kesehatan bagi warga prasejahtera.',
      'Program nutrisi tambahan balita untuk pencegahan stunting.'
    ]
  },
  {
    title: 'Pemberdayaan UMKM',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/vue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
    subs: [
      'Bantuan modal usaha mikro dan alat kerja bagi kepala keluarga.',
      'Pelatihan keterampilan mandiri untuk penguatan ekonomi lokal.'
    ]
  },
  {
    title: 'Lansia Berdaya',
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
    subs: [
      'Paket sembako khusus bulanan bagi lansia non-produktif.',
      'Layanan kunjungan kesehatan rumah (Home Care) rutin.'
    ]
  },
  {
    title: 'Banua Peduli',
    // Ikon Gotong Royong (Tiga Orang)
    iconSvg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>',
    subs: [
      'Bantuan tanggap darurat musibah kebakaran pemukiman.',
      'Rehabilitasi sarana dasar pasca musibah banjir.'
    ]
  }
];
</script>

<style scoped>
/* Transisi halus untuk backdrop-blur */
.group {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>