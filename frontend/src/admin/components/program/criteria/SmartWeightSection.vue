<template>
  <template v-if="smartInputs.length > 0">
    <div class="bg-white rounded-2xl border border-[#E8D5C4] overflow-hidden shadow-sm">
      <div class="px-6 py-4 bg-gradient-to-r from-[#1B4332]/5 to-transparent border-b border-[#E8D5C4] flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-[#1B4332] flex items-center justify-center shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">Bobot Kriteria SMART</h4>
            <p class="text-xs text-[#6B705C]">
              Total harus 100% | Geser slider atau ketik langsung
            </p>
          </div>
        </div>
        <span :class="totalWeight === 100 ? 'bg-green-500' : 'bg-red-500'" class="text-[10px] font-bold text-white px-3 py-1 rounded-full">{{ totalWeight }}%</span>
      </div>

      <div class="p-5 space-y-4">
        <div v-for="input in smartInputs" :key="input.key" class="flex items-center gap-4 p-4 bg-[#FAF6F0] rounded-xl border border-[#E8D5C4] hover:border-[#D4A373] transition-all">
          
          <div class="w-32 flex-shrink-0">
            <p class="text-[12px] font-bold text-[#1B4332] truncate">{{ input.label }}</p>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span v-if="input.sifat === 'benefit'" class="text-[9px] px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-bold">BENEFIT</span>
              <span v-else-if="input.sifat === 'cost'" class="text-[9px] px-1.5 py-0.5 bg-red-100 text-red-700 rounded font-bold">COST</span>
            </div>
          </div>

          <div class="flex-1 px-2">
            <input 
              type="range" 
              min="0" 
              max="100" 
              :value="weights[input.key] || 0"
              :aria-label="`Bobot untuk ${input.label}`"
              :aria-valuenow="weights[input.key] || 0"
              :aria-valuemin="0"
              :aria-valuemax="100"
              @input="setWeight(input.key, Number(($event.target as HTMLInputElement).value))"
              class="slider-smart"
              :style="{ background: `linear-gradient(to right, #1B4332 0%, #1B4332 ${weights[input.key] || 0}%, #E8D5C4 ${weights[input.key] || 0}%, #E8D5C4 100%)` }"
            />
          </div>

          <div class="w-20 flex-shrink-0">
            <div class="flex items-center bg-white rounded-lg border border-[#E8D5C4] overflow-hidden shadow-sm">
              <input 
                type="number" 
                min="0" 
                max="100" 
                :value="weights[input.key] || 0"
                :aria-label="`Nilai bobot untuk ${input.label}`"
                @input="handleInput(input.key, ($event.target as HTMLInputElement).value)"
                class="w-full px-3 py-2 text-center text-sm font-black text-[#1B4332] bg-transparent border-0 outline-none"
              />
              <span class="pr-3 text-xs font-bold text-[#6B705C]" aria-hidden="true">%</span>
            </div>
          </div>
        </div>
        
        <div :class="totalWeight === 100 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'" class="rounded-xl p-3 text-center transition-colors" role="status" aria-live="polite">
          <p v-if="totalWeight !== 100" class="text-[12px] text-red-600 font-bold">
             Total bobot harus tepat 100% (saat ini {{ totalWeight }}%)
          </p>
          <p v-else class="text-[12px] text-green-600 font-bold">
            Total bobot sudah 100% - Siap disimpan
          </p>
        </div>

        <!-- Reset Button -->
        <div class="pt-3 border-t border-dashed border-[#E8D5C4]">
          <button 
            type="button"
            @click="resetAllWeights"
            aria-label="Reset semua bobot ke 0"
            class="w-full px-4 py-2.5 text-xs font-bold text-[#6B705C] bg-white border border-[#E8D5C4] rounded-xl hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all flex items-center justify-center gap-2"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Reset Semua Bobot
          </button>
        </div>
      </div>
    </div>
  </template>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';

