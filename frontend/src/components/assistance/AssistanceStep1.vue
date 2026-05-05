<script setup lang="ts">
import type { AssistanceProgramSchema } from '../../types/assistance';

defineProps<{ programs: AssistanceProgramSchema[] }>();
const emit = defineEmits<{ (e: 'select', program: AssistanceProgramSchema): void }>();
</script>

<template>
  <div class="animate-fade-in">
    <div class="mb-10 text-center md:text-left">
      <h3 class="text-3xl font-black text-[#2D6A4F] tracking-tighter">Pilih Program</h3>
      <p class="text-[#8B5E3C]/60 text-[10px] font-black uppercase tracking-[0.3em] mt-2">Sarana Bantuan Anak Banua</p>
    </div>

    <div v-if="programs.length === 0" class="flex flex-col items-center py-24 bg-[#FCF8F4] rounded-[3.5rem] border-2 border-dashed border-[#EADDCD]">
      <div class="w-10 h-10 border-4 border-[#2D6A4F] border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="item in programs" :key="item.id" @click="emit('select', item)"
           class="group relative bg-white border-2 border-[#F3E5D8] p-8 rounded-[3rem] cursor-pointer hover:border-[#2D6A4F] hover:shadow-2xl hover:shadow-green-900/5 transition-all duration-500 overflow-hidden">
        
        <div class="relative z-10">
          <div class="w-16 h-16 bg-[#Fdf8f1] text-[#2D6A4F] rounded-[1.8rem] flex items-center justify-center mb-6 group-hover:bg-[#2D6A4F] group-hover:text-white transition-all duration-500 shadow-sm" v-html="item.iconSvg"></div>
          
          <h4 class="text-2xl font-black text-[#4A3728] mb-2 tracking-tight group-hover:text-[#2D6A4F] transition-colors">{{ item.title }}</h4>
          
          <div class="inline-flex items-center px-4 py-1.5 bg-[#F8F1E9] text-[#8B5E3C] text-[9px] font-black rounded-full uppercase tracking-widest group-hover:bg-[#2D6A4F] group-hover:text-white transition-all">
            {{ item.badge }}
          </div>
        </div>
        
        <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-[#F3E5D8]/20 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
      </div>
    </div>
  </div>
</template>