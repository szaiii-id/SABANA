<template>
  <div class="bg-white rounded-2xl border border-[#E8D5C4] overflow-hidden shadow-sm">
    <div class="px-6 py-4 bg-gradient-to-r from-[#1B4332]/5 to-transparent border-b border-[#E8D5C4] flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#1B4332] flex items-center justify-center shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </div>
        <div>
          <h4 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">Form Input Data Warga</h4>
          <p class="text-xs text-[#6B705C]">Data yang harus diisi warga saat mendaftar</p>
        </div>
      </div>
      <span v-if="customInputs.length" class="text-[10px] font-bold text-white bg-[#1B4332] px-3 py-1 rounded-full">{{ customInputs.length }} field</span>
    </div>

    <div class="p-5 space-y-4">
      <!-- Field List -->
      <div v-if="customInputs.length" class="space-y-2">
        <div v-for="(input, index) in customInputs" :key="index" 
          class="flex items-center gap-3 p-3 rounded-xl border transition-all"
          :class="editingIndex === index ? 'bg-white border-[#1B4332] shadow-md' : 'bg-[#FAF6F0] border-[#E8D5C4] group hover:border-[#D4A373] hover:shadow-sm'"
        >
          <template v-if="editingIndex !== index">
            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-[10px] font-black text-[#6B705C] shadow-sm">{{ index + 1 }}</div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-[#1B4332] truncate">{{ input.label }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-[10px] px-2 py-0.5 bg-white rounded-md font-bold text-[#6B705C] uppercase">{{ input.type }}</span>
                <span v-if="input.sifat === 'benefit'" class="text-[10px] px-2 py-0.5 bg-green-100 text-green-700 rounded-md font-bold">Benefit</span>
                <span v-else-if="input.sifat === 'cost'" class="text-[10px] px-2 py-0.5 bg-red-100 text-red-700 rounded-md font-bold">Cost</span>
                <span class="text-[11px] font-bold" :class="input.required ? 'text-red-500' : 'text-[#6B705C]'">· {{ input.required ? 'Wajib' : 'Opsional' }}</span>
                <span v-if="input.ideal_value" class="text-[11px] font-bold text-[#1B4332]">· Ideal: {{ formatNumber(input.ideal_value) }}</span>
                <span v-else-if="input.sifat !== 'none' && ['number','decimal','currency'].includes(input.type)" class="text-[11px] font-bold text-red-500">· Ideal: belum diisi!</span>
                <span v-if="input.options" class="text-[11px] text-[#6B705C]">· {{ input.options.length }} opsi</span>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <button type="button" @click="startEdit(index)" class="text-gray-400 hover:text-blue-500 transition-colors opacity-0 group-hover:opacity-100 p-1" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
              </button>
              <button type="button" @click="removeInput(index)" class="text-gray-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 p-1" title="Hapus">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
          </template>

          <template v-else>
            <div class="flex-1 space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                <input v-model="editForm.label" type="text" placeholder="Label" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] outline-none">
                <select v-model="editForm.type" @change="onEditTypeChange" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] outline-none appearance-none bg-white">
                  <option value="text">Text</option>
                  <option value="number">Number</option>
                  <option value="decimal">Decimal</option>
                  <option value="currency">Currency (Rp)</option>
                  <option value="select">Select</option>
                  <option value="date">Date</option>
                  <option value="textarea">Text Area</option>
                </select>
                <select v-if="showEditSifat" v-model="editForm.sifat" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] outline-none appearance-none bg-white">
                  <option value="benefit">Benefit ↑</option>
                  <option value="cost">Cost ↓</option>
                  <option value="none">Tidak Masuk SMART</option>
                </select>
                <label class="flex items-center gap-2 px-3 py-2 text-xs text-[#6B705C]">
                  <input type="checkbox" v-model="editForm.required" class="w-4 h-4 rounded border-[#D4A373] text-[#1B4332]"> Wajib
                </label>
                <div class="flex items-center gap-1">
                  <button type="button" @click="saveEdit" class="px-3 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition-colors">Simpan</button>
                  <button type="button" @click="cancelEdit" class="px-3 py-2 bg-gray-200 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-300 transition-colors">Batal</button>
                </div>
              </div>
              <!-- Edit: Ideal Value -->
              <div v-if="showEditSifat && editForm.sifat !== 'none' && editForm.type !== 'select'" class="grid grid-cols-1 gap-2">
                <div>
                  <label class="text-[10px] font-bold text-[#6B705C] uppercase mb-1 block">
                    Nilai Ideal <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model.number="editForm.ideal_value" 
                    type="number" 
                    placeholder="Masukkan nilai ideal" 
                    :class="editIdealError ? 'border-red-400 focus:border-red-500 bg-red-50' : 'border-[#E8D5C4] focus:border-[#1B4332]'"
                    class="w-full px-3 py-2 rounded-lg border text-xs outline-none"
                  >
                  <p v-if="editIdealError" class="text-[10px] text-red-500 font-bold mt-1">⚠ Nilai Ideal wajib diisi lebih dari 0 untuk kriteria SMART.</p>
                </div>
              </div>
              <div v-if="editForm.type === 'select'" class="space-y-2">
                <p class="text-[10px] font-bold text-[#6B705C] uppercase">Opsi Pilihan</p>
                <div class="bg-blue-50 rounded-xl p-3 border border-blue-200">
                  <p class="text-[10px] text-blue-700 font-bold">
                    💡 <strong>Urutkan dari yang paling diprioritaskan:</strong>
                  </p>
                  <ul class="text-[10px] text-blue-600 mt-1 space-y-0.5 ml-4 list-disc">
                    <li>Opsi <strong>paling atas</strong> = skor tertinggi</li>
                    <li>Opsi <strong>paling bawah</strong> = skor terendah</li>
                    <li>Skor dihitung otomatis merata</li>
                  </ul>
                </div>
                <div class="space-y-1.5">
                  <div v-for="(opt, oidx) in editSelectOptions" :key="oidx" class="flex items-center gap-2 p-2 bg-[#FAF6F0] rounded-lg">
                    <span class="text-[10px] font-bold text-[#6B705C] w-6">{{ oidx + 1 }}.</span>
                    <input v-model="editSelectOptions[oidx]" type="text" class="flex-1 px-2 py-1 text-xs border border-[#E8D5C4] rounded outline-none" :placeholder="'Opsi ' + (oidx + 1)">
                    <button type="button" @click="removeEditOption(oidx)" class="text-gray-400 hover:text-red-500" :disabled="editSelectOptions.length <= 2">✕</button>
                  </div>
                </div>
                <button type="button" @click="editSelectOptions.push('')" class="text-[10px] text-[#1B4332] font-bold hover:underline">+ Tambah Opsi</button>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div v-else class="text-center py-8 text-[#6B705C]">
        <svg class="w-10 h-10 mx-auto mb-2 text-[#E8D5C4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        <p class="text-xs font-medium">Belum ada field input</p>
        <p class="text-[11px]">Tambah field di bawah ini</p>
      </div>
      
      <!-- Add Form -->
      <div v-if="editingIndex === null" class="pt-3 border-t border-dashed border-[#E8D5C4]">
        <div class="flex items-center gap-2 text-[10px] font-bold text-[#6B705C] uppercase tracking-wider mb-3">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Tambah Field
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
          <input v-model="form.label" type="text" placeholder="Label field" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] focus:ring-1 focus:ring-[#1B4332]/20 outline-none">
          <select v-model="form.type" @change="onFormTypeChange" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] outline-none appearance-none bg-white">
            <option value="text">Text</option>
            <option value="number">Number</option>
            <option value="decimal">Decimal</option>
            <option value="currency">Currency (Rp)</option>
            <option value="select">Select</option>
            <option value="date">Date</option>
            <option value="textarea">Text Area</option>
          </select>
          <select v-if="showSifat" v-model="form.sifat" class="px-3 py-2 rounded-lg border border-[#E8D5C4] text-xs focus:border-[#1B4332] outline-none appearance-none bg-white">
            <option value="benefit">Benefit ↑</option>
            <option value="cost">Cost ↓</option>
            <option value="none">Tidak Masuk SMART</option>
          </select>
          <label class="flex items-center gap-2 px-3 py-2 text-xs text-[#6B705C]">
            <input type="checkbox" v-model="form.required" class="w-4 h-4 rounded border-[#D4A373] text-[#1B4332]"> Wajib
          </label>
          <button type="button" @click="addInput" :disabled="!form.label.trim()" class="px-3 py-2 bg-[#1B4332] text-white text-xs font-bold rounded-lg hover:bg-[#2D6A4F] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg> Tambah
          </button>
        </div>
        <!-- Add: Ideal Value -->
        <div v-if="showSifat && form.sifat !== 'none' && form.type !== 'select'" class="grid grid-cols-1 gap-2 mt-2">
          <div>
            <label class="text-[10px] font-bold text-[#6B705C] uppercase mb-1 block">
              Nilai Ideal <span class="text-red-500">*</span>
            </label>
            <input 
              v-model.number="form.ideal_value" 
              type="number" 
              placeholder="Masukkan nilai ideal" 
              :class="idealError ? 'border-red-400 focus:border-red-500 bg-red-50' : 'border-[#E8D5C4] focus:border-[#1B4332]'"
              class="w-full px-3 py-2 rounded-lg border text-xs outline-none"
            >
            <p v-if="idealError" class="text-[10px] text-red-500 font-bold mt-1">⚠ Nilai Ideal wajib diisi lebih dari 0 untuk kriteria SMART.</p>
          </div>
        </div>
        <div v-if="form.type === 'select'" class="mt-3 space-y-2">
          <p class="text-[10px] font-bold text-[#6B705C] uppercase tracking-wider">Opsi Pilihan</p>
          <div class="bg-blue-50 rounded-xl p-3 border border-blue-200">
            <p class="text-[10px] text-blue-700 font-bold">
              💡 <strong>Urutkan dari yang paling diprioritaskan:</strong>
            </p>
            <ul class="text-[10px] text-blue-600 mt-1 space-y-0.5 ml-4 list-disc">
              <li>Opsi <strong>paling atas</strong> = skor tertinggi</li>
              <li>Opsi <strong>paling bawah</strong> = skor terendah</li>
              <li>Skor dihitung otomatis merata</li>
            </ul>
          </div>
          <div class="space-y-1.5">
            <div v-for="(option, idx) in selectOptions" :key="idx" class="flex items-center gap-2 p-2 bg-white rounded-lg border border-[#E8D5C4]">
              <span class="text-[10px] font-bold text-[#6B705C] w-6">{{ idx + 1 }}.</span>
              <input v-model="selectOptions[idx]" type="text" class="flex-1 px-2 py-1 text-xs border-none outline-none" :placeholder="'Opsi ' + (idx + 1)">
              <button type="button" @click="removeSelectOption(idx)" class="text-gray-400 hover:text-red-500" :disabled="selectOptions.length <= 2">✕</button>
            </div>
          </div>
          <button type="button" @click="addSelectOption" class="text-[10px] text-[#1B4332] font-bold hover:underline">+ Tambah Opsi</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