interface SmartInput {
  key: string;
  label: string;
  type: string;
  sifat: 'benefit' | 'cost' | 'none';
  weight?: number;
  ideal_value?: number;
  required?: boolean;
  options?: Array<{ value: string; score: number }>;
}

const emit = defineEmits<{ update: [inputs: Array<{ key: string; weight: number }>] }>();
const props = defineProps<{ 
  inputs: SmartInput[]; 
  existing?: Record<string, number>; 
}>();

const weights = ref<Record<string, number>>({});

const smartInputs = computed<SmartInput[]>(() => 
  props.inputs.filter(i => i.sifat === 'benefit' || i.sifat === 'cost')
);

const totalWeight = computed<number>(() => 
  Object.values(weights.value).reduce((sum, val) => sum + val, 0)
);

const otherTotal = (excludeKey: string): number => {
  return Object.entries(weights.value)
    .filter(([key]) => key !== excludeKey)
    .reduce((sum, [, val]) => sum + val, 0);
};

const emitUpdate = () => {
  const result = smartInputs.value.map(input => ({
    key: input.key,
    weight: weights.value[input.key] || 0,
  }));
  emit('update', result);
};

onMounted(() => {
  if (props.existing) {
    weights.value = { ...props.existing };
  }
  smartInputs.value.forEach(input => {
    if (!(input.key in weights.value)) {
      weights.value[input.key] = input.weight || 0;
    }
  });
  emitUpdate();
});

watch(() => props.inputs, () => {
  const newWeights: Record<string, number> = {};
  smartInputs.value.forEach(input => {
    newWeights[input.key] = weights.value[input.key] ?? input.weight ?? 0;
  });
  weights.value = newWeights;
  emitUpdate();
}, { deep: true });

const setWeight = (key: string, value: number) => {
  const others = otherTotal(key);
  if (others + value > 100) {
    value = 100 - others;
  }
  weights.value[key] = Math.max(0, value);
  emitUpdate();
};

const handleInput = (key: string, raw: string) => {
  let value = parseInt(raw, 10) || 0;
  const others = otherTotal(key);
  if (others + value > 100) {
    value = 100 - others;
  }
  weights.value[key] = Math.max(0, value);
  emitUpdate();
};

const resetAllWeights = () => {
  smartInputs.value.forEach(input => {
    weights.value[input.key] = 0;
  });
  emitUpdate();
};
</script>

<style scoped>
input[type="range"].slider-smart {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 14px;
  border-radius: 999px;
  outline: none;
  cursor: pointer;
  transition: background 0.2s ease;
}

input[type="range"].slider-smart:focus-visible {
  outline: 3px solid #1B4332;
  outline-offset: 4px;
}

input[type="range"].slider-smart::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #1B4332;
  border: 3px solid white;
  box-shadow: 0 3px 10px rgba(27,67,50,0.25);
  cursor: pointer;
  transition: all 0.15s ease;
  margin-top: -7px;
}

input[type="range"].slider-smart::-webkit-slider-thumb:hover {
  transform: scale(1.15);
  box-shadow: 0 5px 15px rgba(27,67,50,0.35);
}

input[type="range"].slider-smart::-webkit-slider-thumb:active {
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(27,67,50,0.3);
}

input[type="range"].slider-smart:focus-visible::-webkit-slider-thumb {
  outline: 3px solid #1B4332;
  outline-offset: 4px;
}

input[type="range"].slider-smart::-moz-range-track {
  width: 100%;
  height: 14px;
  border-radius: 999px;
  background: #E8D5C4;
}

input[type="range"].slider-smart::-moz-range-thumb {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #1B4332;
  border: 3px solid white;
  box-shadow: 0 3px 10px rgba(27,67,50,0.25);
  cursor: pointer;
}

input[type="range"].slider-smart:focus-visible::-moz-range-thumb {
  outline: 3px solid #1B4332;
  outline-offset: 4px;
}

input[type="number"] {
  -moz-appearance: textfield;
}
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>