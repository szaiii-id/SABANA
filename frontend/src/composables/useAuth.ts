import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AuthService from '../services/AuthService';
import type { RegisterPayload, LoginPayload } from '../types/auth'; 

export function useAuth() {
  const router = useRouter(); 
  const isSubmitting = ref(false);
  const authError = ref('');

  const submitRegistration = async (formData: RegisterPayload) => {
    isSubmitting.value = true;
    authError.value = '';
    
    try {
      await AuthService.registerUser(formData);
      return { success: true, phone: formData.whatsapp_number }; 
    } catch (error: any) {
      authError.value = error.response?.data?.message || 'Gagal terhubung ke server SABANA.';
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  const submitLogin = async (payload: LoginPayload) => {
    isSubmitting.value = true;
    authError.value = '';
    
    try {
      // 1. Tangkap response dan set tipe ke 'any' agar TypeScript tidak protes
      // saat kita mencoba mengakses properti .data di bawahnya.
      const response: any = await AuthService.loginUser(payload);
      
      // 2. EKSTRAKSI AMAN (Bulletproof)
      // Karena JSON Laravel bentuknya: { status: '...', message: '...', data: { user: {...}, token: '...' } }
      // Kita cek berlapis untuk mengambil isi data yang sesungguhnya.
      const token = response.data?.token || response.token;
      const user = response.data?.user || response.user;

      // 3. Jika setelah dicek ternyata token tidak ada, lempar error untuk dicegat blok Catch
      if (!token || !user) {
         console.error("Format data dari server tidak sesuai:", response);
         throw new Error("Gagal membaca struktur token dari server.");
      }
      
      // 4. Simpan Token & User ke Local Storage (DIJAMIN isinya string beneran, bukan "undefined")
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      
      return { 
        success: true,
        mustChangePin: user.must_change_pin || false, 
      };
      
    } catch (error: any) {
      if (error.response?.status === 422) {
        const errors = error.response.data.errors;
        
        if (errors?.is_verified) {
          return { 
            success: false, 
            needsVerification: true, 
            wa: errors.whatsapp_number, 
            message: errors.is_verified[0]
          };
        }
        
        authError.value = errors?.nik?.[0] || 'Kombinasi NIK dan PIN tidak cocok.';
      } else {
        console.error("Detail Error Login:", error);
        authError.value = error.response?.data?.message || 'Gagal terhubung ke server SABANA.';
      }
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  };

  const handleLogout = async () => {
    isSubmitting.value = true;
    try {
      await AuthService.logoutUser(); 
    } catch (error) {
      console.error('API logout error, clearing local session anyway.');
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      isSubmitting.value = false;
      router.push({ name: 'home' });
    }
  };

  return { 
    isSubmitting, 
    authError, 
    submitRegistration, 
    submitLogin, 
    handleLogout 
  };
}