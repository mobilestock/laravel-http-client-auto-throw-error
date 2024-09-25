import { Meta, StoryObj } from '@storybook/react';
import { FormHandles } from '@unform/core';
import { Form } from '@unform/web';
import { useRef } from 'react';
import { ThemeProvider } from 'styled-components';
import * as Yup from 'yup';
import { ValidationError } from 'yup';
import { FormInput } from '../../../packages/base/index';
import { theme } from '../../../theme';

interface PropsErroYup {
  [key: string]: string;
}

const meta = {
  title: 'Componentes/FormInput/Formulario-Base',
  component: FormInput,
  decorators: [
    (Story) => {
      const formRef = useRef<FormHandles | null>(null);

      const handleSubmit = async (data: Record<string, unknown>) => {
        try {
          const schema = Yup.object().shape({
            telefone: Yup.string().required('Telefone é obrigatório.'),
          });
          await schema.validate(data, { abortEarly: false });
          console.log('Validação bem-sucedida:', data);
        } catch (error) {
          if (error instanceof ValidationError) {
            const erros: PropsErroYup = {};
            error.inner.forEach((erro) => {
              if (erro.path) erros[erro.path] = erro.message;
            });
            if (formRef.current) formRef.current.setErrors(erros);
          }
        }
      };

      return (
        <ThemeProvider theme={theme}>
          <Form ref={formRef} onSubmit={handleSubmit} style={{ maxWidth: '400px', margin: 'auto' }}>
            <Story />
          </Form>
        </ThemeProvider>
      );
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
      description: 'Texto exibido dentro do campo de entrada.',
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
