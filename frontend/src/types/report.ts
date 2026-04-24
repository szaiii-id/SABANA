export interface SendWhatsappPayload {
  pesan: string; 
}

export interface SendEmailPayload {
  nama: string;  
  email: string; 
  subjek: string; 
  pesan: string;  
}

export interface ReportApiResponse {
  status: string;
  message: string;
}