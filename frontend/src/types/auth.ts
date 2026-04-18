export interface User {
    id: number;
    nik: string;
    full_name: string;
    whatsapp_number: string;
    last_login_at?: string;
}

export interface AuthResponse {
    status: string;
    message: string;
    data: {
        user: User;
        token: string;
    }
}