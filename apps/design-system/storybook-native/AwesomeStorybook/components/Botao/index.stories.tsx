/** @format */
import { Botao } from '@mobilestock/base/index.android'
import { Meta, StoryObj } from '@storybook/react'
import React from 'react'
import { View } from 'react-native'
import { ThemeProvider } from 'styled-components/native'
import { globalTema } from '../../theme'

Botao.defaultProps = {
  isLoading: false,
  text: 'Clique aqui',
  style: {
    width: 200,
    height: 50
  }
}

const meta: Meta<typeof Botao> = {
  title: 'Button',
  component: Botao,
  decorators: [
    Story => (
      <View style={{ padding: 16, alignSelf: 'center' }}>
        <ThemeProvider theme={globalTema}>
          <Story />
        </ThemeProvider>
      </View>
    )
  ],
  argTypes: {
    isLoading: {
      control: 'boolean',
      description: 'Indica se o botão está em estado de carregamento',
      defaultValue: false
    },
    text: {
      control: 'text',
      description: 'Texto exibido no botão',
      defaultValue: 'Clique aqui'
    },
    disabled: {
      control: 'boolean',
      description: 'Desativa o botão',
      defaultValue: false
    },
    style: {
      control: 'object',
      description: 'Estilo customizado para o botão'
    }
  }
}

export default meta

type Story = StoryObj<typeof meta>

export const Basic: Story = {
  args: {
    isLoading: false,
    text: 'Clique aqui'
  }
}

export const Loading: Story = {
  args: {
    isLoading: true,
    text: 'Carregando...'
  }
}

export const Disabled: Story = {
  args: {
    isLoading: false,
    text: 'Desativado',
    disabled: true
  }
}
