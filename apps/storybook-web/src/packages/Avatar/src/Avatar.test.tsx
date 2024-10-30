import '@testing-library/jest-dom'
import { render } from '@testing-library/react'

import Avatar from '.'

jest.mock('@mobilestockweb/lazy-image', () => ((props: any) => <img {...props} />))

describe('Avatar Component - Web', () => {
  it('deve aceitar a prop src e renderizar a imagem com a URL correta', () => {
    const src = 'https://example.com/avatar.jpg'
    const { getByAltText } = render(<Avatar src={src} />)
    expect(getByAltText('Avatar image')).toHaveAttribute('src', src)
  })

  it('deve aceitar e aplicar a prop alt personalizada', () => {
    const alt = 'Imagem de perfil'
    const { getByAltText } = render(<Avatar alt={alt} />)
    expect(getByAltText(alt)).toBeInTheDocument()
  })

  it('deve aplicar a classe CSS corretamente', () => {
    const { container } = render(<Avatar className="custom-avatar" />)
    const image = container.querySelector('img')
    expect(image).toHaveClass('custom-avatar')
  })
})
