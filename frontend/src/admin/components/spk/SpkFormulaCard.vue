<!-- src/admin/components/spk/SpkFormulaCard.vue -->
<template>
  <div class="bg-white border border-[#E8D5C4] rounded-2xl overflow-hidden shadow-sm mb-5">
    <div class="px-6 py-4 bg-[#FAF6F0] border-b border-[#E8D5C4]">
      <h3 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">Rumus SMART</h3>
    </div>
    <div class="p-5">
      <div class="text-xs text-[#6B705C] space-y-2">
        <p><strong>Benefit:</strong> r = X / Ideal</p>
        <p><strong>Cost:</strong> r = (Ideal - X) / Ideal</p>
        <p><strong>Skor:</strong> V = (W1 x r1) + (W2 x r2) + (W3 x r3)</p>
        <p class="text-[10px] mt-2">Nilai akhir ditampilkan dalam persentase (0-100) dan desimal (0.0000).</p>
      </div>
    </div>
  </div>
</template>