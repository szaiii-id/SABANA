export interface AspirasiPayload {
  nama: string;
  email: string;
  subjek: string;
  pesan: string;
}

export interface ApiResponse {
  message: string;
  data?: any;
}