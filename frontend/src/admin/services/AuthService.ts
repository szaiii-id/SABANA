import { adminAuthEndpoint } from '../api/authApi';
import type { LoginPayload } from '../types/auth';

const AuthService = {
  async loginAdmin(payload: LoginPayload) {
    const response = await adminAuthEndpoint.login(payload);
    return response.data;
  },

  async logoutAdmin() {
    const response = await adminAuthEndpoint.logout();
    return response.data;
  },

  async refreshSession() {
    const response = await adminAuthEndpoint.ping();
    return response.data;
  },
};

export default AuthService;