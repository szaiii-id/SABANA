<template>
  <div class="bg-[#FDF8F4] p-6 sm:p-8 rounded-[2.5rem] border-2 border-[#F3E5D8] space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      
      <!-- Kabupaten -->
      <div class="space-y-2">
        <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-1">Kabupaten/Kota</label>
        <select 
          :value="regencyId"
          @change="$emit('update:regencyId', ($event.target as HTMLSelectElement).value); $emit('clearError', 'regency_id')"
          :class="[
            'w-full p-4 rounded-2xl border-2 bg-white transition-all outline-none custom-select',
            regencyError ? 'border-red-200' : 'border-transparent shadow-sm focus:border-[#2D6A4F] text-[#4A3728] font-bold'
          ]"
        >
          <option value="">Pilih Kabupaten</option>
          <option v-for="opt in regencies" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
        </select>
        <p v-if="regencyError" class="text-[9px] font-bold text-red-500 px-1">{{ regencyError }}</p>
      </div>

      <!-- Kecamatan -->
      <div class="space-y-2">
        <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-1">Kecamatan</label>
        <select 
          :value="districtId"
          @change="$emit('update:districtId', ($event.target as HTMLSelectElement).value); $emit('clearError', 'district_id')"
          :class="[
            'w-full p-4 rounded-2xl border-2 bg-white transition-all outline-none custom-select',
            districtError ? 'border-red-200' : 'border-transparent shadow-sm focus:border-[#2D6A4F] text-[#4A3728] font-bold'
          ]"
        >
          <option value="">Pilih Kecamatan</option>
          <option v-for="opt in districts" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
        </select>
        <p v-if="districtError" class="text-[9px] font-bold text-red-500 px-1">{{ districtError }}</p>
      </div>

      <!-- Desa -->
      <div class="space-y-2">
        <label class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em] px-1">Desa/Kelurahan</label>
        <select 
          :value="villageId"
          @change="$emit('update:villageId', ($event.target as HTMLSelectElement).value); $emit('clearError', 'village_id')"
          :class="[
            'w-full p-4 rounded-2xl border-2 bg-white transition-all outline-none custom-select',
            villageError ? 'border-red-200' : 'border-transparent shadow-sm focus:border-[#2D6A4F] text-[#4A3728] font-bold'
          ]"
        >
          <option value="">Pilih Desa</option>
          <option v-for="opt in villages" :key="opt.id" :value="String(opt.id)">{{ opt.name }}</option>
        </select>
        <p v-if="villageError" class="text-[9px] font-bold text-red-500 px-1">{{ villageError }}</p>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import type { Region } from '../../../types/assistance';

defineProps<{
  regencyId: string;
  districtId: string;
  villageId: string;
  regencies: Region[];
  districts: Region[];
  villages: Region[];
  regencyError?: string;
  districtError?: string;
  villageError?: string;
}>();

defineEmits<{
  'update:regencyId': [value: string];
  'update:districtId': [value: string];
  'update:villageId': [value: string];
  clearError: [key: string];
}>();
</script>

<style scoped>
.custom-select {
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  appearance: none !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238B5E3C'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1.2rem center;
  background-size: 1rem;
  padding-right: 2.5rem;
}
</style>