import { citizenAssistanceApi } from '../api/citizenAssistanceApi';
import type { CitizenSearchResult, CitizenAssistancePayload } from '../types/citizen-assistance';
import type { AssistanceProgramSchema } from '../../types/assistance';

export const CitizenAssistanceService = {
  async searchCitizen(query: string): Promise<CitizenSearchResult[]> {
    const response = await citizenAssistanceApi.searchCitizen(query);
    return response.data.data || [];
  },

  async searchCitizenPaginated(query: string, page: number): Promise<{
    data: CitizenSearchResult[];
    has_more: boolean;
    total: number;
  }> {
    const response = await citizenAssistanceApi.searchCitizenPaginated(query, page);
    return response.data.data;
  },

  async getPrograms(citizenId?: string): Promise<AssistanceProgramSchema[]> {
    const response = await citizenAssistanceApi.getPrograms(citizenId);
    const rawData = response.data?.data || response.data || [];
    const list: unknown[] = Array.isArray(rawData) ? rawData : Object.values(rawData);

    return list.map((item: unknown) => {
      const program = item as Record<string, unknown>;
      return {
        id: program.id as string,
        title: (program.name || program.title || 'Tanpa Nama') as string,
        slug: (program.slug || '') as string,
        description: (program.description || '') as string,
        badge: 'Bantuan Aktif',
        banner_url: (program.banner_url || null) as string | null,
        start_date: (program.start_date || null) as string | null,
        end_date: (program.end_date || null) as string | null,
        quota_total: (program.quota_total || null) as number | null,
        benefit_amount: (program.benefit_amount || null) as number | null,
        inputs: ((program.criteria as Record<string, unknown>)?.inputs || program.inputs || []) as AssistanceProgramSchema['inputs'],
        documents: ((program.criteria as Record<string, unknown>)?.documents || program.documents || []) as AssistanceProgramSchema['documents'],
        has_submitted: (program.has_submitted ?? false) as boolean,
      };
    });
  },

  async submitAssistance(payload: CitizenAssistancePayload) {
    const response = await citizenAssistanceApi.submit(payload);
    return response.data;
  },
};