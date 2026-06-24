// resources/js/tests/pages/activity-log/Index.spec.ts

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import { ref } from 'vue';
import Index from '../Index.vue';
import { useActivityLog } from '../../../composables/useActivityLog';

vi.mock('../../../composables/useActivityLog');

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: {} as any }],
});

function mockUseActivityLog(overrides: Record<string, unknown> = {}) {
  vi.mocked(useActivityLog).mockReturnValue({
    logs: ref([]),
    loading: ref(false),
    currentPage: ref(1),
    totalPages: ref(1),
    total: ref(0),
    fetchLogs: vi.fn(),
    ...overrides,
  } as any);
}

describe('ActivityLogIndex.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===== HAPPY PATH =====
  it('merender judul halaman', () => {
    mockUseActivityLog();
    const wrapper = mount(Index, {
      global: { plugins: [router], stubs: { LogFilter: true, LogTable: true } },
    });
    expect(wrapper.text()).toContain('Log Aktivitas');
  });

  // ===== MOUNTED =====
  it('memanggil fetchLogs saat mount', () => {
    const fetchMock = vi.fn();
    mockUseActivityLog({ fetchLogs: fetchMock });

    mount(Index, {
      global: { plugins: [router], stubs: { LogFilter: true, LogTable: true } },
    });

    expect(fetchMock).toHaveBeenCalled();
  });

  // ===== FILTER =====
  it('handleFilter memanggil fetchLogs dengan filter', async () => {
    const fetchMock = vi.fn();
    mockUseActivityLog({ fetchLogs: fetchMock });

    const wrapper = mount(Index, {
      global: { plugins: [router], stubs: { LogFilter: true, LogTable: true } },
    });

    await flushPromises();
    const filter = wrapper.findComponent({ name: 'LogFilter' });
    await filter.vm.$emit('filter', { module: 'program' });

    expect(fetchMock).toHaveBeenCalledWith({ module: 'program', page: 1 });
  });

  // ===== PAGE CHANGE =====
  it('handlePageChange memanggil fetchLogs dengan page', async () => {
    const fetchMock = vi.fn();
    mockUseActivityLog({ fetchLogs: fetchMock });

    const wrapper = mount(Index, {
      global: { plugins: [router], stubs: { LogFilter: true, LogTable: true } },
    });

    await flushPromises();
    const table = wrapper.findComponent({ name: 'LogTable' });
    await table.vm.$emit('pageChange', 3);

    expect(fetchMock).toHaveBeenCalledWith({ page: 3 });
  });
});