import { ref, computed, watch } from 'vue'
import type { ReportFilter, ProgramOption } from '../types/reportAdmin'
import { adminReportService } from '../services/adminReportService'
import adminApi from '../api/axios'

interface WilayahOption {
  id: string
  name: string
}

export function useReportFilter() {
  const filters = ref<ReportFilter>({
    tgl_mulai: '',
    tgl_akhir: '',
    program_id: '',
    wilayah_id: '',
  })

  const programs = ref<ProgramOption[]>([])
  const regencies = ref<WilayahOption[]>([])
  const districts = ref<WilayahOption[]>([])
  const villages = ref<WilayahOption[]>([])
  const isLoading = ref(false)

  const currentRole = ref<string>('')
  const adminRegencyId = ref<string>('')
  const adminDistrictId = ref<string>('')
  const adminVillageId = ref<string>('')

  try {
    const adminData = JSON.parse(localStorage.getItem('admin_user') || '{}')
    currentRole.value = adminData?.role || ''
    adminRegencyId.value = adminData?.regency_id || ''
    adminDistrictId.value = adminData?.district_id || ''
    adminVillageId.value = adminData?.village_id || ''
  } catch {
    // ignore
  }

  const selectedRegencyId = ref(adminRegencyId.value || '')
  const selectedDistrictId = ref(adminDistrictId.value || '')

  const hasFilter = computed(() => {
    return !!(filters.value.tgl_mulai || filters.value.tgl_akhir || filters.value.program_id || filters.value.wilayah_id)
  })

  const filterLabel = computed(() => {
    const parts: string[] = []
    if (filters.value.tgl_mulai && filters.value.tgl_akhir) {
      parts.push(`${filters.value.tgl_mulai} s/d ${filters.value.tgl_akhir}`)
    } else if (filters.value.tgl_mulai) {
      parts.push(`Dari ${filters.value.tgl_mulai}`)
    } else if (filters.value.tgl_akhir) {
      parts.push(`Sampai ${filters.value.tgl_akhir}`)
    }
    if (filters.value.program_id) {
      const program = programs.value.find(p => p.id === filters.value.program_id)
      if (program) parts.push(`Program: ${program.name}`)
    }
    if (filters.value.wilayah_id) {
      const desa = villages.value.find(v => v.id === filters.value.wilayah_id)
      if (desa) parts.push(`Wilayah: ${desa.name}`)
    }
    return parts.length > 0 ? parts.join(' | ') : 'Semua Data'
  })

  function resetFilters(): void {
    filters.value = {
      tgl_mulai: '',
      tgl_akhir: '',
      program_id: '',
      wilayah_id: '',
    }
    districts.value = []
    villages.value = []
    selectedRegencyId.value = adminRegencyId.value || ''
    selectedDistrictId.value = adminDistrictId.value || ''
  }

  async function loadPrograms(): Promise<void> {
    try {
      programs.value = await adminReportService.fetchActivePrograms()
    } catch {
      programs.value = []
    }
  }

  async function loadRegencies(): Promise<void> {
    try {
      const response = await adminApi.get('regions/regencies')
      regencies.value = response.data ?? []
    } catch {
      regencies.value = []
    }
  }

  async function loadDistricts(regencyId: string): Promise<void> {
    if (!regencyId) {
      districts.value = []
      villages.value = []
      return
    }
    try {
      const response = await adminApi.get('regions/districts', { params: { regency_id: regencyId } })
      districts.value = response.data ?? []
    } catch {
      districts.value = []
    }
    villages.value = []
    filters.value.wilayah_id = ''
  }

  async function loadVillages(districtId: string): Promise<void> {
    if (!districtId) {
      villages.value = []
      return
    }
    try {
      const response = await adminApi.get('regions/villages', { params: { district_id: districtId } })
      villages.value = response.data ?? []
    } catch {
      villages.value = []
    }
    filters.value.wilayah_id = ''
  }

  // Watch
  watch(selectedRegencyId, (newVal) => {
    loadDistricts(newVal)
  })

  watch(selectedDistrictId, (newVal) => {
    loadVillages(newVal)
  })

  async function initWilayah(): Promise<void> {
    if (currentRole.value === 'super_admin') {
      await loadRegencies()
      return
    }
    if (currentRole.value === 'regency_admin') {
      await loadDistricts(adminRegencyId.value)
      return
    }
    if (currentRole.value === 'district_admin') {
      await loadDistricts(adminRegencyId.value)
      selectedDistrictId.value = adminDistrictId.value
      await loadVillages(adminDistrictId.value)
      return
    }
    if (adminVillageId.value) {
      filters.value.wilayah_id = adminVillageId.value
    }
  }

  return {
    filters,
    programs,
    regencies,
    districts,
    villages,
    selectedRegencyId,
    selectedDistrictId,
    currentRole,
    isLoading,
    hasFilter,
    filterLabel,
    resetFilters,
    loadPrograms,
    initWilayah,
  }
}