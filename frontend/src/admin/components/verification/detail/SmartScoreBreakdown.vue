<template>
  <!-- Modal -->
  <Transition name="fade">
    <div v-if="modelValue" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('update:modelValue', false)"></div>
      <div class="relative bg-white rounded-[3rem] w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl border-2 border-[#E8D5C4]">
        
        <!-- Header -->
        <div class="sticky top-0 bg-white rounded-t-[3rem] p-8 border-b-2 border-[#E8D5C4] flex items-center justify-between z-10">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-[#FAF6F0] rounded-2xl flex items-center justify-center text-[#1B4332]">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <div>
              <h3 class="text-[#8B5E3C] font-black uppercase text-xs tracking-[0.2em]">Rincian Skor SMART</h3>
              <p class="text-[11px] text-[#6B705C] flex items-center gap-1 mt-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Skor berdasarkan standar nilai ideal
              </p>
            </div>
          </div>
          <button @click="$emit('update:modelValue', false)" class="w-12 h-12 bg-[#FAF6F0] hover:bg-[#E8D5C4] rounded-full flex items-center justify-center transition-all">
            <svg class="w-6 h-6 text-[#8B5E3C]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-8 space-y-6">
          
          <!-- Skor Akhir -->
          <div class="text-center py-6 bg-[#FAF6F0] rounded-[2rem]">
            <p class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-wider mb-2">Skor Akhir</p>
            <p class="text-5xl font-black" :class="scoreColor">{{ displayScore }}</p>
            <div class="flex items-center justify-center gap-3 mt-3">
              <span :class="recommendationBadgeClass" class="px-4 py-1.5 rounded-full text-[10px] font-bold uppercase">{{ recommendationText }}</span>
            </div>
          </div>

          <!-- Tabel Detail -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="text-[#8B7355] uppercase tracking-wider border-b-2 border-[#E8D5C4]">
                  <th class="py-3 font-black">Kriteria</th>
                  <th class="py-3 font-black text-center">Sifat</th>
                  <th class="py-3 font-black text-right">Nilai Input</th>
                  <th class="py-3 font-black text-right">Nilai Ideal</th>
                  <th class="py-3 font-black text-right">Normalisasi</th>
                  <th class="py-3 font-black text-right">Bobot</th>
                  <th class="py-3 font-black text-right">Skor</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in breakdown" :key="item.key" class="border-b border-[#E8D5C4]/30 hover:bg-[#FAF6F0]/50">
                  <td class="py-3.5 font-bold text-[#1B4332]">{{ item.label }}</td>
                  <td class="py-3.5 text-center">
                    <span :class="item.sifat === 'benefit' ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'" class="px-3 py-1 rounded-full font-bold text-[10px] uppercase">
                      {{ item.sifat === 'benefit' ? 'Benefit ↑' : 'Cost ↓' }}
                    </span>
                  </td>
                  <td class="py-3.5 text-right font-mono text-[#1B4332] font-bold">
                    {{ item.displayValue }}
                    <span v-if="item.type === 'select'" class="text-[9px] text-[#6B705C] block">Score: {{ item.value }}</span>
                  </td>
                  <td class="py-3.5 text-right font-mono text-[#1B4332] font-bold">{{ formatNumber(item.ideal) }}</td>
                  <td class="py-3.5 text-right font-mono font-bold">{{ formatDecimal(item.normalized) }}</td>
                  <td class="py-3.5 text-right font-mono font-bold">{{ item.weight }}%</td>
                  <td class="py-3.5 text-right font-mono font-bold text-[#1B4332] text-sm">{{ formatDecimal(item.score) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-[#FAF6F0] font-black">
                  <td colspan="6" class="py-4 text-right text-[#1B4332] uppercase text-[10px]">TOTAL SKOR</td>
                  <td class="py-4 text-right font-mono text-[#1B4332] text-lg">{{ displayScore }}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Info Tambahan -->
          <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100">
            <p class="text-xs text-blue-700 leading-relaxed">
              <strong>Simple Additive Weighting (SMART)</strong> — Nilai setiap kriteria dinormalisasi berdasarkan <strong>nilai ideal</strong> yang ditetapkan admin. Skor <strong>tidak berubah</strong> saat ada pengajuan baru, hanya berubah jika admin mengubah standar nilai ideal.
            </p>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// ===== INTERFACES =====
interface SmartCriteria {
  key: string;
  label: string;
  type?: string;
  sifat?: string;
  weight?: number;
  ideal_value?: number;
  options?: Array<{ value: string; score: number }>;
}

interface SmartItem {
  key: string;
  label: string;
  type: string;
  sifat: 'benefit' | 'cost';
  value: number;
  displayValue: string;
  ideal: number;
  normalized: number;
  weight: number;
  score: number;
}

// ===== PROPS =====
const props = defineProps<{
  modelValue: boolean;
  criteria?: SmartCriteria[];
  submissionData?: Record<string, unknown>;
  smartScore?: number | null;
  recommendation?: string;
}>();

defineEmits<{ 'update:modelValue': [value: boolean] }>();

// ===== COMPUTED =====
const breakdown = computed<SmartItem[]>(() => {
  if (!props.criteria || !props.submissionData) return [];
  
  return props.criteria
    .filter(c => {
      const type = c.type || 'number';
      const sifat = c.sifat || 'benefit';
      const weight = c.weight ?? 0;
      
      // Hanya tipe numerik/select
      if (!['number', 'decimal', 'currency', 'select'].includes(type)) return false;
      // Hanya benefit/cost
      if (!['benefit', 'cost'].includes(sifat)) return false;
      // Harus punya weight
      if (weight <= 0) return false;
      
      return true;
    })
    .map(c => {
      const raw = props.submissionData?.[c.key];
      const type = c.type || 'number';
      let value = 0;
      let displayValue = String(raw ?? '0');

      if (type === 'select' && c.options) {
        const option = c.options.find(o => o.value === String(raw));
        value = option?.score ?? 0;
        displayValue = String(raw ?? '0');
      } else if (type === 'currency') {
        value = Number(raw || 0);
        displayValue = 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
      } else {
        value = Number(raw || 0);
        displayValue = new Intl.NumberFormat('id-ID').format(value);
      }

      const sifat = (c.sifat || 'benefit') as 'benefit' | 'cost';
      const weight = c.weight ?? 0;

      const ideal = type === 'select'
        ? (c.ideal_value ?? Math.max(...(c.options?.map(o => o.score) ?? [100])))
        : (c.ideal_value ?? 1);

      let normalized = 0;
      if (type === 'select') {
        normalized = ideal > 0 ? Math.min(value / ideal, 1.0) : 0;
      } else if (sifat === 'benefit') {
        normalized = ideal > 0 ? Math.min(value / ideal, 1.0) : 0;
      } else {
        normalized = ideal > 0 ? Math.max((ideal - value) / ideal, 0.0) : 0;
      }

      return {
        key: c.key, label: c.label, type, sifat,
        value, displayValue, ideal,
        normalized, weight,
        score: normalized * (weight / 100),
      };
    });
});

const displayScore = computed(() => {
  const score = props.smartScore ?? 0;
  return (score * 100).toFixed(1);
});

const recommendationText = computed(() => props.recommendation || 'Belum Dinilai');
const recommendationBadgeClass = computed(() => {
  if (!props.smartScore) return 'bg-gray-100 text-gray-500';
  if (props.smartScore >= 0.70) return 'bg-green-100 text-green-700';
  if (props.smartScore >= 0.50) return 'bg-yellow-100 text-yellow-700';
  if (props.smartScore >= 0.30) return 'bg-orange-100 text-orange-700';
  return 'bg-red-100 text-red-700';
});
const scoreColor = computed(() => {
  if (!props.smartScore) return 'text-gray-400';
  if (props.smartScore >= 0.70) return 'text-green-600';
  if (props.smartScore >= 0.50) return 'text-yellow-600';
  if (props.smartScore >= 0.30) return 'text-orange-600';
  return 'text-red-600';
});

const formatNumber = (n: number | null | undefined): string => {
  if (n === null || n === undefined || isNaN(n)) return '0';
  return new Intl.NumberFormat('id-ID').format(n);
};
const formatDecimal = (n: number | null | undefined): string => {
  if (n === null || n === undefined || isNaN(n)) return '0.0000';
  return n.toFixed(4);
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>