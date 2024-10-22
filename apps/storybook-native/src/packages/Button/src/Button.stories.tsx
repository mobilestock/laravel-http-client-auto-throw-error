import { Meta, StoryObj } from '@storybook/react'
import React from 'react'
import { View } from 'react-native'
import Button from './'

const meta: Meta<typeof Button> = {
  title: 'Button',
  component: Button,
  decorators: [
    (Story) => (
      <View style={{ padding: 16, alignSelf: 'center' }}>
        <Story />
      </View>
    ),
  ],
  parameters: {
    notes: `### Componente de botão
    Ele tem um comportamento de loading e de disabled.
    `,
  },
  argTypes: {
    isLoading: {
      control: 'boolean',
      description: 'Indica se o botão está em estado de carregamento',
      defaultValue: false,
    },
    text: {
      control: 'text',
      description: 'Texto exibido no botão',
      defaultValue: 'Clique aqui',
    },
    disabled: {
      control: 'boolean',
      description: 'Desativa o botão',
      defaultValue: false,
    },
  },
}

export default meta

type Story = StoryObj<typeof meta>

export const Basic: Story = {
  args: {
    isLoading: false,
    text: 'Clique aqui',
  },
}

export const Loading: Story = {
  args: {
    isLoading: true,
    text: 'Carregando...',
  },
}

export const Disabled: Story = {
  args: {
    isLoading: false,
    text: 'Desativado',
    disabled: true,
  },
}
