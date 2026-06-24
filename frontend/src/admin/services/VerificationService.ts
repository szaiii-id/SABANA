import { verificationEndpoint } from '../api/verificationApi';
import type { VerificationFilters } from '../types/verification';

const VerificationService = {
  async fetchVerifications(filters?: VerificationFilters) {
    const response = await verificationEndpoint.getAll(filters);
    return response.data;
  },

  async fetchVerificationDetail(id: string) {
    const response = await verificationEndpoint.getById(id);
    return response.data;
  },

  async approveVerification(id: string) {
    const response = await verificationEndpoint.approve(id);
    return response.data;
  },

  async rejectVerification(id: string, notes: string) {
    const response = await verificationEndpoint.reject(id, { notes });
    return response.data;
  },

async requestRevision(id: string, notes: string, revisionItems?: string[]) {
    const response = await verificationEndpoint.requestRevision(id, { 
        notes, 
        revision_items: revisionItems || [] 
    });
    return response.data;
},

  async completeVerification(id: string) {
    const response = await verificationEndpoint.complete(id);
    return response.data;
  },

  async unvalidateVerification(id: string, notes: string) {
    const response = await verificationEndpoint.unvalidate(id, { notes });
    return response.data;
  },

  async bulkCompleteVerification(ids: string[]) {
      const response = await verificationEndpoint.bulkComplete(ids);
      return response.data;
  },
};

export default VerificationService;