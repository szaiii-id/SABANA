import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

// ===== TYPES =====
interface IdleTimeoutOptions {
  timeoutMinutes?: number;
  enableWarning?: boolean;
  warningSeconds?: number;
  debug?: boolean;
}

// ===== COMPOSABLE =====
export function useIdleTimeout(
  onLogout?: () => Promise<void>,
  options: IdleTimeoutOptions = {}
) {
  const {
    timeoutMinutes = 3,
    enableWarning = false,
    warningSeconds = 60,
    debug = false,
  } = options;

  const router = useRouter();
  const isWarning = ref(false);
  const remainingSeconds = ref(warningSeconds);

  let timeoutId: ReturnType<typeof setTimeout>;
  let warningIntervalId: ReturnType<typeof setInterval>;
  let warningTimeoutId: ReturnType<typeof setTimeout>;

  const timeoutMs = timeoutMinutes * 60 * 1000;
  const warningMs = (timeoutMinutes * 60 - warningSeconds) * 1000;

  // ===== AUTO LOGOUT =====
  const triggerAutoLogout = async (): Promise<void> => {
    if (debug) console.log('[IdleTimeout] Auto-logout triggered');

    localStorage.setItem('session_expired', 'true');
    localStorage.removeItem('sabana_token');

    if (onLogout) {
      await onLogout();
    }

    await router.push({ name: 'login' });
  };

  // ===== WARNING =====
  const startWarning = (): void => {
    if (!enableWarning) {
      triggerAutoLogout();
      return;
    }

    if (debug) console.log('[IdleTimeout] Warning started');

    isWarning.value = true;
    remainingSeconds.value = warningSeconds;

    warningIntervalId = setInterval(() => {
      remainingSeconds.value--;

      if (remainingSeconds.value <= 0) {
        clearInterval(warningIntervalId);
        isWarning.value = false;
        triggerAutoLogout();
      }
    }, 1000);

    warningTimeoutId = setTimeout(() => {
      triggerAutoLogout();
    }, warningSeconds * 1000);
  };

  // ===== RESET =====
  const resetTimer = (): void => {
    clearTimeout(timeoutId);

    if (enableWarning) {
      isWarning.value = false;
      remainingSeconds.value = warningSeconds;
      clearInterval(warningIntervalId);
      clearTimeout(warningTimeoutId);
    }

    if (enableWarning) {
      timeoutId = setTimeout(startWarning, warningMs);
    } else {
      timeoutId = setTimeout(triggerAutoLogout, timeoutMs);
    }
  };

  // ===== LISTENERS =====
  const setupListeners = (): void => {
    const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => window.addEventListener(event, resetTimer));
    resetTimer();
  };

  const cleanupListeners = (): void => {
    const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => window.removeEventListener(event, resetTimer));
    clearTimeout(timeoutId);
    clearInterval(warningIntervalId);
    clearTimeout(warningTimeoutId);
  };

  // ===== LIFECYCLE =====
  onMounted(() => setupListeners());
  onUnmounted(() => cleanupListeners());

  return {
    isWarning,
    remainingSeconds,
  };
}