const emit = defineEmits<{ update: [inputs: any[]] }>();
const props = defineProps<{ existing?: any[] }>();

const customInputs = ref<any[]>([]);
const form = ref({ label: '', type: 'text', sifat: 'benefit', required: true, ideal_value: 1 });
const selectOptions = ref<string[]>(['', '']);

const editingIndex = ref<number | null>(null);
const editForm = ref({ label: '', type: 'text', sifat: 'benefit', required: true, ideal_value: 1 });
const editSelectOptions = ref<string[]>(['', '']);

const showSifat = computed(() => ['number', 'decimal', 'currency', 'select'].includes(form.value.type));
const showEditSifat = computed(() => ['number', 'decimal', 'currency', 'select'].includes(editForm.value.type));

const idealError = computed(() => {
  if (!showSifat.value || form.value.sifat === 'none' || form.value.type === 'select') return false;
  return (form.value.ideal_value ?? 0) <= 0;
});

const editIdealError = computed(() => {
  if (!showEditSifat.value || editForm.value.sifat === 'none' || editForm.value.type === 'select') return false;
  return (editForm.value.ideal_value ?? 0) <= 0;
});

const autoSifat = (type: string): string => {
  if (type === 'currency') return 'cost';
  if (['number', 'decimal', 'select'].includes(type)) return 'benefit';
  return 'none';
};

