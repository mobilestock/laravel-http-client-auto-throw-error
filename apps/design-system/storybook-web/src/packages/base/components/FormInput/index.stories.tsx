import { useRef } from 'react'
import * as Yup from 'yup'
import { ValidationError } from 'yup'

import { Meta, StoryObj } from '@storybook/react'
import { FormHandles } from '@unform/core'
import { Form } from '@unform/web'

import { FormInput } from './'

interface PropsErroYup {
  [key: string]: string
}

const meta = {
  title: 'Componentes/FormInput',
  component: FormInput,
  decorators: [
    (Story) => {
      const formRef = useRef<FormHandles | null>(null)

      const handleSubmit = async (data: Record<string, unknown>) => {
        try {
          const schema = Yup.object().shape({
            nome: Yup.string().required('Nome é obrigatório.'),
          })
          await schema.validate(data, { abortEarly: false })
          console.log('Validação bem-sucedida:', data)
        } catch (error) {
          if (error instanceof ValidationError) {
            const erros: PropsErroYup = {}
            error.inner.forEach((erro) => {
              if (erro.path) erros[erro.path] = erro.message
            })
            if (formRef.current) formRef.current.setErrors(erros)
          }
        }
      }

      return (
        <div>
          {/* @ts-expect-error @ts-ignore */}
          <Form ref={formRef} onSubmit={handleSubmit} style={{ maxWidth: '400px', margin: 'auto' }}>
            <Story />
          </Form>
        </div>
      )
    },
  ],
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: 'Componente de entrada de formulário com integração ao Unform e validação com Yup.',
      },
    },
  },
  args: {
    name: 'Nome',
    placeholder: 'Digite seu nome...',
    type: 'tel',
    label: 'Nome',
  },
  argTypes: {
    name: {
      control: 'text',
      description: 'Nome do campo utilizado pelo Unform para identificação.',
    },
    placeholder: {
      control: 'text',
      description: 'Texto exibido dentro do campo de entrada.',
    },
    type: {
      control: 'select',
      options: ['text', 'tel', 'email', 'password'],
      description: 'Tipo do campo de entrada.',
    },
    label: {
      control: 'text',
      description: 'Rótulo exibido acima do campo de entrada.',
    },
    format: {
      control: false,
      description: 'Função opcional para formatar o valor do campo.',
    },
  },
} satisfies Meta<typeof FormInput>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {
  args: {
    name: 'Nome',
    placeholder: 'Digite seu nome...',
  },
}

export const WithErrorMessage: Story = {
  args: {
    name: 'nome',
    label: 'Nome',
    placeholder: 'Digite seu nome...',
    error: 'Este campo é obrigatório.',
    defaultValue: '',
  },
  parameters: {
    docs: {
      description: {
        story: 'FormInput com uma mensagem de erro simulada.',
      },
    },
  },
}

export const PasswordInput: Story = {
  args: {
    name: 'senha',
    placeholder: 'Digite sua senha...',
    type: 'password',
    label: 'Senha',
  },
  parameters: {
    docs: {
      description: {
        story: 'Exemplo de campo de senha com o ícone de visibilidade.',
      },
    },
  },
}
