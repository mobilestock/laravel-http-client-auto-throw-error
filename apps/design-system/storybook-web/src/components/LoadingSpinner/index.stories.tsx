import type { Meta, StoryObj } from '@storybook/react'
import { LoadingSpinner } from '../../../../packages/base/index.web'

const meta = {
  title: 'Componentes/LoadingSpinner',
  component: LoadingSpinner,
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
