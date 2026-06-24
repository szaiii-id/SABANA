import adminApi from './axios';
import type { LoginPayload, LoginResponse } from '../types/auth';

export const adminAuthEndpoint = {
  login: (data: LoginPayload) => adminApi.post<LoginResponse>('gate/verify-nip', data),
  logout: () => adminApi.post('auth/logout'),
  ping: () => adminApi.get('profile'),
};