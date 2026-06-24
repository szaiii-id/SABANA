<template>
  <div class="space-y-4">
    <TargetGolonganSection :existing="(existing?.targets as string[])" @update="updateField('targets', $event)" />
    <DokumenWajibSection :existing="(existing?.documents as string[])" @update="updateField('documents', $event)" />
    <InputDataSection :existing="(existing?.inputs as Record<string, unknown>[])" @update="updateField('inputs', $event)" />
    
    <SmartWeightSection 
      v-if="currentInputs.length" 
      :inputs="(currentInputs as unknown as SmartInput[])" 
      :existing="(existing?.weights as Record<string, number>)" 
      @update="handleWeightUpdate" 
    />
    
    <div v-else class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
      <svg class="w-8 h-8 text-amber-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
      <p class="text-sm font-bold text-amber-700">Tidak ada kriteria Benefit/Cost</p>
      <p class="text-xs text-amber-600 mt-1">
        SMART tidak akan dihitung. Tambahkan minimal 1 kriteria dengan sifat <strong>Benefit</strong> atau <strong>Cost</strong> di bagian Input Data.
      </p>
    </div>

    <AiConfigSection 
      v-if="currentCriteria.documents?.length"
      :documents="(currentCriteria.documents as (string | { key: string; label: string; active?: boolean })[])" 
      :existing="(existing?.ai_config as AiConfigData)"
      @update="handleAiConfigUpdate" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import AiConfigSection from './criteria/AiConfigSection.vue';
import TargetGolonganSection from './criteria/TargetGolonganSection.vue';
import DokumenWajibSection from './criteria/DokumenWajibSection.vue';
import InputDataSection from './criteria/InputDataSection.vue';
import SmartWeightSection from './criteria/SmartWeightSection.vue';

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

interface WeightUpdate {
  key: string;
  weight: number;
}

interface AiConfigData {
  [documentKey: string]: { ocr?: boolean; nlp_match?: boolean };
}

interface CriteriaData {
  targets: string[];
  documents: (string | { key: string; label: string; active?: boolean })[];
  inputs: Record<string, unknown>[];
  ai_config: AiConfigData;
}

const emit = defineEmits<{ update: [criteria: Record<string, unknown>, aiConfig?: Record<string, unknown>] }>();
const props = defineProps<{ existing?: Record<string, unknown> }>();

const currentCriteria = reactive<CriteriaData>({
  targets: (props.existing?.targets as string[]) || [],
  documents: (props.existing?.documents as (string | { key: string; label: string; active?: boolean })[]) || [],
  inputs: (props.existing?.inputs as Record<string, unknown>[]) || [],
  ai_config: (props.existing?.ai_config as AiConfigData) || {},
});

const currentInputs = ref<Record<string, unknown>[]>((props.existing?.inputs as Record<string, unknown>[]) || []);

const handleWeightUpdate = (weights: WeightUpdate[]) => {
  currentInputs.value = currentInputs.value.map(input => {
    const match = weights.find(w => w.key === input.key);
    return match ? { ...input, weight: match.weight } : input;
  });
  currentCriteria.inputs = currentInputs.value;
  emitCriteriaUpdate();
};

const updateField = (field: string, value: unknown): void => {
  (currentCriteria as Record<string, unknown>)[field] = value;
  if (field === 'inputs') currentInputs.value = value as Record<string, unknown>[];
  emitCriteriaUpdate();
};

const handleAiConfigUpdate = (aiConfig: AiConfigData): void => {
  currentCriteria.ai_config = aiConfig;
  emitCriteriaUpdate();
};

const emitCriteriaUpdate = (): void => {
  emit('update', {
    targets: currentCriteria.targets,
    documents: currentCriteria.documents,
    inputs: currentCriteria.inputs,
  }, currentCriteria.ai_config as Record<string, unknown>);
};
</script>