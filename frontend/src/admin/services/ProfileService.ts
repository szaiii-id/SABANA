import adminApi from '../api/axios';
import type { AdminUser } from '../types/auth';

export const profileService = {
  get: () => adminApi.get<{ status: string; data: AdminUser }>('profile'),
  update: (data: { name: string }) => adminApi.put<{ status: string; message: string; data: AdminUser }>('profile', data),
  updatePassword: (data: { current_password: string; new_password: string }) => adminApi.patch<{ status: string; message: string }>('profile/password', data),
};