import '@testing-library/jest-dom'
import { fireEvent, render } from '@testing-library/react'

import Button from '.'

describe('Button Component - Web', () => {
  it('deve renderizar sem erros', () => {
    render(global.app(<Button />))
  })

  it('deve exibir o texto passado via props', () => {
    const { getByText } = render(global.app(<Button text="Clique aqui" />))
    getByText('Clique aqui')
  })

  it('deve renderizar os filhos quando passados', () => {
    const { getByText } = render(global.app(<Button>Enviar</Button>))
    getByText('Enviar')
  })

  it('deve mostrar o indicador de carregamento quando isLoading for true', () => {
    const { container } = render(global.app(<Button isLoading={true} />))
    const spinner = container.querySelector('.circular')
    expect(spinner).toBeInTheDocument()
  })

  it('não deve mostrar o indicador de carregamento quando isLoading for false', () => {
    const { container } = render(global.app(<Button isLoading={false} />))
    const spinner = container.querySelector('.circular')
    expect(spinner).toBeNull()
  })

  it('deve chamar onClick quando clicado', () => {
    const onClick = jest.fn()
    const { getByRole } = render(global.app(<Button onClick={onClick} />))
    fireEvent.click(getByRole('button'))
    expect(onClick).toHaveBeenCalled()
  })

  it('deve estar desabilitado quando a prop disabled for true', () => {
    const { getByRole } = render(global.app(<Button disabled={true} />))
    expect(getByRole('button')).toBeDisabled()
  })

  it('deve aplicar a classe personalizada quando className for fornecido', () => {
    const { getByRole } = render(global.app(<Button className="custom-class" />))
    expect(getByRole('button')).toHaveClass('custom-class')
  })
})
