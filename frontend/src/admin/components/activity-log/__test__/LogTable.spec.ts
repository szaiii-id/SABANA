import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import LogTable from '../LogTable.vue'
import type { ActivityLog } from '../../../types/activityLog'

const mockLogs: ActivityLog[] = [
  {
    id: '1', actor_type: 'admin', actor_name: 'Admin 1', actor_role: 'super_admin',
    module: 'program', action: 'create', action_label: 'Membuat Program',
    target_type: 'program', target_name: 'Bantuan Beras',
    metadata: null, ip_address: null, created_at: '2024-01-01T10:00:00Z', source: 'activity_logs',
  },
  {
    id: '2', actor_type: 'citizen', actor_name: 'Joko', actor_role: 'citizen',
    module: 'submission', action: 'submit', action_label: 'Mengajukan Bantuan',
    target_type: null, target_name: null,
    metadata: null, ip_address: null, created_at: '2024-01-02T10:00:00Z', source: 'activity_logs',
  },
]

describe('LogTable', () => {
  it('menampilkan data log', () => {
    const wrapper = mount(LogTable, {
      props: { logs: mockLogs, loading: false, currentPage: 1, totalPages: 1, total: 2 },
    })
    expect(wrapper.text()).toContain('Admin 1')
    expect(wrapper.text()).toContain('Membuat Program')
    expect(wrapper.text()).toContain('Joko')
  })

  it('menampilkan loading state', () => {
    const wrapper = mount(LogTable, {
      props: { logs: [], loading: true, currentPage: 1, totalPages: 1, total: 0 },
    })
    expect(wrapper.text()).toContain('Memuat data')
  })

  it('menampilkan empty state', () => {
    const wrapper = mount(LogTable, {
      props: { logs: [], loading: false, currentPage: 1, totalPages: 1, total: 0 },
    })
    expect(wrapper.text()).toContain('Tidak ada log')
  })
})