// ✅ Ganti watch dengan event handler — tidak ada infinite loop
const onFormTypeChange = (): void => {
  form.value.sifat = autoSifat(form.value.type);
};

const onEditTypeChange = (): void => {
  editForm.value.sifat = autoSifat(editForm.value.type);
};

onMounted(() => {
  if (props.existing) customInputs.value = [...props.existing];
});

const trimValue = (v: string) => v.trim();

const addSelectOption = () => selectOptions.value.push('');
const removeSelectOption = (idx: number) => {
  if (selectOptions.value.length > 2) selectOptions.value.splice(idx, 1);
};

const addInput = () => {
  if (!form.value.label.trim()) return;
  if (idealError.value) return;
  
  const key = trimValue(form.value.label.toLowerCase().replace(/\s+/g, '_'));
  const isSmart = showSifat.value && form.value.sifat !== 'none';
  
  const input: any = { 
    key, 
    type: form.value.type, 
    label: trimValue(form.value.label), 
    required: form.value.required, 
    sifat: showSifat.value ? form.value.sifat : 'none',
    weight: 0,
    ideal_value: isSmart && form.value.type !== 'select' ? form.value.ideal_value : 0,
  };
  
  if (form.value.type === 'select') {
    const opts = selectOptions.value.filter(o => o.trim()).map(trimValue);
    if (opts.length) input.options = opts.map((v, i) => ({ 
      value: v, 
      score: Math.round(((opts.length - i) / opts.length) * 100)
    }));
    selectOptions.value = ['', ''];
  }
  
  customInputs.value.push(input);
  form.value = { label: '', type: 'text', sifat: autoSifat('text'), required: true, ideal_value: 1 };
  emitUpdate();
};

