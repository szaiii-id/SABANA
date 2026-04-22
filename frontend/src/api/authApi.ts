import api from './axios'; 
import type { 
  RegisterPayload, 
  RegisterResponse,   
  VerifyOtpPayload, 
  ResendOtpPayload,
  LoginPayload,       
  LoginResponse,
  ForgotPinPayload,
  ResetPinPayload       
} from '../types/auth';

export const authEndpoint = {
  register: (data: RegisterPayload) => api.post<RegisterResponse>('auth/register', data),
  
  login: (data: LoginPayload) => api.post<LoginResponse>('auth/login', data),
  
  verifyRegistration: (data: VerifyOtpPayload) => api.post('auth/verify-registration', data),
  
  resendRegistrationOtp: (data: ResendOtpPayload) => api.post('auth/resend-registration-otp', data),

  forgotPin: (data: ForgotPinPayload) => api.post('auth/forgot-pin', data),

  resetPin: (data: ResetPinPayload) => api.post('auth/reset-pin', data),

  logout: () => api.post('auth/logout'),

};