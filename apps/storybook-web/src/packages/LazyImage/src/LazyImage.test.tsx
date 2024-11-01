import '@testing-library/jest-dom'
import { fireEvent, render } from '@testing-library/react'
import LazyImage from '.'

interface IntersectionObserverMock extends IntersectionObserver {
  observe: jest.Mock
  unobserve: jest.Mock
  disconnect: jest.Mock
}

describe('LazyImage Component - Web', () => {
  let observe: jest.Mock
  let unobserve: jest.Mock
  let disconnect: jest.Mock
  let intersectionObserverMock: IntersectionObserverMock

  beforeEach(() => {
    observe = jest.fn()
    unobserve = jest.fn()
    disconnect = jest.fn()

    intersectionObserverMock = {
      observe,
      unobserve,
      disconnect,
      root: null,
      rootMargin: '0px',
      thresholds: [],
      takeRecords: jest.fn(),
    } as IntersectionObserverMock

    global.IntersectionObserver = jest.fn(() => intersectionObserverMock)
  })

  afterEach(() => {
    jest.clearAllMocks()
  })

  it('deve renderizar sem erros', () => {
    const { getByAltText } = render(<LazyImage src="image.jpg" />)
    expect(getByAltText('Base image')).toBeInTheDocument()
  })

  it('deve observar a imagem com IntersectionObserver', () => {
    render(<LazyImage src="image.jpg" />)
    expect(observe).toHaveBeenCalled()
  })

  it('deve definir o atributo src quando a imagem entrar no viewport', () => {
    const { getByAltText } = render(<LazyImage src="image.jpg" />)
    const image = getByAltText('Base image') as HTMLImageElement

    expect(image.src).toBe('')

    fireEvent.scroll(window);
    (global.IntersectionObserver as jest.Mock).mock.calls[0][0]([{ isIntersecting: true }])

    expect(image.src).toContain('image.jpg')
  })

  it('não deve definir o src se a imagem ainda não entrou no viewport', () => {
    const { getByAltText } = render(<LazyImage src="image.jpg" />)
    const image = getByAltText('Base image') as HTMLImageElement

    expect(image.src).toBe('');

    (global.IntersectionObserver as jest.Mock).mock.calls[0][0]([{ isIntersecting: false }])

    expect(image.src).toBe('')
  })

  it('deve chamar a função onError quando ocorre um erro de carregamento', () => {
    const { getByAltText } = render(<LazyImage src="image.jpg" />)
    const image = getByAltText('Base image') as HTMLImageElement

    fireEvent.error(image)

    expect(image.src).toContain('broken-image.png')
  })

  it('deve parar de observar a imagem após o carregamento do src', () => {
    const { getByAltText } = render(<LazyImage src="image.jpg" />)
    const image = getByAltText('Base image') as HTMLImageElement

    fireEvent.scroll(window);
    (global.IntersectionObserver as jest.Mock).mock.calls[0][0]([{ isIntersecting: true }])

    expect(unobserve).toHaveBeenCalledWith(image)
  })

  it('deve desconectar o IntersectionObserver ao desmontar o componente', () => {
    const { unmount } = render(<LazyImage src="image.jpg" />)

    unmount()

    expect(disconnect).toHaveBeenCalled()
  })
})
