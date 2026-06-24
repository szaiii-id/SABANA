import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockSearchCitizenPaginated = vi.fn()
const mockGetPrograms = vi.fn()
const mockSubmitAssistance = vi.fn()

vi.mock('../../services/CitizenAssistanceService', () => ({
  CitizenAssistanceService: {
    searchCitizenPaginated: (...args: any[]) => mockSearchCitizenPaginated(...args),
    getPrograms: (...args: any[]) => mockGetPrograms(...args),
    submitAssistance: (...args: any[]) => mockSubmitAssistance(...args),
  },
}))

import { useCitizenAssistance } from '../useCitizenAssistance'

describe('useCitizenAssistance', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  // =============================================
  // SEARCH
  // =============================================
  it('searchCitizen tidak mencari jika query kurang dari 3 karakter', () => {
    const { searchCitizen, searchResults } = useCitizenAssistance()
    searchCitizen('ab')
    vi.advanceTimersByTime(500)
    expect(mockSearchCitizenPaginated).not.toHaveBeenCalled()
    expect(searchResults.value).toEqual([])
  })

  it('searchCitizen mencari dengan debounce 500ms', () => {
    const { searchCitizen } = useCitizenAssistance()
    mockSearchCitizenPaginated.mockResolvedValue({ data: [{ id: '1', full_name: 'Joko', nik: '6301' }], has_more: false })

    searchCitizen('Joko')
    vi.advanceTimersByTime(500)

    expect(mockSearchCitizenPaginated).toHaveBeenCalledWith('Joko', 1)
  })

  // =============================================
  // SELECT CITIZEN
  // =============================================
  it('selectCitizen mengisi selectedCitizen dan reset search', () => {
    const { selectCitizen, selectedCitizen, searchResults } = useCitizenAssistance()
    searchResults.value = [{ id: '1', full_name: 'Joko', nik: '6301', family_card_number: '6301', whatsapp_number: '62812' }]

    selectCitizen({ id: '1', full_name: 'Joko', nik: '6301', family_card_number: '6301', whatsapp_number: '62812' })

    expect(selectedCitizen.value).toBeTruthy()
    expect(searchResults.value).toEqual([])
  })

  // =============================================
  // FETCH PROGRAMS
  // =============================================
  it('fetchPrograms mengisi programs', async () => {
    mockGetPrograms.mockResolvedValue([{ id: '1', title: 'Bantuan Beras' }])
    const { fetchPrograms, programs } = useCitizenAssistance()
    await fetchPrograms()
    expect(programs.value).toHaveLength(1)
  })

  // =============================================
  // SUBMIT
  // =============================================
  it('submitAssistance return data sukses', async () => {
    mockSubmitAssistance.mockResolvedValue({ data: { data: { registration_number: 'SBN-ABC' } }, message: 'OK' })
    const { submitAssistance } = useCitizenAssistance()
    const result = await submitAssistance({ citizen_id: '1', program_id: '1', regency_id: '6301', district_id: '6301020', village_id: '6301020001', disbursement_method: 'village_cash', dynamicInputs: {}, files: {} })

    expect(result).toBeTruthy()
  })

  it('submitAssistance return null saat gagal', async () => {
    mockSubmitAssistance.mockRejectedValue({ response: { data: { message: 'Gagal.' } } })
    const { submitAssistance, errorMessage } = useCitizenAssistance()
    const result = await submitAssistance({ citizen_id: '1', program_id: '1', regency_id: '6301', district_id: '6301020', village_id: '6301020001', disbursement_method: 'village_cash', dynamicInputs: {}, files: {} })

    expect(result).toBeNull()
    expect(errorMessage.value).toBe('Gagal.')
  })

  // =============================================
  // RESET
  // =============================================
  it('resetForm membersihkan semua state', () => {
    const { resetForm, selectedCitizen, selectedProgram, searchResults } = useCitizenAssistance()
    selectedCitizen.value = { id: '1', full_name: 'Joko', nik: '6301', family_card_number: '6301', whatsapp_number: '62812' }
    selectedProgram.value = { id: '1', title: 'Test', slug: '', description: '', badge: '', banner_url: null, start_date: null, end_date: null, quota_total: null, benefit_amount: null, inputs: [], documents: [], has_submitted: false }
    searchResults.value = [{ id: '1', full_name: 'Joko', nik: '6301', family_card_number: '6301', whatsapp_number: '62812' }]

    resetForm()

    expect(selectedCitizen.value).toBeNull()
    expect(selectedProgram.value).toBeNull()
    expect(searchResults.value).toEqual([])
  })
})