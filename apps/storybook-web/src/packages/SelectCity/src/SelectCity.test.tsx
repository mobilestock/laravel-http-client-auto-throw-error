import '@testing-library/jest-dom'
import { fireEvent, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { useField } from '@unform/core'
import SelectCity from '.'


jest.mock('@unform/core', () => ({
  useField: jest.fn()
}))

jest.mock('lodash.debounce', () => jest.fn((fn) => fn))


describe('SelectCity Component - Web', () => {
  const mockRegisterField = jest.fn()
  const mockOnChangeInput = jest.fn()
  const mockFetchCities = jest.fn()

  beforeEach(() => {
    jest.clearAllMocks();
    (useField as jest.Mock).mockReturnValue({
      fieldName: 'cidade',
      registerField: mockRegisterField,
      error: null
    })
  })

  const renderComponent = (props = {}) => {
    return render(global.app(
      <SelectCity
        name="cidade"
        onChangeInput={mockOnChangeInput}
        fetchCities={mockFetchCities}
        {...props}
      />
    ))
  }

  it('deve renderizar corretamente com props básicas', () => {
    renderComponent()
    const input = screen.getByRole('combobox')
    expect(4).toBe(3)
    expect(input).toBeInTheDocument()
  })

  it('deve renderizar com label e placeholder', () => {
    renderComponent({ label: 'Cidade', placeholder: 'Selecione uma cidade' })
    const label = screen.getByText('Cidade')
    const input = screen.getByPlaceholderText('Selecione uma cidade')
    expect(label).toBeInTheDocument()
    expect(input).toBeInTheDocument()
  })

  it('deve chamar fetchCities ao digitar no input após debounce', async () => {
    jest.setTimeout(10000)

    mockFetchCities.mockResolvedValue([
      { tem_ponto: true, id: 1, nome: 'Limeira', uf: 'SP', latitude: -23.55, longitude: -46.63, label: 'Limeira - SP' }
    ])

    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByRole('combobox')

    await userEvent.type(input, 'Limeira', { delay: 1 })

    await waitFor(() => {
      expect(mockFetchCities).toHaveBeenCalledWith('limeira')
    }, { timeout: 1000 })
  })

  it('deve exibir opções retornadas pela fetchCities', async () => {
    jest.setTimeout(10000)

    mockFetchCities.mockResolvedValue([
      {
        tem_ponto: true,
        id: 1,
        nome: 'Limeira',
        uf: 'SP',
        latitude: -23.55,
        longitude: -46.63,
        label: 'Limeira - SP'
      },
      {
        tem_ponto: true,
        id: 3,
        nome: 'Limeiras',
        uf: 'MG',
        latitude: -20.55,
        longitude: -43.19,
        label: 'Limeiras - MG'
      }
    ])

    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByRole('combobox')

    await userEvent.type(input, 'Limeira', { delay: 1 })

    await waitFor(() => {
      expect(mockFetchCities).toHaveBeenCalledWith('limeira')
    })

    const option1 = await screen.findByText('Limeira - SP')
    const option2 = await screen.findByText('Limeiras - MG')

    expect(option1).toBeInTheDocument()
    expect(option2).toBeInTheDocument()

    jest.useRealTimers()
  })

  it('deve selecionar uma cidade e chamar onChangeInput', async () => {
    const city = {
      tem_ponto: true,
      id: 1,
      nome: 'Limeira',
      uf: 'SP',
      latitude: -23.55,
      longitude: -46.63,
      label: 'Limeira - SP'
    }
    mockFetchCities.mockResolvedValue([city])

    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByPlaceholderText('Digite a cidade')

    await userEvent.type(input, 'Limeira')

    await waitFor(() => {
      expect(mockFetchCities).toHaveBeenCalledWith('limeira')
    })

    const option = await screen.findByText('Limeira - SP')
    expect(option).toBeInTheDocument()

    fireEvent.click(option)

    expect(mockOnChangeInput).toHaveBeenCalledWith(city)
    expect(input).toHaveValue('Limeira - SP')
  })

  it('deve exibir mensagem de erro quando showErrorMessage é true e há erro', () => {
    (useField as jest.Mock).mockReturnValue({
      fieldName: 'cidade',
      registerField: mockRegisterField,
      error: 'Cidade é obrigatória'
    })

    renderComponent({ showErrorMessage: true })
    const errorMessage = screen.getByText('Cidade é obrigatória')
    expect(errorMessage).toBeInTheDocument()
  })

  it('deve exibir a opção "Cidade não encontrada" quando não há resultados', async () => {
    mockFetchCities.mockResolvedValue([])

    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByPlaceholderText('Digite a cidade')

    await userEvent.type(input, 'XYZ')

    await waitFor(() => {
      expect(mockFetchCities).toHaveBeenCalledWith('xyz')
    })

    await waitFor(() => {
      const noOptions = screen.getByText('Cidade não encontrada')
      expect(noOptions).toBeInTheDocument()
    })
  })

  it('deve mostrar ícone Store quando tem_ponto é true', async () => {
    const city = {
      tem_ponto: true,
      id: 1,
      nome: 'Limeira',
      uf: 'SP',
      latitude: -23.55,
      longitude: -46.63,
      label: 'Limeira - SP'
    }
    mockFetchCities.mockResolvedValue([city])

    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByPlaceholderText('Digite a cidade')

    await userEvent.type(input, 'Limeira')

    await waitFor(() => {
      expect(mockFetchCities).toHaveBeenCalledWith('limeira')
    })

    const option = await screen.findByText('Limeira - SP')
    expect(option).toBeInTheDocument()
    expect(option.querySelector('svg')).toBeInTheDocument()
  })

  it('não deve chamar fetchCities se a string de busca tiver 2 caracteres ou menos', async () => {
    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByPlaceholderText('Digite a cidade')

    await userEvent.type(input, 'Li')

    await waitFor(() => {
      expect(mockFetchCities).not.toHaveBeenCalled()
    })
  })

  it('não deve exibir loading durante a busca se não há progressbar', async () => {
    mockFetchCities.mockImplementation(
      () =>
        new Promise((resolve) =>
          setTimeout(() => resolve([{
            tem_ponto: false,
            id: 2,
            nome: 'Cantagalo',
            uf: 'RJ',
            latitude: -22.91,
            longitude: -43.17,
            label: 'Cantagalo - RJ'
          }]), 1000)
        )
    )

    renderComponent({ placeholder: 'Digite a cidade' })
    const input = screen.getByPlaceholderText('Digite a cidade')

    await userEvent.type(input, 'Cantagalo')

    await waitFor(() => {
      expect(mockFetchCities).toHaveBeenCalledWith('cantagalo')
    })

    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument()
  })
})
