/** @format */

import type { Meta, StoryObj } from '@storybook/react'
import { Botao } from '../../../../packages/base/index.web'

Botao.defaultProps = {
  isLoading: false,
  texto: 'Botão',
  style: {
    backgroundColor: 'blue',
    color: 'white',
    padding: '10px',
    border: 'none',
    minWidth: '100px',
    borderRadius: '5px',
    cursor: 'pointer',
  },
}

const meta = {
  title: 'Componentes/Botao',
  component: Botao,
  parameters: {
    layout: 'centered',
    docs: {
      subtitle: 'Botão padrão do sistema.',
    },
  },
  tags: ['autodocs'],
  argTypes: {
    isLoading: {
      control: 'boolean',
      description: 'Indica se o botão está em estado de carregamento.',
      defaultValue: false,
    },
    texto: {
      control: 'text',
      description: 'Texto exibido dentro do botão.',
      defaultValue: 'Botão',
    },
    onClick: {
      action: 'clicked',
      description: 'Função chamada ao clicar no botão.',
    },
  },
} satisfies Meta<typeof Botao>

export default meta
type Story = StoryObj<typeof meta>

export const Primary: Story = {
  args: {
    isLoading: false,
    texto: 'Botão',
  },
}

export const Loading: Story = {
  args: {
    isLoading: true,
    texto: 'Carregando...',
  },
}

export const Disabled: Story = {
  args: {
    isLoading: false,
    texto: 'Desativado',
    disabled: true,
  },
}
