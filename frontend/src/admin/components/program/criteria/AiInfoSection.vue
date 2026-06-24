<template>
  <section 
    class="bg-[#F0FDF4] border-2 border-[#1B4332]/20 rounded-2xl p-5"
    role="complementary" 
    aria-labelledby="ai-guide-heading"
  >
    <h4 id="ai-guide-heading" class="text-xs font-black text-[#1B4332] uppercase tracking-wider mb-3">Panduan AI</h4>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-[10px]">
      <div class="bg-white rounded-xl p-3 border border-[#E8D5C4]">
        <p class="font-bold text-[#1B4332] mb-1">OCR - Pembaca Teks</p>
        <p class="text-[#6B705C] leading-relaxed">Membaca teks dari gambar dokumen: KTP, KK, slip gaji, struk listrik, rapor, SKTM, surat keterangan.</p>
        <p class="text-[#6B705C] mt-1"><span class="font-bold">Centang:</span> Dokumen yang perlu dibaca teksnya.</p>
      </div>
      
      <div class="bg-white rounded-xl p-3 border border-[#E8D5C4]">
        <p class="font-bold text-[#1B4332] mb-1">NLP Match - Pencocok Data</p>
        <p class="text-[#6B705C] leading-relaxed">Mencocokkan data antar dokumen: NIK KTP dengan NIK KK, Nama KTP dengan Slip Gaji.</p>
        <p class="text-[#6B705C] mt-1"><span class="font-bold">Centang:</span> Untuk KTP & KK (wajib), dokumen lain (opsional).</p>
      </div>
      
      <div class="bg-white rounded-xl p-3 border border-[#E8D5C4]">
        <p class="font-bold text-[#1B4332] mb-1">Anomaly Detection</p>
        <p class="text-[#6B705C] leading-relaxed">Mendeteksi kecurangan: double submit, foto mirip, data tidak wajar, lonjakan pendaftar.</p>
        <p class="text-[#6B705C] mt-1"><span class="font-bold text-green-700">Selalu aktif</span> - tidak bisa dimatikan.</p>
      </div>
    </div>
  </section>
</template>