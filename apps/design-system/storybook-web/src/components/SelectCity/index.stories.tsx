import type { Meta, StoryObj } from '@storybook/react';
import { FormHandles } from '@unform/core';
import { Form } from '@unform/web';
import { useRef } from 'react';
import { SelectCity } from '../../../packages/base/index';

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
    defaultValue: 'São Paulo',
    placeholder: 'Selecione uma cidade',
    showErrorMessage: true,
    onChangeInput: (value) => console.log(value),
    fetchOptions: async (value) => {
      const response = await fetch(`http://192.168.0.140:8800/api_administracao/cidades/pontos?pesquisa=${value}`, { mode: 'no-cors' })
      const data = await response.json()
      return data
    }
  }
}
