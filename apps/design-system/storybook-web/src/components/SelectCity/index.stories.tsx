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
  },
  args: {
    name: 'cidade',
    label: 'Cidade',
    defaultValue: 'Belo Horizonte',
    placeholder: 'Selecione uma cidade',
    showErrorMessage: true
  },
  argTypes: {
    fetchCities: {
      control: false,
      description: 'Função para buscar opções de cidade.'
    },
    onChangeInput: {
      control: false,
      description: 'Função para lidar com a seleção de uma cidade.'
    },
    showErrorMessage: {
      control: 'boolean',
      description: 'Exibe mensagem de erro abaixo do campo, se houver.'
    },
    name: {
      control: 'text',
      description: 'Nome do campo utilizado pelo Unform para identificação.'
    },
    label: {
      control: 'text',
      description: 'Rótulo exibido acima do campo de entrada.'
    },
    defaultValue: {
      control: 'text',
      description: 'Valor padrão do campo.'
    },
    placeholder: {
      control: 'text',
      description: 'Texto exibido dentro do campo de entrada.'
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
    onChangeInput: () => {},
    fetchCities: async (value) => {
      const response = await api.get(`http://localhost:8008/api_administracao/cidades/pontos?pesquisa=${value}`)
      return response.data
    }
  }
}
