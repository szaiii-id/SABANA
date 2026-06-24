import type { AdminUser } from './auth';

export interface RegionData {
  regency: string | null;
  district: string | null;
  village: string | null;
}

export interface AccountData extends AdminUser {
  region: RegionData;
  regency_id?: string | null;
  district_id?: string | null;
  village_id?: string | null;
}

export interface AccountPayload {
  nip: string;
  name: string;
  password?: string;
  role: 'super_admin' | 'regency_admin' | 'district_admin' | 'village_officer';
  regency_id?: string;
  district_id?: string;
  village_id?: string;
  is_active?: boolean;
}

export interface AccountFilters {
  search?: string;
  role?: string;
  is_active?: boolean | undefined;
  page?: number;
  per_page?: number;
}