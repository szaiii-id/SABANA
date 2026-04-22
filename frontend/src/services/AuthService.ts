import { authEndpoint } from '../api/authApi';
import type { 
  RegisterPayload, 
  LoginPayload, 
  VerifyOtpPayload, 
  ResendOtpPayload,
  ForgotPinPayload,
  ResetPinPayload
} from '../types/auth';

const AuthService = {
  async registerUser(payload: RegisterPayload) {
    const response = await authEndpoint.register(payload);
    return response.data;
  },

  async loginUser(payload: LoginPayload) {
    const response = await authEndpoint.login(payload);
    return response.data;
  },

  async verifyOtp(payload: VerifyOtpPayload) {
    const response = await authEndpoint.verifyRegistration(payload);
    return response.data;
  },

  async resendOtp(payload: ResendOtpPayload) {
    const response = await authEndpoint.resendRegistrationOtp(payload);
    return response.data;
  },

  async logoutUser() {
    const response = await authEndpoint.logout();
    return response.data;
  },

  async requestForgotPinOtp(payload: ForgotPinPayload) {
    const response = await authEndpoint.forgotPin(payload);
    return response.data;
  },

  async resetPin(payload: ResetPinPayload) {
    const response = await authEndpoint.resetPin(payload);
    return response.data;
  }
  
};

export default AuthService;