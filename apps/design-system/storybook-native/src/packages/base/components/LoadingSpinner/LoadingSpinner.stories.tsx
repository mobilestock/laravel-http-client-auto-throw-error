import { Meta, StoryObj } from '@storybook/react'
import React from 'react'
import { View } from 'react-native'
import { ThemeProvider } from 'styled-components/native'
import { theme } from '../../theme'
import { LoadingSpinner } from '../LoadingSpinner'

const meta: Meta<typeof LoadingSpinner> = {
  title: 'LoadingSpinner',
  component: LoadingSpinner,
  decorators: [
    Story => (
      <View style={{ padding: 16, alignSelf: 'center' }}>
        <ThemeProvider theme={theme}>
          <Story />
        </ThemeProvider>
      </View>
    )
  ]
}

export default meta

type Story = StoryObj<typeof LoadingSpinner>

export const UsoBasico: Story = {}

export const ComTitulo: Story = {
  args: {
    title: 'Carregando...'
  }
}
