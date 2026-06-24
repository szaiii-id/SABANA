<template>
  <div class="bg-white rounded-2xl border border-[#E8D5C4] overflow-hidden shadow-sm">
    <div class="px-6 py-4 bg-gradient-to-r from-[#1B4332]/5 to-transparent border-b border-[#E8D5C4] flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#1B4332] flex items-center justify-center shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h4 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">Target Golongan</h4>
          <p class="text-xs text-[#6B705C]">Siapa yang berhak menerima</p>
        </div>
      </div>
      <span v-if="totalSelected" class="text-[10px] font-bold text-white bg-[#1B4332] px-3 py-1 rounded-full">{{ totalSelected }} dipilih</span>
    </div>

    <div class="p-5 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <label v-for="target in presetTargets" :key="target.key" @click="toggleTarget(target.key)"
          class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all duration-200 group"
          :class="isSelected(target.key) ? 'bg-[#1B4332]/5 border-2 border-[#1B4332]/30 shadow-sm' : 'hover:bg-gray-50 border-2 border-transparent'"
        >
          <div class="relative flex-shrink-0">
            <div :class="isSelected(target.key) ? 'bg-[#1B4332] border-[#1B4332]' : 'border-[#D4A373]'" class="w-5 h-5 border-2 rounded-lg flex items-center justify-center transition-all">
              <svg v-if="isSelected(target.key)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7"></path></svg>
            </div>
          </div>
          <div class="min-w-0">
            <span class="text-sm font-bold text-[#1B4332] block truncate">{{ target.label }}</span>
            <span class="text-[11px] text-[#6B705C] block truncate">{{ target.description }}</span>
          </div>
        </label>
        
        <div v-for="(target, index) in customTargets" :key="target.key"
          class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200 group"
          :class="target.active ? 'bg-[#1B4332]/5 border-2 border-[#1B4332]/30 shadow-sm' : 'hover:bg-gray-50 border-2 border-transparent'"
        >
          <div class="relative flex-shrink-0 cursor-pointer" @click="toggleCustomTarget(index)">
            <div :class="target.active ? 'bg-[#1B4332] border-[#1B4332]' : 'border-[#D4A373]'" class="w-5 h-5 border-2 rounded-lg flex items-center justify-center transition-all">
              <svg v-if="target.active" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7"></path></svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <span class="text-sm font-bold text-[#1B4332] block truncate">{{ target.label }}</span>
            <span class="text-[11px] text-[#6B705C] block truncate">{{ target.description }}</span>
          </div>
          <span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full font-bold">Custom</span>
          <button @click="removeCustomTarget(index)" class="text-gray-400 hover:text-red-500 transition-colors p-1 flex-shrink-0" title="Hapus permanen">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
      </div>

      <div class="pt-3 border-t border-dashed border-[#E8D5C4]">
        <div class="flex items-center gap-2 text-[10px] font-bold text-[#6B705C] uppercase tracking-wider mb-3">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Tambah Kustom
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
          <input v-model="form.label" type="text" placeholder="Label golongan" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] focus:ring-1 focus:ring-[#1B4332]/20 outline-none transition-all">
          <input v-model="form.description" type="text" placeholder="Deskripsi singkat" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] focus:ring-1 focus:ring-[#1B4332]/20 outline-none transition-all">
          <button @click="addCustomTarget" :disabled="!form.label.trim()" class="px-3 py-2 bg-[#1B4332] text-white text-xs font-bold rounded-lg hover:bg-[#2D6A4F] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">+ Tambah</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

const emit = defineEmits<{ update: [targets: any[]] }>();
const props = defineProps<{ existing?: any[] }>();

const presetTargets = [
  { key: 'miskin_ekstrem', label: 'Miskin Ekstrem', description: 'Penghasilan < Rp 500.000/bulan' },
  { key: 'miskin', label: 'Miskin', description: 'Penghasilan < Rp 1.000.000/bulan' },
  { key: 'rentan_miskin', label: 'Rentan Miskin', description: 'Penghasilan < Rp 2.000.000/bulan' },
  { key: 'lansia', label: 'Lansia', description: 'Usia > 60 tahun' },
  { key: 'disabilitas', label: 'Disabilitas', description: 'Penyandang disabilitas' },
  { key: 'pelajar', label: 'Pelajar', description: 'Anak usia sekolah' },
  { key: 'ibu_hamil', label: 'Ibu Hamil/Menyusui', description: 'Ibu hamil/balita' },
  { key: 'umkm', label: 'UMKM Mikro', description: 'Pemilik usaha kecil' },
];

const selectedKeys = ref<string[]>([]);
const customTargets = ref<Array<{key: string; label: string; description: string; active: boolean}>>([]);
const form = ref({ label: '', description: '' });

// ✅ Helper trim
const trim = (v: string) => v.trim();

const totalSelected = computed(() => 
  selectedKeys.value.length + customTargets.value.filter(t => t.active).length
);

onMounted(() => {
  if (props.existing) {
    selectedKeys.value = props.existing
      .filter((t: any) => typeof t === 'string')
      .map((t: string) => t);
    
    customTargets.value = props.existing
      .filter((t: any) => typeof t === 'object')
      .map((t: any) => ({ 
        ...t, 
        label: trim(t.label || ''), 
        description: trim(t.description || ''),
        active: t.active !== false 
      }));
  }
});

const isSelected = (key: string) => selectedKeys.value.includes(key);

const toggleTarget = (key: string) => {
  const idx = selectedKeys.value.indexOf(key);
  idx === -1 ? selectedKeys.value.push(key) : selectedKeys.value.splice(idx, 1);
  emitUpdate();
};

const toggleCustomTarget = (index: number) => {
  customTargets.value[index].active = !customTargets.value[index].active;
  emitUpdate();
};

const addCustomTarget = () => {
  if (!form.value.label.trim()) return;
  const key = 'custom_' + trim(form.value.label.toLowerCase().replace(/\s+/g, '_'));
  customTargets.value.push({ 
    key, 
    label: trim(form.value.label), 
    description: trim(form.value.description), 
    active: true 
  });
  form.value = { label: '', description: '' };
  emitUpdate();
};

const removeCustomTarget = (index: number) => {
  customTargets.value.splice(index, 1);
  emitUpdate();
};

const emitUpdate = () => {
  const custom = customTargets.value.map(t => ({
    key: t.key,
    label: trim(t.label),
    description: trim(t.description),
    active: t.active,
    custom: true,
  }));
  emit('update', [...selectedKeys.value, ...custom]);
};
</script>