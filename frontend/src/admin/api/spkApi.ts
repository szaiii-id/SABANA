import adminApi from './axios'
import type { SpkProgram, SpkData } from '../types/spk'

export const spkApi = {
  getPrograms: (search?: string) =>
    adminApi.get<{ status: string; data: SpkProgram[] }>('/spk/programs', {
      params: search ? { search } : {},
    }),

  getSpkData: (programId: string, params?: Record<string, string>) =>
    adminApi.get<{ status: string; data: SpkData }>(`/spk/${programId}`, { params }),
}