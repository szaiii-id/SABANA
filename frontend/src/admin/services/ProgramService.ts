import { programEndpoint } from '../api/programApi';
import type { ProgramPayload, ProgramFilters } from '../types/program';

const ProgramService = {
  async fetchPrograms(filters?: ProgramFilters) {
    const response = await programEndpoint.getAll(filters);
    return response.data;
  },

  async createProgram(payload: ProgramPayload) {
    const response = await programEndpoint.create(payload);
    return response.data;
  },

  async updateProgram(id: string, payload: Partial<ProgramPayload>) {
    const response = await programEndpoint.update(id, payload);
    return response.data;
  },

  async deleteProgram(id: string) {
    const response = await programEndpoint.delete(id);
    return response.data;
  },

  async uploadBanner(id: string, file: File, onProgress?: (percent: number) => void) {
    const response = await programEndpoint.uploadBanner(id, file, onProgress);
    return response.data;
  },

  async closeProgram(id: string) {
    const response = await programEndpoint.close(id);
    return response.data;
  },

  async reopenProgram(id: string) {
    const response = await programEndpoint.reopen(id);
    return response.data;
  },

  async duplicateProgram(id: string) {
    const response = await programEndpoint.duplicate(id);
    return response.data;
  },

};

export default ProgramService;