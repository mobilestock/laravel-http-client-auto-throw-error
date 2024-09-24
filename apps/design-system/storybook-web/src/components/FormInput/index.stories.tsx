import { FormInput } from '@mobilestock/base';
import { Meta, StoryObj } from '@storybook/react';
import { FormHandles } from '@unform/core';
import { Form } from '@unform/web';
import { useRef } from 'react';
import { ThemeProvider } from 'styled-components';
import * as Yup from 'yup';
import { ValidationError } from 'yup';
import { theme } from '../../../theme';

interface PropsErroYup {
  [key: string]: string
}

const meta = {
    title: 'Componentes/FormInput',
    component: FormInput,
    decorators: [
        (Story) => {
            const formRef = useRef<FormHandles | null>(null)
            return (
              <ThemeProvider theme={theme}>
                <Form
                    onSubmit={async () => {
                        try {
                            const schema = Yup.object().shape({
                                telefone: Yup.string()
                                  .required('Texto exibido em caso de erro')
                              })
                        await schema.validate({}, { abortEarly: false })
                        } catch (error) {
                            if (error instanceof ValidationError) {
                                const erros: PropsErroYup = {}
                                error.inner.forEach(erro => {
                                  if (erro.path) erros[erro.path] = erro.message
                                })
                                if (formRef) return formRef.current?.setErrors(erros)
                              }
                        }

                    }}
                    ref={formRef}
                    style={{ maxWidth: '400px', margin: 'auto' }}>
                  <Story />
                </Form>
              </ThemeProvider>
            )
        },
    ],
    parameters: {
        layout: 'centered',
        docs: {
            description: {
                component: 'Componente de entrada de formulário com integração ao Unform.',
            },
        },
    },
    args: {
        name: 'telefone',
        placeholder: 'Digite seu telefone...',
        type: 'tel',
        showErrorMessage: true,
        label: 'Telefone',
    },
    argTypes: {
        name: {
            control: 'text',
            description: 'Nome do campo utilizado pelo Unform para identificação.',
        },
        placeholder: {
            control: 'text',
            description: 'Placeholder exibido dentro do campo de entrada.',
        },
        type: {
            control: 'select',
            options: ['text', 'tel', 'email', 'password'],
            description: 'Tipo do campo de entrada.',
        },
        showErrorMessage: {
            control: 'boolean',
            description: 'Exibe mensagem de erro abaixo do campo, se houver.',
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
} satisfies Meta<typeof FormInput>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
    args: {
        name: 'telefone',
        placeholder: 'Digite seu telefone...',
    },
};

export const WithErrorMessage: Story = {
    args: {
        name: 'telefone',
        placeholder: 'Digite seu telefone...',
        showErrorMessage: true,
        label: 'Telefone com erro',
        defaultValue: '123 /asdf',
    },
    parameters: {
        docs: {
            description: {
                story: 'FormInput com uma mensagem de erro simulada.',
            },
        },
    },
};

export const PasswordInput: Story = {
    args: {
        name: 'senha',
        placeholder: 'Digite sua senha...',
        type: 'password',
        showErrorMessage: false,
        label: 'Senha',
    },
    parameters: {
        docs: {
            description: {
                story: 'Exemplo de campo de senha com o ícone de visibilidade.',
            },
        },
    },
};
