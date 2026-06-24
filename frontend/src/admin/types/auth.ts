export interface AdminUser {
  id: string;
  nip: string;
  name: string;
  role: 'super_admin' | 'regency_admin' | 'district_admin' | 'village_officer';
  is_active: boolean;
  last_login_at: string | null;
  created_at: string;
  region?: {
    regency: string | null;
    district: string | null;
    village: string | null;
  };
}

export interface LoginPayload {
  nip: string;
  password: string;
}

export interface LoginResponseData {
  admin: AdminUser;
  token: string;
}

export interface LoginResponse {
  status: string;
  message: string;
  data: LoginResponseData;
  token?: string;
  admin?: AdminUser;
}