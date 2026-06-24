import { spkApi } from '../api/spkApi'
import type { SpkProgram, SpkData } from '../types/spk'

export const SpkService = {
  fetchPrograms: async (search?: string): Promise<SpkProgram[]> => {
    const { data } = await spkApi.getPrograms(search)
    return data.data
  },

  fetchSpkData: async (programId: string, filters?: Record<string, string>): Promise<SpkData> => {
    const { data } = await spkApi.getSpkData(programId, filters)
    return data.data
  },
}