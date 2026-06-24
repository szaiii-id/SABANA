<template>
  <div v-if="evidences?.length" class="bg-white rounded-2xl p-4 border border-[#E8D5C4] shadow-sm">
    <h3 class="text-[#8B5E3C] font-black uppercase text-[10px] tracking-[0.15em] mb-3 flex items-center gap-2">
      <div class="w-7 h-7 bg-[#FAF6F0] rounded-lg flex items-center justify-center text-[#1B4332]">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
      </div>
      Dokumen Bukti
      <span class="text-[#6B705C] font-medium text-[9px] ml-auto">Diverifikasi: {{ zoomedCount }}/{{ evidences.length }}</span>
    </h3>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
      <div v-for="ev in evidences" :key="ev.id" class="bg-[#FAF6F0] rounded-2xl overflow-hidden border border-[#E8D5C4] shadow-sm hover:shadow-md transition-shadow">
        
        <div class="relative cursor-pointer" @click="$emit('zoom', ev.image_url, ev.image_type)">
          <img :src="ev.image_url" :alt="ev.image_type" loading="lazy" class="w-full aspect-square object-cover"/>
          <div v-if="ev.ai_result?.success && hasVisibleMatches(ev)" class="absolute top-2 left-2">
            <span :class="aiBadgeClass(ev.ai_result.summary)" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase shadow">{{ aiBadgeText(ev.ai_result.summary) }}</span>
          </div>
          <div v-if="zoomedDocuments[ev.image_type]" class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7"/></svg>
          </div>
        </div>

        <div class="p-3">
          <p class="text-[10px] font-black text-[#1B4332] uppercase mb-2">{{ formatKey(ev.image_type) }}</p>
          
          <div v-if="ev.ai_result?.success && visibleMatches(ev).length" class="space-y-1">
            <div v-for="match in visibleMatches(ev)" :key="match.key" class="flex items-center justify-between py-0.5 border-b border-[#E8D5C4]/30 last:border-0">
              <div class="flex-1 min-w-0">
                <span class="text-[10px] font-bold text-[#1B4332]">{{ match.label }}</span>
                <div class="flex items-center gap-1.5 mt-0.5" v-if="match.ocr_value && match.match_status !== 'tidak_ditemukan'">
                  <span class="text-[8px] text-[#6B705C]">OCR:</span>
                  <span class="text-[9px] font-mono font-bold truncate" :class="match.match_status === 'cocok' ? 'text-green-700' : 'text-amber-600'">{{ match.ocr_value }}</span>
                </div>
                <div class="flex items-center gap-1.5 mt-0.5" v-if="match.input_value && match.match_status !== 'tidak_ditemukan'">
                  <span class="text-[8px] text-[#6B705C]">Input:</span>
                  <span class="text-[9px] font-mono font-bold text-[#1B4332] truncate">{{ match.input_value }}</span>
                </div>
              </div>
              <span :class="matchStatusBadge(match.match_status)" class="px-1.5 py-0.5 rounded-full text-[8px] font-bold uppercase flex-shrink-0 ml-1.5">
                {{ matchStatusLabel(match.match_status) }}
              </span>
            </div>
          </div>
          <div v-else-if="ev.ai_result && !ev.ai_result.success" class="text-[10px] text-red-500">⚠ AI gagal</div>
          <div v-else class="text-[10px] text-[#6B705C]">Tidak mendeteksi kemiripan</div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface AiMatch {
  key: string;
  label: string;
  match_status: string;
  match_score: number;
  input_value?: string;
  ocr_value?: string | null;
}

interface AiSummary {
  cocok: number;
  total: number;
}

interface Evidence {
  id: string;
  image_type: string;
  image_url: string;
  ai_result?: {
    success: boolean;
    matches?: AiMatch[];
    summary?: AiSummary;
  } | null;
}

const props = defineProps<{
  evidences: Evidence[];
  zoomedDocuments: Record<string, boolean>;
}>();

defineEmits<{ zoom: [imageUrl: string, imageType: string] }>();

const zoomedCount = computed(() => Object.values(props.zoomedDocuments).filter(Boolean).length);
const formatKey = (k: string) => k.replace(/_/g, ' ');

// KTP & KK: selalu tampilkan NIK, Nama, KK (identity fields) + semua match lainnya
// Dokumen lain: hanya tampilkan yang cocok & parsial
const visibleMatches = (ev: Evidence): AiMatch[] => {
  if (!ev.ai_result?.matches) return [];
  
  const identityKeys = ['nik', 'full_name', 'family_card_number'];
  const isIdentity = ['ktp', 'kk'].includes(ev.image_type);
  
  return ev.ai_result.matches.filter(m => {
    if (isIdentity && identityKeys.includes(m.key)) return true; // NIK, Nama, KK selalu tampil
    return m.match_status === 'cocok' || m.match_status === 'parsial';
  });
};

const hasVisibleMatches = (ev: Evidence): boolean => visibleMatches(ev).length > 0;

const matchStatusLabel = (status: string): string => {
  const labels: Record<string, string> = { cocok: 'Cocok', parsial: 'Parsial', tidak_cocok: 'Tidak Cocok', tidak_ditemukan: 'Tidak Ada' };
  return labels[status] || status;
};

const matchStatusBadge = (status: string): string => {
  const badges: Record<string, string> = { cocok: 'bg-green-100 text-green-700', parsial: 'bg-amber-100 text-amber-700', tidak_cocok: 'bg-red-100 text-red-700', tidak_ditemukan: 'bg-gray-100 text-gray-400' };
  return badges[status] || 'bg-gray-100 text-gray-400';
};

const aiBadgeText = (s: AiSummary | undefined): string => {
  if (!s) return '';
  if (s.total === 0) return 'Diproses';
  return `${s.cocok}/${s.total} Cocok`;
};

const aiBadgeClass = (s: AiSummary | undefined): string => {
  if (!s) return 'bg-gray-100/80 text-gray-600';
  if (s.total === 0) return 'bg-blue-100/80 text-blue-700';
  const ratio = s.cocok / s.total;
  if (ratio === 1) return 'bg-green-100/80 text-green-700';
  if (ratio >= 0.5) return 'bg-amber-100/80 text-amber-700';
  return 'bg-red-100/80 text-red-700';
};
</script>