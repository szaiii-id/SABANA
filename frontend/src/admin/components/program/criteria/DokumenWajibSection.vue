<template>
  <div class="bg-white rounded-2xl border border-[#E8D5C4] overflow-hidden shadow-sm">
    <div class="px-6 py-4 bg-gradient-to-r from-[#1B4332]/5 to-transparent border-b border-[#E8D5C4] flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#1B4332] flex items-center justify-center shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h4 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">Dokumen Persyaratan</h4>
          <p class="text-xs text-[#6B705C]">Dokumen yang harus diunggah warga</p>
        </div>
      </div>
      <span v-if="totalSelected" class="text-[10px] font-bold text-white bg-[#1B4332] px-3 py-1 rounded-full">
        {{ totalSelected }} dipilih ({{ requiredCount }} wajib)
      </span>
    </div>

    <div class="p-5 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div v-for="doc in presetDocuments" :key="doc.key"
          class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 group cursor-pointer"
          :class="isSelected(doc.key) ? 'bg-[#1B4332]/5 border-2 border-[#1B4332]/30 shadow-sm' : 'hover:bg-gray-50 border-2 border-transparent'"
          @click="toggleDocument(doc.key)"
        >
          <div class="relative flex-shrink-0">
            <div :class="isSelected(doc.key) ? 'bg-[#1B4332] border-[#1B4332]' : 'border-[#D4A373]'" class="w-5 h-5 border-2 rounded-lg flex items-center justify-center transition-all">
              <svg v-if="isSelected(doc.key)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7"></path></svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <span class="text-sm font-bold text-[#1B4332] block truncate">{{ doc.label }}</span>
            <span class="text-[11px] text-[#6B705C] block truncate">{{ doc.description }}</span>
          </div>
          <button 
            v-if="isSelected(doc.key)"
            type="button"
            @click.stop="toggleRequired(doc.key)"
            :class="isRequired(doc.key) ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
            class="text-[9px] px-2 py-1 rounded-full font-bold transition-colors flex-shrink-0"
            :title="isRequired(doc.key) ? 'Klik untuk jadi Opsional' : 'Klik untuk jadi Wajib'"
          >
            {{ isRequired(doc.key) ? '🔴 Wajib' : '🟢 Opsional' }}
          </button>
        </div>
        
        <div v-for="(doc, index) in customDocuments" :key="doc.key"
          class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 group"
          :class="doc.active ? 'bg-[#1B4332]/5 border-2 border-[#1B4332]/30 shadow-sm' : 'hover:bg-gray-50 border-2 border-transparent'"
        >
          <div class="relative flex-shrink-0 cursor-pointer" @click="toggleCustomDocument(index)">
            <div :class="doc.active ? 'bg-[#1B4332] border-[#1B4332]' : 'border-[#D4A373]'" class="w-5 h-5 border-2 rounded-lg flex items-center justify-center transition-all">
              <svg v-if="doc.active" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7"></path></svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <span class="text-sm font-bold text-[#1B4332] block truncate">{{ doc.label }}</span>
            <span class="text-[11px] text-[#6B705C] block truncate">{{ doc.description }}</span>
          </div>
          <button 
            v-if="doc.active"
            type="button"
            @click.stop="toggleCustomRequired(index)"
            :class="doc.required ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
            class="text-[9px] px-2 py-1 rounded-full font-bold transition-colors flex-shrink-0"
          >
            {{ doc.required ? '🔴 Wajib' : '🟢 Opsional' }}
          </button>
          <span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full font-bold flex-shrink-0">Custom</span>
          <button type="button" @click="removeCustomDocument(index)" class="text-gray-400 hover:text-red-500 transition-colors p-1 flex-shrink-0" title="Hapus permanen">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
      </div>

      <div class="pt-3 border-t border-dashed border-[#E8D5C4]">
        <div class="flex items-center gap-2 text-[10px] font-bold text-[#6B705C] uppercase tracking-wider mb-3">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Tambah Dokumen Kustom
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
          <input v-model="form.label" type="text" placeholder="Label dokumen" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] focus:ring-1 focus:ring-[#1B4332]/20 outline-none transition-all">
          <input v-model="form.description" type="text" placeholder="Deskripsi singkat" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] focus:ring-1 focus:ring-[#1B4332]/20 outline-none transition-all">
          <select v-model="form.required" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] outline-none appearance-none bg-white">
            <option :value="true">🔴 Wajib</option>
            <option :value="false">🟢 Opsional</option>
          </select>
          <button type="button" @click="addCustomDocument" :disabled="!form.label.trim()" class="px-3 py-2 bg-[#1B4332] text-white text-xs font-bold rounded-lg hover:bg-[#2D6A4F] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">+ Tambah</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

