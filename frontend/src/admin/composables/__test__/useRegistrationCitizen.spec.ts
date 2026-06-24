import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockGetList = vi.fn()
const mockCreate = vi.fn()
const mockResendPin = vi.fn()

vi.mock('../../api/registrationCitizenApi', () => ({
  registrationCitizenApi: {
    getList: (...args: any[]) => mockGetList(...args),
    create: (...args: any[]) => mockCreate(...args),
    resendPin: (...args: any[]) => mockResendPin(...args),
  },
}))

import { useRegistrationCitizen } from '../useRegistrationCitizen'

describe('useRegistrationCitizen', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  // =============================================
  // FETCH
  // =============================================
  it('fetchCitizens mengisi daftar warga', async () => {
    mockGetList.mockResolvedValue({
      data: { data: [{ id: '1', full_name: 'Joko' }], current_page: 1, last_page: 1, total: 1 },
    })

    const { citizens, loading, fetchCitizens } = useRegistrationCitizen()
    await fetchCitizens({})

    expect(citizens.value).toEqual([{ id: '1', full_name: 'Joko' }])
    expect(loading.value).toBe(false)
  })

  it('fetchCitizens mengisi error saat gagal', async () => {
    mockGetList.mockRejectedValue({
      response: { data: { message: 'Gagal memuat.' } },
    })

    const { errorMessage, fetchCitizens } = useRegistrationCitizen()
    await fetchCitizens({})

    expect(errorMessage.value).toBe('Gagal memuat.')
  })

  // =============================================
  // REGISTER
  // =============================================
  it('registerCitizen mengembalikan data sukses', async () => {
    mockCreate.mockResolvedValue({
      data: { status: 'success', message: 'OK', data: { citizen: { id: '1' }, access_pin: '123456' } },
    })

    const { registerCitizen } = useRegistrationCitizen()
    const result = await registerCitizen({
      nik: '6301234567890123', full_name: 'Joko', family_card_number: '6301234567890123',
      whatsapp_number: '6281234567890', with_pin: true,
    })

    expect(result).toEqual({ citizen: { id: '1' }, access_pin: '123456' })
  })

  it('registerCitizen mengembalikan null saat gagal', async () => {
    mockCreate.mockRejectedValue({
      response: { data: { message: 'NIK sudah terdaftar.' } },
    })

    const { registerCitizen, errorMessage } = useRegistrationCitizen()
    const result = await registerCitizen({
      nik: '6301234567890123', full_name: 'Joko', family_card_number: '6301234567890123',
      whatsapp_number: '6281234567890', with_pin: true,
    })

    expect(result).toBeNull()
    expect(errorMessage.value).toBe('NIK sudah terdaftar.')
  })

  // =============================================
  // RESEND PIN
  // =============================================
  it('resendPin mengembalikan data sukses', async () => {
    mockResendPin.mockResolvedValue({
      data: { status: 'success', message: 'OK', data: { access_pin: '654321' } },
    })

    const { resendPin } = useRegistrationCitizen()
    const result = await resendPin('uuid-1')

    expect(result).toEqual({ access_pin: '654321' })
  })

  it('resendPin mengembalikan null saat gagal', async () => {
    mockResendPin.mockRejectedValue({
      response: { data: { message: 'Warga tidak memiliki WhatsApp.' } },
    })

    const { resendPin, errorMessage } = useRegistrationCitizen()
    const result = await resendPin('uuid-1')

    expect(result).toBeNull()
    expect(errorMessage.value).toBe('Warga tidak memiliki WhatsApp.')
  })
})