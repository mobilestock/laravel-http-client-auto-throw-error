import { fireEvent, render } from '@testing-library/react-native'
import React from 'react'

import { Text } from 'react-native'
import Button from '.'

describe('Button Component - Native', () => {
  it('deve renderizar sem erros', () => {
    render(global.app(<Button />))
  })

  it('deve exibir o texto passado via props', () => {
    const { getByText } = render(global.app(<Button text="Clique aqui" />))
    expect(getByText('Clique aqui')).toBeTruthy()
  })

  it('deve renderizar os filhos quando passados', () => {
    const { getByText } = render(global.app(
      <Button>
        <Text>Enviar</Text>
      </Button>,
    ))
    expect(getByText('Enviar')).toBeTruthy()
  })

  it('deve mostrar o indicador de carregamento quando isLoading for true', () => {
    const { getByTestId } = render(global.app(<Button isLoading={true} />))
    expect(getByTestId('loading-indicator')).toBeTruthy()
  })

  it('não deve mostrar o indicador de carregamento quando isLoading for false', () => {
    const { queryByTestId } = render(global.app(<Button isLoading={false} />))
    expect(queryByTestId('loading-indicator')).toBeNull()
  })

  it('deve chamar onPress quando pressionado', () => {
    const onPressMock = jest.fn()
    const { getByTestId } = render(global.app(<Button onPress={onPressMock} />))
    fireEvent.press(getByTestId('button'))
    expect(onPressMock).toHaveBeenCalled()
  })

  it('deve estar desabilitado quando a prop disabled for true', () => {
    const { getByTestId } = render(global.app(<Button disabled={true} />))
    const button = getByTestId('button')

    expect(button.props.accessibilityState.disabled).toBe(true)
  })

  it('deve aplicar o estilo personalizado quando textStyle for fornecido', () => {
    const customStyle = { fontSize: 20 }
    const { getByText } = render(global.app(<Button text="Estilo Personalizado" textStyle={customStyle} />))
    expect(getByText('Estilo Personalizado').props.style).toEqual(
      expect.arrayContaining([expect.objectContaining(customStyle)]),
    )
  })
})
