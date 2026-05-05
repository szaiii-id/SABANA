import { onMounted, onUnmounted } from 'vue';
import { useAuth } from './useAuth';

export function useIdleTimeout(timeoutMinutes = 3) {
  const { handleLogout } = useAuth();
  let timeoutId: ReturnType<typeof setTimeout>;
  
  const timeoutMs = timeoutMinutes * 60 * 1000; 

  const triggerAutoLogout = async () => {
    // 1. Tandai bahwa ini adalah logout paksa karena sesi habis
    localStorage.setItem('session_expired', 'true');
    
    // 2. Eksekusi logout (hapus token di BE & FE)
    await handleLogout();
    
    // 3. Alihkan ke halaman LOGIN sesuai permintaan Mas Akhmad
    window.location.href = '/login'; 
  };

  const resetTimer = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(triggerAutoLogout, timeoutMs);
  };

  const setupListeners = () => {
    const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => window.addEventListener(event, resetTimer));
    resetTimer(); 
  };

  const cleanupListeners = () => {
    const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => window.removeEventListener(event, resetTimer));
    clearTimeout(timeoutId);
  };

  onMounted(() => setupListeners());
  onUnmounted(() => cleanupListeners());
}