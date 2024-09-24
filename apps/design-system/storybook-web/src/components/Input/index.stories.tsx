import { Input, InputProps } from '@mobilestock/base'
import { Meta, StoryFn } from '@storybook/react'

Input.defaultProps = {
  placeholder: 'Digite algo...',
  style: {border: '1px solid black', borderRadius: '5px'}
}

export default {
  title: 'Componentes/FormInput/Input',
  component: Input,
  parameters: {
    layout: "centered"
  },
  argTypes: {
    label: {
      control: 'text',
      defaultValue: 'Label',
    },
    error: {
      control: 'text',
    },
    type: {
      control: 'text',
      defaultValue: 'text',
    },
    format: {
      control: false, // Desativar controle para funções
    },
  },
} as Meta

// Template básico para renderizar o Input
const Template: StoryFn<InputProps> = (args) => <Input {...args} />


export const Default = Template.bind({})
Default.args = {
  name: 'default',
}

export const Password = Template.bind({})
Password.args = {
  name: 'password',
  type: 'password',
}

export const WithError = Template.bind({})
WithError.args = {
  name: 'withError',
  error: 'Esse campo é obrigatório',
}
