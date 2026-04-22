// src/types/auth.ts

export interface RegisterPayload {
  nik: string;
  family_card_number: string;
  full_name: string;
  whatsapp_number: string;
  pin: string;
  pin_confirmation: string;
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

export interface LoginPayload {
  nik: string;
  pin: string;
}


export interface RegisterResponse {
  status: string;
  message: string;
  data?: {
    nik: string;
    whatsapp_number: string;
    full_name: string;
  };
}

export interface LoginResponse {
  user: any; 
  token: string;
}

export interface Citizen {
  id: number;
  nik: string;
  full_name: string;
  whatsapp_number: string;
  is_verified: boolean;
  last_login_at?: string;
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
  new_pin_confirmation: string; // Tambahkan ini
}