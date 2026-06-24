import { accountEndpoint } from '../api/accountApi';
import type { AccountPayload, AccountFilters } from '../types/account';

const AccountService = {
  async fetchAccounts(filters?: AccountFilters) {
    const response = await accountEndpoint.getAll(filters);
    return response.data; 
  },

  async createAccount(payload: AccountPayload) {
    const response = await accountEndpoint.create(payload);
    return response.data;
  },

  async updateAccount(id: string, payload: Partial<AccountPayload>) {
    const response = await accountEndpoint.update(id, payload);
    return response.data;
  },

  async deleteAccount(id: string) {
    const response = await accountEndpoint.delete(id);
    return response.data;
  },

  async activateAccount(id: string) {
    const response = await accountEndpoint.activate(id);
    return response.data;
  },

  async resetPassword(id: string, newPassword: string) {
    const response = await accountEndpoint.resetPassword(id, newPassword);
    return response.data;
  }
};

export default AccountService;