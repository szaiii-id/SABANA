<template>
  <div class="bg-white p-4 rounded-3xl border border-[#E8D5C4] shadow-sm flex flex-col sm:flex-row gap-4">
    <div class="relative flex-grow">
      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <input 
        :value="modelValue.search"
        @input="$emit('update:modelValue', { ...modelValue, search: ($event.target as HTMLInputElement).value })"
        type="text" placeholder="Cari NIP atau Nama..." 
        class="w-full pl-11 pr-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors"
      />
    </div>
    <div class="sm:w-48">
      <select 
        :value="modelValue.role"
        @change="$emit('update:modelValue', { ...modelValue, role: ($event.target as HTMLSelectElement).value })"
        class="w-full px-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors appearance-none"
      >
        <option value="">Semua Level</option>
        <option value="regency_admin">Admin Kabupaten</option>
        <option value="district_admin">Admin Kecamatan</option>
        <option value="village_officer">Petugas Desa</option>
      </select>
    </div>
    <div class="sm:w-40">
      <select 
        :value="modelValue.is_active"
        @change="$emit('update:modelValue', { ...modelValue, is_active: ($event.target as HTMLSelectElement).value })"
        class="w-full px-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors appearance-none"
      >
        <option value="">Semua Status</option>
        <option value="true">Aktif</option>
        <option value="false">Nonaktif</option>
      </select>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ modelValue: { search: string; role: string; is_active: string } }>();
defineEmits<{ 'update:modelValue': [value: { search: string; role: string; is_active: string }] }>();
</script>