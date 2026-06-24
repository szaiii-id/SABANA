<template>
  <div v-if="anomalies?.length" class="bg-red-50 rounded-[2.5rem] p-6 md:p-8 border-2 border-red-200">
    <h3 class="text-red-700 font-black uppercase text-[10px] tracking-[0.2em] mb-6 flex items-center gap-3">
      <div class="w-8 h-8 bg-red-100 rounded-xl flex items-center justify-center text-red-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
      </div>
      Aktivitas Mencurigakan
      <span class="text-[10px] font-bold text-red-500 bg-red-100 px-3 py-1 rounded-full ml-auto">{{ anomalies.length }} terdeteksi</span>
    </h3>
    
    <!-- ✅ Scrollable list -->
    <div class="max-h-80 overflow-y-auto space-y-3 pr-1">
      <div v-for="(anomaly, i) in anomalies" :key="i" class="flex items-start gap-3 p-4 bg-white rounded-2xl border border-red-100">
        <div :class="severityBadgeClass(anomaly.severity)" class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider flex-shrink-0">
          {{ severityLabel(anomaly.severity) }}
        </div>
        <p class="text-sm font-bold text-red-800 leading-relaxed">{{ anomaly.message }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ anomalies?: any[] }>();

const severityLabel = (severity: string) => ({ high: 'Tinggi', medium: 'Sedang', low: 'Rendah' }[severity] || severity);
const severityBadgeClass = (severity: string) => ({ high: 'bg-red-100 text-red-700', medium: 'bg-amber-100 text-amber-700', low: 'bg-blue-100 text-blue-700' }[severity] || 'bg-gray-100 text-gray-700');
</script>

<style scoped>
.max-h-80::-webkit-scrollbar {
  width: 5px;
}
.max-h-80::-webkit-scrollbar-track {
  background: transparent;
}
.max-h-80::-webkit-scrollbar-thumb {
  background: #fecaca;
  border-radius: 10px;
}
</style>