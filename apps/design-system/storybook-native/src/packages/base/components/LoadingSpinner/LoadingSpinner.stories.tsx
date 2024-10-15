import React from 'react'
import { View } from 'react-native'

import { Meta, StoryObj } from '@storybook/react'

import { LoadingSpinner } from '../LoadingSpinner'

const meta: Meta<typeof LoadingSpinner> = {
  title: 'LoadingSpinner',
  component: LoadingSpinner,
  decorators: [
    Story => (
      <View style={{ padding: 16, alignSelf: 'center' }}>
        <Story />
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
