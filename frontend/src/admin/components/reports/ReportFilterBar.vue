<script setup lang="ts">
import type { ReportFilter, ProgramOption } from '../../types/reportAdmin'
import { XMarkIcon, FunnelIcon, MapPinIcon, CalendarIcon } from '@heroicons/vue/24/outline'

interface WilayahOption {
  id: string
  name: string
}

const props = defineProps<{
  filters: ReportFilter
  programs: ProgramOption[]
  regencies: WilayahOption[]
  districts: WilayahOption[]
  villages: WilayahOption[]
  selectedRegencyId: string
  selectedDistrictId: string
  currentRole: string
  hasFilter: boolean
  filterLabel: string
}>()

const emit = defineEmits<{
  'update:filters': [value: ReportFilter]
  'update:selectedRegencyId': [value: string]
  'update:selectedDistrictId': [value: string]
  'reset': []
}>()

function updateTglMulai(value: string): void {
  emit('update:filters', { ...props.filters, tgl_mulai: value })
}

function updateTglAkhir(value: string): void {
  emit('update:filters', { ...props.filters, tgl_akhir: value })
}

function updateProgram(value: string): void {
  emit('update:filters', { ...props.filters, program_id: value })
}

function updateRegency(value: string): void {
  emit('update:selectedRegencyId', value)
  emit('update:selectedDistrictId', '')
  emit('update:filters', { ...props.filters, wilayah_id: '' })
}

function updateDistrict(value: string): void {
  emit('update:selectedDistrictId', value)
  emit('update:filters', { ...props.filters, wilayah_id: '' })
}

function updateVillage(value: string): void {
  emit('update:filters', { ...props.filters, wilayah_id: value })
}
</script>

<template>
  <div class="bg-white/90 backdrop-blur-md rounded-2xl border border-[#E8D5C4] shadow-sm transition-all duration-300 overflow-hidden">
    
    <div class="flex items-center justify-between px-5 py-4 border-b border-[#F0E8DA]">
      <div class="flex items-center gap-2">
        <FunnelIcon class="w-4 h-4 text-[#2D6A4F]" />
        <span class="text-sm font-semibold text-[#1B4332]">Filter Laporan</span>
      </div>
      <button
        v-if="props.hasFilter"
        @click="emit('reset')"
        class="flex items-center gap-1 text-xs text-red-500 hover:text-red-700 font-medium px-3 py-1.5 rounded-lg hover:bg-red-50 transition-all"
      >
        <XMarkIcon class="w-3.5 h-3.5" />
        Reset Filter
      </button>
    </div>

    <div class="p-5 space-y-4">

      <!-- Periode -->
      <div>
        <div class="flex items-center gap-1.5 mb-2">
          <CalendarIcon class="w-3.5 h-3.5 text-[#6B705C]" />
          <span class="text-xs font-medium text-[#6B705C] uppercase tracking-wide">Periode</span>
        </div>
        <div class="flex items-center gap-2">
          <input
            type="date"
            :value="props.filters.tgl_mulai"
            @input="updateTglMulai(($event.target as HTMLInputElement).value)"
            class="flex-1 border border-[#E8D5C4] rounded-xl px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-[#2D6A4F]/20 focus:border-[#2D6A4F] transition-all outline-none"
          />
          <span class="text-[#A39A87] text-xs">s/d</span>
          <input
            type="date"
            :value="props.filters.tgl_akhir"
            @input="updateTglAkhir(($event.target as HTMLInputElement).value)"
            class="flex-1 border border-[#E8D5C4] rounded-xl px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-[#2D6A4F]/20 focus:border-[#2D6A4F] transition-all outline-none"
          />
        </div>
      </div>

      <!-- Program -->
      <div>
        <label class="text-xs font-medium text-[#6B705C] uppercase tracking-wide block mb-2">Program</label>
        <select
          :value="props.filters.program_id"
          @change="updateProgram(($event.target as HTMLSelectElement).value)"
          class="w-full border border-[#E8D5C4] rounded-xl px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-[#2D6A4F]/20 focus:border-[#2D6A4F] transition-all outline-none"
        >
          <option value="">Semua Program</option>
          <option v-for="p in props.programs" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <!-- Wilayah -->
      <div>
        <div class="flex items-center gap-1.5 mb-2">
          <MapPinIcon class="w-3.5 h-3.5 text-[#6B705C]" />
          <span class="text-xs font-medium text-[#6B705C] uppercase tracking-wide">Wilayah</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          
          <!-- Kabupaten -->
          <div v-if="props.currentRole === 'super_admin'">
            <select
              :value="props.selectedRegencyId"
              @change="updateRegency(($event.target as HTMLSelectElement).value)"
              class="w-full border border-[#E8D5C4] rounded-xl px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-[#2D6A4F]/20 focus:border-[#2D6A4F] transition-all outline-none"
            >
              <option value="">Semua Kabupaten</option>
              <option v-for="r in props.regencies" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>

          <!-- Kecamatan -->
          <div v-if="props.currentRole !== 'village_officer'">
            <select
              :value="props.selectedDistrictId"
              :disabled="!props.selectedRegencyId && props.currentRole === 'super_admin'"
              @change="updateDistrict(($event.target as HTMLSelectElement).value)"
              class="w-full border border-[#E8D5C4] rounded-xl px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-[#2D6A4F]/20 focus:border-[#2D6A4F] transition-all outline-none disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <option value="">Semua Kecamatan</option>
              <option v-for="d in props.districts" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>

          <!-- Desa -->
          <div>
            <select
              :value="props.filters.wilayah_id"
              :disabled="!props.selectedDistrictId && props.currentRole !== 'village_officer'"
              @change="updateVillage(($event.target as HTMLSelectElement).value)"
              class="w-full border border-[#E8D5C4] rounded-xl px-3.5 py-2.5 text-sm bg-white focus:ring-2 focus:ring-[#2D6A4F]/20 focus:border-[#2D6A4F] transition-all outline-none disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <option value="">Semua Desa</option>
              <option v-for="v in props.villages" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
          </div>

        </div>
      </div>

    </div>

    <div
      v-if="props.hasFilter"
      class="px-5 py-3 bg-[#F0F7F4] border-t border-[#E8D5C4] flex items-center gap-2 text-xs text-[#2D6A4F]"
    >
      <span class="w-1.5 h-1.5 rounded-full bg-[#2D6A4F]"></span>
      <span class="font-medium">Filter Aktif:</span>
      <span class="text-[#6B705C]">{{ props.filterLabel }}</span>
    </div>

  </div>
</template>