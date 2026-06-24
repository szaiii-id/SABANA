// ==========================================
// PAYLOADS
// ==========================================

export interface RegisterPayload {
  nik: string;
  family_card_number: string;
  full_name: string;
  whatsapp_number: string;
  pin: string;
  pin_confirmation: string;
}

export interface LoginPayload {
  nik: string;
  pin: string;
}

export interface VerifyOtpPayload {
  nik: string;
  whatsapp_number: string;
  otp: string;
}

export interface ResendOtpPayload {
  nik: string;
  whatsapp_number: string;
}

export interface ForgotPinPayload {
  nik: string;
  whatsapp_number: string;
}

export interface ResetPinPayload {
  nik: string;
  whatsapp_number: string;
  otp: string;
  new_pin: string;
  new_pin_confirmation: string;
}

export interface UpdateProfilePayload {
  full_name: string;
  whatsapp_number: string;
}

export interface UpdatePinPayload {
  current_pin: string;
  new_pin: string;
  new_pin_confirmation: string;
}

// ==========================================
// RESPONSES
// ==========================================

export interface ApiResponse<T = any> {
  status: string;
  message: string;
  data?: T;
}

export interface CitizenData {
  nik: string;
  family_card_number: string;
  full_name: string;
  whatsapp_number: string;
  is_verified: boolean;
  last_login: string | null;
}

export interface LoginData {
  citizen: CitizenData;
  token: string;
  require_pin_change?: boolean;
}

export interface RegisterData {
  nik: string;
  family_card_number: string;
  full_name: string;
  whatsapp_number: string;
  is_verified: boolean;
  last_login: string | null;
}

export type RegisterResponse = ApiResponse<RegisterData>;
export type LoginResponse = ApiResponse<LoginData>;