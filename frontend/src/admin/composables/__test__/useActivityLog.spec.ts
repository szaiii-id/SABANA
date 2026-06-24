// resources/js/tests/composables/useActivityLog.spec.ts

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useActivityLog } from '../../composables/useActivityLog';
import { ActivityLogService } from '../../services/ActivityLogService';
import type { ActivityLog } from '../../types/activityLog';

vi.mock('../../services/ActivityLogService');

// ===== HELPERS =====
function validLogs(): ActivityLog[] {
  return [
    {
      id: '1',
      actor_type: 'admin',
      actor_name: 'Admin Test',
      actor_role: 'super_admin',
      module: 'program',
      action: 'create',
      action_label: 'Membuat Program',
      target_type: 'program',
      target_name: 'BLT',
      metadata: null,
      ip_address: null,
      created_at: '2026-06-07T10:00:00Z',
      source: 'activity_logs',
    },
  ];
}

function validMeta() {
  return { current_page: 1, total: 1, last_page: 1, per_page: 15 };
}

describe('useActivityLog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====
  it('fetchLogs mengisi data logs', async () => {
    const mockLogs = validLogs();
    const mockMeta = validMeta();
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({ data: mockLogs, meta: mockMeta });

    const { logs, fetchLogs, loading, total } = useActivityLog();

    expect(loading.value).toBe(false);
    await fetchLogs();

    expect(logs.value).toEqual(mockLogs);
    expect(total.value).toBe(1);
    expect(loading.value).toBe(false);
  });

  it('fetchLogs mengirim parameter filter', async () => {
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({ data: [], meta: validMeta() });

    const { fetchLogs } = useActivityLog();
    await fetchLogs({ module: 'program', page: 2 });

    expect(ActivityLogService.getAll).toHaveBeenCalledWith({ module: 'program', page: 2 });
  });

  // ===== SAD PATH =====
  it('fetchLogs mengosongkan logs saat error', async () => {
    vi.mocked(ActivityLogService.getAll).mockRejectedValue(new Error('Network Error'));

    const { logs, fetchLogs, loading } = useActivityLog();
    await fetchLogs();

    expect(logs.value).toEqual([]);
    expect(loading.value).toBe(false);
  });

  // ===== BOUNDARY =====
  it('fetchLogs menggunakan page default 1', async () => {
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({ data: [], meta: validMeta() });

    const { fetchLogs } = useActivityLog();
    await fetchLogs({});

    expect(ActivityLogService.getAll).toHaveBeenCalledWith(expect.objectContaining({ page: 1 }));
  });

  // ===== EDGE CASE =====
  it('fetchLogs handle response tanpa data', async () => {
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({ data: null as unknown as ActivityLog[], meta: validMeta() });

    const { logs, fetchLogs } = useActivityLog();
    await fetchLogs();

    expect(logs.value).toEqual([]);
  });

  // ===== NULL / EMPTY =====
  it('fetchLogs handle filter kosong', async () => {
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({ data: [], meta: validMeta() });

    const { fetchLogs } = useActivityLog();
    await fetchLogs({});

    expect(ActivityLogService.getAll).toHaveBeenCalledWith({ page: 1 });
  });

  // ===== LOADING STATE =====
  it('loading true selama fetchLogs', async () => {
    vi.mocked(ActivityLogService.getAll).mockImplementation(() => new Promise(r => setTimeout(() => r({ data: [], meta: validMeta() }), 50)));

    const { fetchLogs, loading } = useActivityLog();
    const promise = fetchLogs();

    expect(loading.value).toBe(true);
    await promise;
    expect(loading.value).toBe(false);
  });

  // ===== STATE — PAGE CHANGE =====
  it('currentPage berubah setelah fetchLogs', async () => {
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({
      data: [],
      meta: { current_page: 3, total: 100, last_page: 10, per_page: 10 },
    });

    const { fetchLogs, currentPage } = useActivityLog();
    await fetchLogs({ page: 3 });

    expect(currentPage.value).toBe(3);
  });

  // ===== TOTAL PAGES =====
  it('totalPages berubah setelah fetchLogs', async () => {
    vi.mocked(ActivityLogService.getAll).mockResolvedValue({
      data: [],
      meta: { current_page: 1, total: 50, last_page: 5, per_page: 10 },
    });

    const { fetchLogs, totalPages } = useActivityLog();
    await fetchLogs();

    expect(totalPages.value).toBe(5);
  });
});