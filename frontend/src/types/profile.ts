export interface CitizenProfile {
  nik: string;
  family_card_number: string;
  full_name: string;
  whatsapp_number: string;
}

export interface UpdateProfilePayload {
  full_name?: string;
  whatsapp_number?: string;
}

export interface ProfileApiResponse {
  message: string;
  data?: CitizenProfile;
}