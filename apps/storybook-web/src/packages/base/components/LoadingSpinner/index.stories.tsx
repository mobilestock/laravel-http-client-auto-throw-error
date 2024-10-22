import type { Meta, StoryObj } from '@storybook/react'

import { LoadingSpinner } from './index'

const meta = {
  title: 'Componentes/LoadingSpinner',
  component: LoadingSpinner,
  tags: ['!dev'],
  parameters: {
    layout: 'centered',
    docs: {
      subtitle: 'Spinner de carregamento padrão do sistema.'
    }
  }
} satisfies Meta<typeof LoadingSpinner>

export default meta
type Story = StoryObj<typeof LoadingSpinner>

export const UsoBasico: Story = {}
