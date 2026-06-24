<template>
  <div class="bg-white rounded-2xl border border-[#E8D5C4] overflow-hidden shadow-sm">
    <div class="px-6 py-4 bg-gradient-to-r from-[#1B4332]/5 to-transparent border-b border-[#E8D5C4] flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-[#1B4332] flex items-center justify-center shadow-sm">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
      </div>
      <div>
        <h4 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">Konfigurasi AI per Dokumen</h4>
        <p class="text-xs text-[#6B705C]">Pilih AI yang aktif untuk setiap dokumen</p>
      </div>
    </div>

    <div class="p-5">
      <div v-if="activeDocuments.length" class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div v-for="doc in activeDocuments" :key="doc.key" class="flex items-center justify-between p-3 bg-[#FAF6F0] rounded-xl border border-[#E8D5C4]">
          <span class="text-xs font-bold text-[#1B4332]">{{ doc.label }}</span>
          <div class="flex items-center gap-3">
            <label :for="`ocr-${doc.key}`" class="flex items-center gap-1 text-[10px] cursor-pointer">
              <input 
                :id="`ocr-${doc.key}`"
                type="checkbox" 
                :checked="getAi(doc.key, 'ocr')" 
                :aria-label="`Aktifkan OCR untuk ${doc.label}`"
                @change="toggleAi(doc.key, 'ocr')" 
                class="w-3 h-3 rounded border-[#D4A373] text-[#1B4332]" 
              />
              OCR
            </label>
            <label :for="`nlp-${doc.key}`" class="flex items-center gap-1 text-[10px] cursor-pointer">
              <input 
                :id="`nlp-${doc.key}`"
                type="checkbox" 
                :checked="getAi(doc.key, 'nlp_match')" 
                :aria-label="`Aktifkan NLP matching untuk ${doc.label}`"
                @change="toggleAi(doc.key, 'nlp_match')" 
                class="w-3 h-3 rounded border-[#D4A373] text-[#1B4332]" 
              />
              NLP
            </label>
          </div>
        </div>
      </div>
      <p v-else class="text-xs text-[#6B705C] text-center py-4">Pilih dokumen terlebih dahulu di bagian Dokumen Persyaratan.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';

// ✅ Interface untuk type safety
interface AiDocConfig {
  ocr?: boolean;
  nlp_match?: boolean;
}

interface AiConfigData {
  [documentKey: string]: AiDocConfig;
}

interface DocumentItem {
  key: string;
  label: string;
}

const props = defineProps<{ 
  documents: Array<string | { key: string; label: string; active?: boolean }>; 
  existing?: AiConfigData; 
}>();

const emit = defineEmits<{ update: [aiConfig: AiConfigData] }>();

const aiConfig = ref<AiConfigData>(props.existing ? { ...props.existing } : {});

const presetLabels: Record<string, string> = {
  ktp: 'KTP',
  kk: 'Kartu Keluarga',
  foto_rumah_depan: 'Foto Rumah Depan',
  foto_rumah_dalam: 'Foto Rumah Dalam',
  sktm: 'SKTM',
  slip_gaji: 'Slip Gaji',
  rekening_listrik: 'Rekening Listrik',
  rapor: 'Rapor Sekolah',
  surat_usaha: 'Surat Keterangan Usaha',
  foto_usaha: 'Foto Tempat Usaha',
};

const activeDocuments = computed<DocumentItem[]>(() => {
  return props.documents
    .filter((d) => {
      if (typeof d === 'string') return true;
      return d.active !== false;
    })
    .map((d) => {
      if (typeof d === 'string') {
        return { key: d, label: presetLabels[d] || d };
      }
      return { key: d.key, label: d.label };
    });
});

const getAi = (key: string, type: 'ocr' | 'nlp_match'): boolean => {
  return aiConfig.value[key]?.[type] ?? defaultAi(key, type);
};

const toggleAi = (key: string, type: 'ocr' | 'nlp_match') => {
  if (!aiConfig.value[key]) {
    aiConfig.value[key] = {};
  }
  aiConfig.value[key]![type] = !getAi(key, type);
  emit('update', { ...aiConfig.value });
};

const defaultAi = (key: string, type: 'ocr' | 'nlp_match'): boolean => {
  if (type === 'ocr') {
    return !['foto_rumah_depan', 'foto_rumah_dalam', 'foto_usaha'].includes(key);
  }
  if (type === 'nlp_match') {
    return ['ktp', 'kk'].includes(key);
  }
  return false;
};

watch(activeDocuments, () => {
  // Emit default AI config saat dokumen berubah
  const defaultConfig: AiConfigData = {};
  activeDocuments.value.forEach(doc => {
    const ocr = defaultAi(doc.key, 'ocr');
    const nlp = defaultAi(doc.key, 'nlp_match');
    if (ocr || nlp) {
      defaultConfig[doc.key] = {};
      if (ocr) defaultConfig[doc.key].ocr = true;
      if (nlp) defaultConfig[doc.key].nlp_match = true;
    }
  });
  aiConfig.value = defaultConfig;
  emit('update', { ...defaultConfig });
}, { immediate: true });
</script>