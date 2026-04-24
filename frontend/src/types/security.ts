export interface UpdatePinPayload {
  current_pin: string;
  new_pin: string;
  new_pin_confirmation: string;
}

export interface SecurityApiResponse {
  message: string;
}