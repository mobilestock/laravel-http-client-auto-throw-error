import { render } from '@testing-library/react-native'
import React from 'react'
import { Text } from 'react-native'
import { ThemeProvider } from 'styled-components/native'
import LoadingSpinner from '.'
import { theme } from '../../../../utils/theme'

function withProviders(ui: React.ReactElement) {
  return <ThemeProvider theme={theme}>{ui}</ThemeProvider>
}

describe('LoadingSpinner', () => {
  it('deve renderizar o ActivityIndicator e o título quando children não são fornecidos', () => {
    const title = 'Carregando...'
    const { getByText, getByTestId } = render(withProviders(<LoadingSpinner title={title} />))

    expect(getByText(title)).toBeTruthy()
    expect(getByTestId('activity-indicator')).toBeTruthy()
  })

  it('deve renderizar os children quando fornecidos', () => {
    const childText = 'Conteúdo personalizado'
    const { getByText, queryByTestId } = render(
      withProviders(
        <LoadingSpinner>
          <Text>{childText}</Text>
        </LoadingSpinner>,
      ),
    )

    expect(getByText(childText)).toBeTruthy()
    expect(queryByTestId('activity-indicator')).toBeNull()
  })

  it('deve passar as props para o Container', () => {
    const testID = 'loading-spinner-container'
    const { getByTestId } = render(withProviders(<LoadingSpinner testID={testID} />))

    const container = getByTestId(testID)
    expect(container).toBeTruthy()
  })

  it('deve aplicar o estilo correto ao ActivityIndicator', () => {
    const { getByTestId } = render(withProviders(<LoadingSpinner />))
    const activityIndicator = getByTestId('activity-indicator')

    expect(activityIndicator.props.color).toBe(theme.colors.container.shadow)
    expect(activityIndicator.props.size).toBe('large')
  })

  it('deve corresponder ao snapshot quando children não são fornecidos', () => {
    const { toJSON } = render(withProviders(<LoadingSpinner title="Carregando..." />))
    expect(toJSON()).toMatchSnapshot()
  })

  it('deve corresponder ao snapshot quando children são fornecidos', () => {
    const { toJSON } = render(
      withProviders(
        <LoadingSpinner>
          <Text>Conteúdo personalizado</Text>
        </LoadingSpinner>,
      ),
    )
    expect(toJSON()).toMatchSnapshot()
  })
})
