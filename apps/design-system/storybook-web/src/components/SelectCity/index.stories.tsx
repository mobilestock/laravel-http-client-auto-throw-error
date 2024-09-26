import type { Meta, StoryObj } from '@storybook/react';
import { FormHandles } from '@unform/core';
import { Form } from '@unform/web';
import { useRef } from 'react';
import { SelectCity } from '../../../packages/base/index';
import { api } from '../../services/api';
const meta = {
  title: 'Componentes/SelectCity',
  component: SelectCity,
  decorators: [
    Story => {
      const formRef = useRef<FormHandles | null>(null);


      return (
        <>
          {/* @ts-expect-error @ts-ignore */}
          <Form ref={formRef} onSubmit={() => {}} style={{ maxWidth: '400px', margin: 'auto' }}>
            <Story />
          </Form>
        </>
      );
    }
  ],
  parameters: {
    layout: 'centered',
    docs: {
      subtitle: 'Componente de seleção de cidade.'
    }
  }
} satisfies Meta<typeof SelectCity>

export default meta
type Story = StoryObj<typeof SelectCity>

export const UsoBasico: Story = {
  args: {
    name: 'cidade',
    label: 'Cidade',
    defaultValue: 'Belo Horizonte',
    placeholder: 'Selecione uma cidade',
    showErrorMessage: true,
    onChangeInput: (value) => console.log(value),
    fetchOptions: async (value) => {
      const response = await api.get(`api_administracao/cidades/pontos?pesquisa=${value}`)
      return response.data
    }
  }
}
