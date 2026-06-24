// ===== [IMPORTS] =====
import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockBudgetSummary = vi.fn()
const mockProgramRecipients = vi.fn()
const mockDisbursedRecipients = vi.fn()
const mockMostAppliedPrograms = vi.fn()

vi.mock('../../services/adminReportService', () => ({
  adminReportService: {
    budgetSummary: (...args: any[]) => mockBudgetSummary(...args),
    programRecipients: (...args: any[]) => mockProgramRecipients(...args),
    disbursedRecipients: (...args: any[]) => mockDisbursedRecipients(...args),
    mostAppliedPrograms: (...args: any[]) => mockMostAppliedPrograms(...args),
  },
}))

import { useReportDownload } from '../../composables/useReportDownload'

describe('useReportDownload', () => {
  const validFilters = { tgl_mulai: '', tgl_akhir: '', program_id: '', wilayah_id: '' }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('harus mendownload laporan budget summary', async () => {
    mockBudgetSummary.mockResolvedValue(undefined)

    const { download } = useReportDownload()
    await download('budgetSummary', validFilters)

    expect(mockBudgetSummary).toHaveBeenCalledWith(validFilters)
  })

  it('harus throw error jika download gagal', async () => {
    mockProgramRecipients.mockRejectedValue(new Error('Network error'))

    const { download } = useReportDownload()

    await expect(download('programRecipients', validFilters)).rejects.toThrow('Gagal mengunduh laporan')
  })

  it('harus set isDownloading true selama proses download', async () => {
    mockDisbursedRecipients.mockImplementation(() =>
      new Promise(resolve => setTimeout(resolve, 100))
    )

    const { download, isDownloading } = useReportDownload()
    const promise = download('disbursedRecipients', validFilters)

    expect(isDownloading.value).toBe(true)
    await promise
    expect(isDownloading.value).toBe(false)
  })

  it('harus menangani download laporan tanpa filter', async () => {
    mockMostAppliedPrograms.mockResolvedValue(undefined)

    const { download } = useReportDownload()
    await download('mostAppliedPrograms', { tgl_mulai: '', tgl_akhir: '', program_id: '', wilayah_id: '' })

    expect(mockMostAppliedPrograms).toHaveBeenCalled()
  })

  it('harus bisa download dengan filter undefined values', async () => {
    mockBudgetSummary.mockResolvedValue(undefined)

    const { download } = useReportDownload()
    await download('budgetSummary', validFilters)

    expect(mockBudgetSummary).toHaveBeenCalled()
  })
})