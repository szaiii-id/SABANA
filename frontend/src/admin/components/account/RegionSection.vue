<template>
  <div class="p-5 rounded-2xl bg-[#FAF6F0] border border-[#E8D5C4] space-y-4">
    <h4 class="text-xs font-black text-[#1B4332] uppercase tracking-widest border-b border-[#E8D5C4] pb-2">Penugasan Wilayah</h4>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-2 block mb-2">Kabupaten/Kota</label>
        <select 
          :value="regencyId"
          @change="$emit('update:regencyId', ($event.target as HTMLSelectElement).value)" 
          class="w-full p-3.5 rounded-2xl border-2 border-transparent shadow-md bg-white text-[#4A3728] font-bold outline-none focus:ring-4 focus:ring-[#2D6A4F]/5 focus:border-[#2D6A4F] transition-all custom-select"
        >
          <option value="">Pilih Kabupaten</option>
          <option v-for="opt in regencies" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
        </select>
      </div>

      <div v-if="showDistrict">
        <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-2 block mb-2">Kecamatan</label>
        <select 
          :value="districtId"
          @change="$emit('update:districtId', ($event.target as HTMLSelectElement).value)" 
          :disabled="!regencyId" 
          class="w-full p-3.5 rounded-2xl border-2 border-transparent shadow-md bg-white text-[#4A3728] font-bold outline-none focus:ring-4 focus:ring-[#2D6A4F]/5 focus:border-[#2D6A4F] transition-all custom-select disabled:bg-gray-100 disabled:cursor-not-allowed"
        >
          <option value="">Pilih Kecamatan</option>
          <option v-for="opt in districts" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
        </select>
      </div>

      <div v-if="showVillage">
        <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-2 block mb-2">Desa/Kelurahan</label>
        <select 
          :value="villageId"
          @change="$emit('update:villageId', ($event.target as HTMLSelectElement).value)" 
          :disabled="!districtId" 
          class="w-full p-3.5 rounded-2xl border-2 border-transparent shadow-md bg-white text-[#4A3728] font-bold outline-none focus:ring-4 focus:ring-[#2D6A4F]/5 focus:border-[#2D6A4F] transition-all custom-select disabled:bg-gray-100 disabled:cursor-not-allowed"
        >
          <option value="">Pilih Desa</option>
          <option v-for="opt in villages" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Region } from '../../composables/useRegion';

defineProps<{
  regencyId?: string; 
  districtId?: string; 
  villageId?: string;
  regencies: Region[]; 
  districts: Region[]; 
  villages: Region[];
  showDistrict: boolean; 
  showVillage: boolean;
}>();

defineEmits<{
  'update:regencyId': [value: string];
  'update:districtId': [value: string];
  'update:villageId': [value: string];
}>();
</script>

<style scoped>
.custom-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238B5E3C'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1.2rem;
  padding-right: 2.5rem;
}
</style>