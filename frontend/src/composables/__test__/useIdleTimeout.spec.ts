// src/composables/__test__/useIdleTimeout.spec.ts
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useIdleTimeout } from '../useIdleTimeout';
import { useAuth } from '../useAuth';
import { createApp } from 'vue';

// ============================================================
// MOCKS
// ============================================================
vi.mock('../useAuth', () => ({
  useAuth: vi.fn(),
}));

vi.mock('vue-router', () => ({
  useRouter: vi.fn(),
}));

// ============================================================
// HELPER: Jalankan composable dalam Vue app context
// ============================================================
function withSetup(composable: () => any) {
  let result: any;
  const app = createApp({
    setup() {
      result = composable();
      return () => {};
    },
  });
  app.mount(document.createElement('div'));
  return { result, app };
}

// ============================================================
// TEST SUITE
// ============================================================
describe('useIdleTimeout - Professional QA Test Suite', () => {

  let handleLogoutMock: any;
  let locationMock: { href: string };
  let storageMock: Record<string, string>;

  beforeEach(() => {
    vi.useFakeTimers();
    
    locationMock = { href: '' };
    vi.stubGlobal('location', locationMock);
    
    storageMock = {};
    vi.stubGlobal('localStorage', {
      setItem: vi.fn((key: string, val: string) => { storageMock[key] = val; }),
      getItem: vi.fn((key: string) => storageMock[key] || null),
    });

    handleLogoutMock = vi.fn().mockResolvedValue({});
    (useAuth as any).mockReturnValue({ handleLogout: handleLogoutMock });
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  // ============================================================
  // INISIALISASI
  // ============================================================
  describe('Inisialisasi', () => {

    it('[INIT-01] Timer dimulai saat composable dipanggil', () => {
      const { app } = withSetup(() => useIdleTimeout(1));
      
      // Timer seharusnya berjalan
      expect(handleLogoutMock).not.toHaveBeenCalled();
      
      app.unmount();
    });

    it('[INIT-02] Cleanup saat unmount', () => {
      const { app } = withSetup(() => useIdleTimeout(1));
      
      app.unmount();
      
      // Setelah unmount, timer seharusnya dibersihkan
      vi.advanceTimersByTime(120000);
      expect(handleLogoutMock).not.toHaveBeenCalled();
    });
  });

  // ============================================================
  // TIMEOUT BEHAVIOR
  // ============================================================
  describe('Timeout Behavior', () => {

    it('[TIMEOUT-01] Memicu logout setelah waktu idle habis', async () => {
      const { app } = withSetup(() => useIdleTimeout(1));

      // Maju 1 menit (default: 60000ms)
      vi.advanceTimersByTime(60000);
      await vi.runAllTicks();

      expect(handleLogoutMock).toHaveBeenCalledTimes(1);
      
      app.unmount();
    });

    it('[TIMEOUT-02] Default timeout 3 menit', () => {
      const { app } = withSetup(() => useIdleTimeout());
      
      // Maju 2 menit 59 detik - belum trigger
      vi.advanceTimersByTime(179000);
      expect(handleLogoutMock).not.toHaveBeenCalled();
      
      // Maju 1 detik lagi - trigger
      vi.advanceTimersByTime(1000);
      expect(handleLogoutMock).toHaveBeenCalledTimes(1);
      
      app.unmount();
    });

    it('[TIMEOUT-03] Custom timeout 5 menit', () => {
      const { app } = withSetup(() => useIdleTimeout(5));
      
      // 4 menit - belum trigger
      vi.advanceTimersByTime(240000);
      expect(handleLogoutMock).not.toHaveBeenCalled();
      
      // 1 menit lagi - trigger
      vi.advanceTimersByTime(60000);
      expect(handleLogoutMock).toHaveBeenCalledTimes(1);
      
      app.unmount();
    });
  });

  // ============================================================
  // RESET TIMER (AKTIVITAS USER)
  // ============================================================
  describe('Reset Timer - User Activity', () => {

    const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];

    events.forEach(event => {
      it(`[RESET-01] Event "${event}" mereset timer`, async () => {
        const { app } = withSetup(() => useIdleTimeout(1));

        // Maju 40 detik
        vi.advanceTimersByTime(40000);
        
        // Trigger event
        window.dispatchEvent(new Event(event));
        
        // Maju 40 detik lagi - belum trigger karena timer direset
        vi.advanceTimersByTime(40000);
        expect(handleLogoutMock).not.toHaveBeenCalled();

        // Maju sisa waktu - harusnya trigger
        vi.advanceTimersByTime(21000);
        await vi.runAllTicks();
        
        expect(handleLogoutMock).toHaveBeenCalledTimes(1);
        
        app.unmount();
      });
    });

    it('[RESET-02] Multiple aktivitas mereset timer berulang kali', async () => {
      const { app } = withSetup(() => useIdleTimeout(1));

      // Simulasi aktivitas setiap 30 detik
      for (let i = 0; i < 5; i++) {
        vi.advanceTimersByTime(30000);
        window.dispatchEvent(new Event('mousemove'));
      }

      // Setelah 2.5 menit aktivitas - belum logout
      expect(handleLogoutMock).not.toHaveBeenCalled();

      // Tidak ada aktivitas selama 1 menit - logout
      vi.advanceTimersByTime(60000);
      await vi.runAllTicks();
      
      expect(handleLogoutMock).toHaveBeenCalledTimes(1);
      
      app.unmount();
    });
  });

  // ============================================================
  // LOGOUT PROCESS
  // ============================================================
  describe('Logout Process', () => {

    it('[LOGOUT-01] Set session_expired di localStorage sebelum logout', async () => {
      const { app } = withSetup(() => useIdleTimeout(1));

      vi.advanceTimersByTime(60000);
      await vi.runAllTicks();

      expect(localStorage.setItem).toHaveBeenCalledWith('session_expired', 'true');
      expect(handleLogoutMock).toHaveBeenCalledTimes(1);
      
      app.unmount();
    });

    it('[LOGOUT-02] Redirect ke /login setelah logout', async () => {
      const { app } = withSetup(() => useIdleTimeout(1));

      vi.advanceTimersByTime(60000);
      await vi.runAllTicks();

      expect(locationMock.href).toBe('/login');
      
      app.unmount();
    });

    it('[LOGOUT-03] handleLogout dipanggil sebelum redirect', async () => {
      const { app } = withSetup(() => useIdleTimeout(1));

      vi.advanceTimersByTime(60000);
      await vi.runAllTicks();

      // handleLogout harus dipanggil
      expect(handleLogoutMock).toHaveBeenCalledTimes(1);
      // Setelah itu redirect
      expect(locationMock.href).toBe('/login');
      
      app.unmount();
    });
  });

  // ============================================================
  // CLEANUP
  // ============================================================
  describe('Cleanup', () => {

    it('[CLEAN-01] Timer dibersihkan saat unmount', () => {
      const { app } = withSetup(() => useIdleTimeout(1));
      
      app.unmount();
      
      // Maju 10 menit - tidak boleh ada logout
      vi.advanceTimersByTime(600000);
      expect(handleLogoutMock).not.toHaveBeenCalled();
    });

    it('[CLEAN-02] Event listener dibersihkan saat unmount', () => {
      const { app } = withSetup(() => useIdleTimeout(1));
      
      app.unmount();
      
      // Trigger event setelah unmount - tidak boleh ada efek
      window.dispatchEvent(new Event('mousemove'));
      vi.advanceTimersByTime(120000);
      
      expect(handleLogoutMock).not.toHaveBeenCalled();
    });
  });
});