import { fireEvent, render, waitFor } from '@testing-library/react'
import { useField } from '@unform/core'
import FormInput from '.'
import { theme } from '../../../../utils/theme'

jest.mock('@unform/core', () => ({
  useField: jest.fn()
}))

describe('FormInput Component - Web', () => {
  const mockRegisterField = jest.fn()
  const mockUseField = (fieldName: string, defaultValue = '', error = '') => {
    (useField as jest.Mock).mockReturnValue({
      fieldName,
      defaultValue,
      registerField: mockRegisterField,
      error
    })
  }

  beforeEach(() => {
    jest.clearAllMocks();
    (window.HTMLFormElement.prototype.requestSubmit as jest.Mock) = jest.fn()
  })

  it('deve renderizar o componente sem erros', () => {
    mockUseField('email')
    render(global.app(<FormInput name="email" />))
  })

  it('deve renderizar o valor padrão corretamente', () => {
    mockUseField('email', 'test@example.com')
    const { getByDisplayValue } = render(global.app(<FormInput name="email" />))
    expect(getByDisplayValue('test@example.com')).toBeInTheDocument()
  })

  it('deve registrar o campo corretamente', () => {
    mockUseField('email')
    render(global.app(<FormInput name="email" />))
    expect(mockRegisterField).toHaveBeenCalledWith({
      name: 'email',
      ref: expect.any(Object),
      getValue: expect.any(Function),
      setValue: expect.any(Function),
      clearValue: expect.any(Function)
    })
  })

  it('deve exibir o erro quando houver', () => {
    mockUseField('email', '', 'Campo obrigatório')
    const { getByText } = render(global.app(<FormInput name="email" />))
    expect(getByText('Campo obrigatório')).toBeInTheDocument()
  })

  it('deve permitir a alteração do valor do input', () => {
    mockUseField('email')
    const { getByRole } = render(global.app(<FormInput name="email" />))
    const input = getByRole('textbox')
    fireEvent.change(input, { target: { value: 'novo valor' } })
    expect((input as HTMLInputElement).value).toBe('novo valor')
  })

  it('deve limpar o valor do input quando solicitado', () => {
    mockUseField('email', 'valor inicial')
    const { getByRole } = render(global.app(<FormInput name="email" />))
    const input = getByRole('textbox')
    fireEvent.change(input, { target: { value: 'novo valor' } })
    fireEvent.change(input, { target: { value: '' } })
    expect((input as HTMLInputElement).value).toBe('')
  })

  it('deve renderizar o label corretamente', () => {
    mockUseField('username')
    const { getByLabelText } = render(global.app(<FormInput name="username" label="Username" />))
    expect(getByLabelText('Username')).toBeInTheDocument()
  })

  it('deve alternar a visibilidade da senha ao clicar no botão', () => {
    mockUseField('password')
    const { getByLabelText, getByRole } = render(global.app(<FormInput name="password" type="password"
                                                                             label="Password" />))
    const input = getByLabelText('Password') as HTMLInputElement
    const toggleButton = getByRole('button')

    expect(input.type).toBe('password')

    fireEvent.click(toggleButton)
    expect(input.type).toBe('text')

    fireEvent.click(toggleButton)
    expect(input.type).toBe('password')
  })

  it('deve aplicar a função de formatação corretamente', () => {
    const formatMock = jest.fn((value: string) => value.toUpperCase())
    mockUseField('formattedInput')
    const { getByRole } = render(global.app(<FormInput name="formattedInput" format={formatMock} />))
    const input = getByRole('textbox')

    fireEvent.change(input, { target: { value: 'test' } })
    expect(formatMock).toHaveBeenCalledWith('test')
    expect((input as HTMLInputElement).value).toBe('TEST')
  })

  it('deve submeter e limpar o input do tipo telefone quando o comprimento for 15', async () => {
    mockUseField('phone')
    const mockRequestSubmit = jest.fn()
    const mockBlur = jest.fn()

    const originalRequestSubmit = window.HTMLFormElement.prototype.requestSubmit
    window.HTMLFormElement.prototype.requestSubmit = mockRequestSubmit

    const originalBlur = HTMLInputElement.prototype.blur
    HTMLInputElement.prototype.blur = mockBlur

    const { getByRole } = render(
      global.app(
        <form onSubmit={mockRequestSubmit}>
          <FormInput name="phone" type="tel" autoSubmitTelefone />
        </form>
      )
    )

    const input = getByRole('textbox') as HTMLInputElement

    fireEvent.change(input, { target: { value: '123456789012345' } })

    await waitFor(() => {
      expect(mockRequestSubmit).toHaveBeenCalled()
      expect(mockBlur).toHaveBeenCalled()
    })

    HTMLInputElement.prototype.blur = originalBlur
    window.HTMLFormElement.prototype.requestSubmit = originalRequestSubmit
  })

  it('deve exibir o ícone de erro quando houver erro', () => {
    mockUseField('email', '', 'Campo inválido')
    const { getByText, getByRole } = render(global.app(<FormInput name="email" />))
    expect(getByText('Campo inválido')).toBeInTheDocument()
    const input = getByRole('textbox')
    expect(input).toHaveStyle(`box-shadow: 0 0.25rem 0.25rem ${theme.colors.container.outline.default}`)
  })
})
