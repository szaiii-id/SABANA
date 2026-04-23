import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useIdleTimeout } from '../useIdleTimeout';
import { useAuth } from '../useAuth';
import { createApp } from 'vue';

vi.mock('../useAuth', () => ({
  useAuth: vi.fn(),
}));

vi.mock('vue-router', () => ({
  useRouter: vi.fn(),
}));

// Helper untuk menjalankan composable dalam konteks aplikasi Vue
function withSetup(composable: () => any) {
  let result;
  const app = createApp({
    setup() {
      result = composable();
      return () => {};
    },
  });
  app.mount(document.createElement('div'));
  return [result, app] as const;
}

describe('useIdleTimeout', () => {
  let handleLogoutMock: any;
  const locationMock = { href: '' };

  beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('location', locationMock);
    
    // Mock LocalStorage agar tidak crash
    const storage: Record<string, string> = {};
    vi.stubGlobal('localStorage', {
      setItem: vi.fn((key, val) => { storage[key] = val }),
      getItem: vi.fn((key) => storage[key]),
    });
    
    handleLogoutMock = vi.fn().mockResolvedValue({});
    (useAuth as any).mockReturnValue({ handleLogout: handleLogoutMock });
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers();
  });

  it('memicu handleLogout setelah waktu idle habis', async () => {
    // Jalankan dalam context Vue
    withSetup(() => useIdleTimeout(1));

    // Maju 1 menit
    vi.advanceTimersByTime(60000);
    
    // Tunggu janji asinkronus (handleLogout) selesai
    await vi.runAllTicks();
    
    expect(handleLogoutMock).toHaveBeenCalled();
  });

  it('mereset timer jika ada aktivitas user', async () => {
    withSetup(() => useIdleTimeout(1));

    vi.advanceTimersByTime(40000);
    
    // Simulasikan aktivitas mousedown pada window
    window.dispatchEvent(new Event('mousedown'));

    vi.advanceTimersByTime(40000);
    expect(handleLogoutMock).not.toHaveBeenCalled();

    vi.advanceTimersByTime(21000);
    await vi.runAllTicks();
    
    expect(handleLogoutMock).toHaveBeenCalled();
  });
});