import '@testing-library/jest-dom'
import { render, screen } from '@testing-library/react'
import LoadingSpinner from '.'

describe('LoadingSpinner Component', () => {
  test('deve renderizar sem erros', () => {
    render(global.app(<LoadingSpinner />))
    const spinnerElement = screen.getByTestId('loading-spinner')
    expect(spinnerElement).toBeInTheDocument()
  })

  test('deve exibir o ícone de carregamento', () => {
    render(global.app(<LoadingSpinner />))
    const iconElement = screen.getByTestId('loading-spinner-icon')
    expect(iconElement).toBeInTheDocument()
    expect(iconElement).toContainHTML('svg')
  })

  test('deve ter os estilos aplicados corretamente', () => {
    render(global.app(<LoadingSpinner />))
    const spinnerElement = screen.getByTestId('loading-spinner')
    expect(spinnerElement).toHaveStyle(`
      height: 1.5rem;
      width: 1.5rem;
    `)
  })
})
