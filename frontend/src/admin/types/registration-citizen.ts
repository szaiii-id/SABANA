export interface RegisteredCitizen {
  id: string;
  nik: string;
  full_name: string;
  family_card_number: string;
  whatsapp_number: string | null;
  is_verified: boolean;
  last_login_at: string | null;
  created_at: string;
}

export interface RegisterCitizenPayload {
  nik: string;
  full_name: string;
  family_card_number: string;
  whatsapp_number?: string | null;
  with_pin: boolean;
}

export interface RegisterCitizenResponse {
  status: string;
  message: string;
  data: {
    citizen?: RegisteredCitizen;
    access_pin?: string;
  };
}

export interface CitizenListFilters {
  search?: string;
  page?: number;
  per_page?: number;
}