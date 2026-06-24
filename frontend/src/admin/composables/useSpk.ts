import { ref } from 'vue'
import { SpkService } from '../services/SpkService'
import type { SpkProgram, SpkData, SpkFilters } from '../types/spk'

export function useSpk() {
  const programs = ref<SpkProgram[]>([])
  const spkData = ref<SpkData | null>(null)
  const isLoading = ref(false)
  const error = ref('')

  const fetchPrograms = async (search?: string): Promise<void> => {
    isLoading.value = true
    error.value = ''
    try {
      programs.value = await SpkService.fetchPrograms(search)
    } catch (e: unknown) {
      error.value = 'Gagal memuat daftar program.'
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  const fetchSpkData = async (programId: string, filters?: Partial<SpkFilters>): Promise<void> => {
    isLoading.value = true
    error.value = ''
    try {
      spkData.value = await SpkService.fetchSpkData(programId, filters as Record<string, string>)
    } catch (e: unknown) {
      error.value = 'Gagal memuat data SPK.'
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  const resetSpkData = (): void => {
    spkData.value = null
    error.value = ''
  }

  return {
    programs,
    spkData,
    isLoading,
    error,
    fetchPrograms,
    fetchSpkData,
    resetSpkData,
  }
}