const startEdit = (index: number) => {
  editingIndex.value = index;
  const input = customInputs.value[index];
  editForm.value = { 
    label: input.label, 
    type: input.type, 
    sifat: input.sifat || autoSifat(input.type), 
    required: input.required,
    ideal_value: input.ideal_value || 1,
  };
  editSelectOptions.value = input.options?.map((o: any) => o.value) || ['', ''];
};

const saveEdit = () => {
  if (editingIndex.value === null || !editForm.value.label.trim()) return;
  if (editIdealError.value) return;
  
  const index = editingIndex.value;
  const isSmart = showEditSifat.value && editForm.value.sifat !== 'none';
  
  const input: any = { 
    key: customInputs.value[index].key,
    type: editForm.value.type, 
    label: trimValue(editForm.value.label), 
    required: editForm.value.required,
    sifat: showEditSifat.value ? editForm.value.sifat : 'none',
    weight: customInputs.value[index].weight || 0,
    ideal_value: isSmart && editForm.value.type !== 'select' ? editForm.value.ideal_value : 0,
  };
  
  if (editForm.value.type === 'select') {
    const opts = editSelectOptions.value.filter(o => o.trim()).map(trimValue);
    if (opts.length) input.options = opts.map((v, i) => ({ 
      value: v, 
      score: Math.round(((opts.length - i) / opts.length) * 100)
    }));
  }
  
  customInputs.value[index] = input;
  editingIndex.value = null;
  emitUpdate();
};

const cancelEdit = () => { editingIndex.value = null; };

const removeInput = (index: number) => {
  customInputs.value.splice(index, 1);
  if (editingIndex.value === index) editingIndex.value = null;
  emitUpdate();
};

const removeEditOption = (idx: number) => {
  if (editSelectOptions.value.length > 2) editSelectOptions.value.splice(idx, 1);
};

const formatNumber = (n: number): string => {
  return new Intl.NumberFormat('id-ID').format(n);
};

const emitUpdate = () => emit('update', [...customInputs.value]);
</script>