const emit = defineEmits<{ update: [documents: any[]] }>();
const props = defineProps<{ existing?: any[] }>();

interface DocItem {
  key: string;
  label: string;
  description: string;
  active: boolean;
  required: boolean;
  custom?: boolean;
}

const presetDocuments = [
  { key: 'ktp', label: 'KTP', description: 'Foto KTP penerima' },
  { key: 'kk', label: 'Kartu Keluarga', description: 'Foto Kartu Keluarga' },
  { key: 'foto_rumah_depan', label: 'Foto Rumah Depan', description: 'Foto kondisi rumah tampak depan' },
  { key: 'foto_rumah_dalam', label: 'Foto Rumah Dalam', description: 'Foto ruang utama rumah' },
  { key: 'sktm', label: 'SKTM', description: 'Surat Keterangan Tidak Mampu' },
  { key: 'slip_gaji', label: 'Slip Gaji', description: 'Slip gaji 3 bulan terakhir' },
  { key: 'rekening_listrik', label: 'Rekening Listrik', description: 'Struk pembayaran listrik' },
  { key: 'rapor', label: 'Rapor Sekolah', description: 'Foto rapor terbaru' },
  { key: 'surat_usaha', label: 'Surat Keterangan Usaha', description: 'SKU dari Desa' },
  { key: 'foto_usaha', label: 'Foto Tempat Usaha', description: 'Foto kondisi tempat usaha' },
];

const selectedDocs = ref<DocItem[]>([]);
const customDocuments = ref<DocItem[]>([]);
const form = ref({ label: '', description: '', required: true });

const totalSelected = computed(() => 
  selectedDocs.value.filter(d => d.active).length + customDocuments.value.filter(d => d.active).length
);

const requiredCount = computed(() => 
  selectedDocs.value.filter(d => d.active && d.required).length + 
  customDocuments.value.filter(d => d.active && d.required).length
);

const trim = (v: string) => v.trim();

onMounted(() => {
  if (props.existing) {
    selectedDocs.value = props.existing
      .filter((d: any) => typeof d === 'string')
      .map((key: string) => {
        const preset = presetDocuments.find(p => p.key === key);
        return { key, label: preset?.label || key, description: preset?.description || '', active: true, required: true };
      });
    
    customDocuments.value = props.existing
      .filter((d: any) => typeof d === 'object')
      .map((d: any) => ({ 
        ...d, 
        label: trim(d.label || ''), 
        description: trim(d.description || ''),
        active: d.active !== false, 
        required: d.required !== false 
      }));
  }
});

const isSelected = (key: string) => selectedDocs.value.some(d => d.key === key && d.active);
const isRequired = (key: string) => selectedDocs.value.find(d => d.key === key)?.required ?? true;

const toggleDocument = (key: string) => {
  const existing = selectedDocs.value.find(d => d.key === key);
  if (existing) {
    existing.active = !existing.active;
  } else {
    const preset = presetDocuments.find(p => p.key === key);
    selectedDocs.value.push({ key, label: preset?.label || key, description: preset?.description || '', active: true, required: true });
  }
  emitUpdate();
};

const toggleRequired = (key: string) => {
  const doc = selectedDocs.value.find(d => d.key === key);
  if (doc) doc.required = !doc.required;
  emitUpdate();
};

const toggleCustomDocument = (index: number) => {
  customDocuments.value[index].active = !customDocuments.value[index].active;
  emitUpdate();
};

const toggleCustomRequired = (index: number) => {
  customDocuments.value[index].required = !customDocuments.value[index].required;
  emitUpdate();
};

const addCustomDocument = () => {
  if (!form.value.label.trim()) return;
  const key = 'custom_' + trim(form.value.label.toLowerCase().replace(/\s+/g, '_'));
  customDocuments.value.push({ 
    key, 
    label: trim(form.value.label), 
    description: trim(form.value.description), 
    active: true, 
    required: form.value.required, 
    custom: true 
  });
  form.value = { label: '', description: '', required: true };
  emitUpdate();
};

const removeCustomDocument = (index: number) => {
  customDocuments.value.splice(index, 1);
  emitUpdate();
};

const emitUpdate = () => {
  const allDocs = [
    // ✅ Preset — simpan sebagai object jika required = false, sebagai string jika required = true
    ...selectedDocs.value
      .filter(d => d.active)
      .map(d => d.required ? d.key : { 
        key: d.key, 
        label: d.label, 
        description: d.description, 
        active: d.active, 
        required: false, 
        custom: false 
      }),
    // ✅ Custom tetap sebagai object
    ...customDocuments.value
      .filter(d => d.active)
      .map(d => ({ 
        key: d.key, 
        label: trim(d.label), 
        description: trim(d.description), 
        active: d.active, 
        required: d.required, 
        custom: true 
      })),
  ];
  emit('update', allDocs);
};
</script>