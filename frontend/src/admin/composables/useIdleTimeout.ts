import { ref, onMounted, onUnmounted, readonly } from 'vue';

interface IdleTimeoutOptions {
  timeoutMinutes?: number;
  enableWarning?: boolean;
  debug?: boolean;
}

export function useIdleTimeout(
  onIdleAction: () => void | Promise<void>,
  options: IdleTimeoutOptions = {}
) {
  const {
    timeoutMinutes = 30,
    enableWarning = true,
    debug = false
  } = options;

  const isWarning = ref(false);
  const remainingSeconds = ref(0);
  
  let idleTimer: ReturnType<typeof setTimeout> | null = null;
  let warningTimer: ReturnType<typeof setTimeout> | null = null;
  let countdownTimer: ReturnType<typeof setInterval> | null = null;
  let isActionExecuting = false;

  const TIMEOUT_MS = timeoutMinutes * 60 * 1000;
  const WARNING_BEFORE_MS = 60 * 1000;
  const WARNING_THRESHOLD_MS = TIMEOUT_MS - WARNING_BEFORE_MS;

  const log = (...args: unknown[]) => {
    if (debug) console.log('[IdleTimeout]', ...args);
  };

  const clearAllTimers = () => {
    if (idleTimer) clearTimeout(idleTimer);
    if (warningTimer) clearTimeout(warningTimer);
    if (countdownTimer) clearInterval(countdownTimer);
    idleTimer = null;
    warningTimer = null;
    countdownTimer = null;
  };

  const executeIdleAction = async () => {
    if (isActionExecuting) return;
    
    isActionExecuting = true;
    localStorage.setItem('admin_session_expired', 'true');
    
    try {
      await onIdleAction();
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Unknown error';
      console.error('[IdleTimeout] Error:', message);
    } finally {
      isActionExecuting = false;
    }
  };

  const startWarningCountdown = () => {
    isWarning.value = true;
    remainingSeconds.value = 60;
    
    countdownTimer = setInterval(() => {
      remainingSeconds.value--;
      
      if (remainingSeconds.value <= 0) {
        if (countdownTimer) clearInterval(countdownTimer);
        executeIdleAction();
      }
    }, 1000);
  };

  const resetTimer = () => {
    if (isActionExecuting) return;
    
    clearAllTimers();
    isWarning.value = false;
    remainingSeconds.value = 0;
    
    if (enableWarning && WARNING_THRESHOLD_MS > 0) {
      warningTimer = setTimeout(() => startWarningCountdown(), WARNING_THRESHOLD_MS);
    }
    
    idleTimer = setTimeout(() => {
      if (!enableWarning || !countdownTimer) {
        executeIdleAction();
      }
    }, TIMEOUT_MS);
  };

  const ACTIVITY_EVENTS = [
    'mousedown',
    'mousemove',
    'keydown',
    'touchstart',
    'scroll',
    'wheel'
  ];

  const handleVisibilityChange = () => {
    if (!document.hidden) resetTimer();
  };

  onMounted(() => {
    ACTIVITY_EVENTS.forEach(event => {
      window.addEventListener(event, resetTimer, { passive: true });
    });
    document.addEventListener('visibilitychange', handleVisibilityChange);
    resetTimer();
  });

  onUnmounted(() => {
    ACTIVITY_EVENTS.forEach(event => {
      window.removeEventListener(event, resetTimer);
    });
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    clearAllTimers();
  });

  return {
    isWarning: readonly(isWarning),
    remainingSeconds: readonly(remainingSeconds),
    resetTimer
  };
}