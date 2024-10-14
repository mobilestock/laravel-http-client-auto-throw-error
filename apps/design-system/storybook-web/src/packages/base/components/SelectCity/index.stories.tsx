import { useRef } from 'react'

import type { Meta, StoryObj } from '@storybook/react'
import { FormHandles } from '@unform/core'
import { Form } from '@unform/web'

import { SelectCity } from './index'

const meta = {
  title: 'Componentes/SelectCity',
  component: SelectCity,
  decorators: [
    Story => {
      const formRef = useRef<FormHandles | null>(null)

      return (
        <>
          {/* @ts-expect-error @ts-ignore */}
          <Form
            ref={formRef}
            onSubmit={() => {
              console.log('Formulario enviado')
            }}
            style={{ maxWidth: '400px', margin: 'auto' }}
          >
            <Story />
          </Form>
        </>
      )
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
    placeholder: 'Selecione uma cidade'
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

const mockCities = [
  {
    tem_ponto: true,
    id: 1,
    nome: 'Belo Horizonte',
    uf: 'MG',
    latitude: -19.92083,
    longitude: -43.93778,
    label: 'Belo Horizonte, MG'
  },
  {
    tem_ponto: false,
    id: 2,
    nome: 'São Paulo',
    uf: 'SP',
    latitude: -23.55052,
    longitude: -46.63331,
    label: 'São Paulo, SP'
  },
  {
    tem_ponto: true,
    id: 3,
    nome: 'Rio de Janeiro',
    uf: 'RJ',
    latitude: -22.90642,
    longitude: -43.18223,
    label: 'Rio de Janeiro, RJ'
  },
  {
    tem_ponto: false,
    id: 4,
    nome: 'Salvador',
    uf: 'BA',
    latitude: -12.9714,
    longitude: -38.5014,
    label: 'Salvador, BA'
  },
  {
    tem_ponto: true,
    id: 5,
    nome: 'Nova Serrana',
    uf: 'MG',
    latitude: -19.9714,
    longitude: -98.5014,
    label: 'Nova Serrana, MG'
  },
  {
    tem_ponto: false,
    id: 6,
    nome: 'Xiq Xiq',
    uf: 'BA',
    latitude: -99.9666,
    longitude: -66.6555,
    label: 'Xiq Xiq, BA'
  }
]

export const UsoBasico: Story = {
  args: {
    name: 'cidade',
    label: 'Cidade',
    defaultValue: 'Belo Horizonte',
    placeholder: 'Selecione uma cidade',
    onChangeInput: () => console.log('Cidade selecionada'),
    fetchCities: async value => {
      return await new Promise(resolve => {
        const filteredCities = mockCities.filter(city => city.nome.toLowerCase().includes(value.toLowerCase()))
        setTimeout(() => {
          resolve(filteredCities)
        }, 250)
      })
    }
  }
}
