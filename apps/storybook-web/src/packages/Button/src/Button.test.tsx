import '@testing-library/jest-dom'
import { render, screen } from '@testing-library/react'
import { ThemeProvider } from 'styled-components'
import Button from '.'
import { theme } from '../../../../utils/theme'

function withProviders(ui: React.ReactNode) {
  return <ThemeProvider theme={theme}>{ui}</ThemeProvider>
}

describe('Button component', () => {
  it('deve renderizar o texto passado via props', () => {
    render(withProviders(<Button text="Enviar" />))
    expect(screen.getByText('Enviar')).toBeInTheDocument()
  })

  it('deve renderizar o CircularProgress quando isLoading for true', () => {
    render(withProviders(<Button isLoading />))
    expect(screen.getByRole('progressbar')).toBeInTheDocument()
  })

  it('não deve renderizar o CircularProgress quando isLoading for false', () => {
    render(withProviders(<Button text="Enviar" isLoading={false} />))
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument()
  })
})
