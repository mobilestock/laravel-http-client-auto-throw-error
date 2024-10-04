import type { Meta, StoryObj } from '@storybook/react'

import { Button } from './index'

const meta = {
  title: 'Componentes/Button',
  component: Button,
  parameters: {
    layout: 'centered',
    docs: {
      subtitle: 'Botão padrão do sistema.'
    }
  },
  args: {
    isLoading: false,
    text: 'Botão',
    onClick: () => console.log('Clicou no botão')
  },
  argTypes: {
    isLoading: {
      control: 'boolean',
      description: 'Indica se o botão está em estado de carregamento.',
      defaultValue: false
    },
    text: {
      control: 'text',
      description: 'Texto exibido dentro do botão.',
      defaultValue: 'Botão'
    },
    onClick: {
      action: () => {
        alert('Clicou no botão')
      },
      description: 'Função chamada ao clicar no botão.',
      defaultValue: () => console.log('teste'),
      control: () => {
        alert('Clicou no botão')
      },
      type: { name: 'function' }
    }
  }
} satisfies Meta<typeof Button>

export default meta
type Story = StoryObj<typeof meta>

export const Primary: Story = {
  args: {
    isLoading: false,
    text: 'Botão'
  }
}

export const Loading: Story = {
  args: {
    style: { minWidth: '9rem' },
    isLoading: true,
    text: 'Carregando...',
    disabled: true
  }
}

export const Disabled: Story = {
  args: {
    isLoading: false,
    text: 'Desativado',
    disabled: true
  }
}
