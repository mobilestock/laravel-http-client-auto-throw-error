import { Meta, StoryFn } from '@storybook/react'

import Input, { InputProps } from './index'

export default {
  title: 'Componentes/Input',
  component: Input,
  tags: ['!dev'],
  parameters: {
    layout: 'centered'
  },
  argTypes: {
    label: {
      control: 'text',
      defaultValue: 'Label'
    },
    error: {
      control: 'text'
    },
    type: {
      control: 'text',
      defaultValue: 'text'
    },
    format: {
      control: false
    }
  }
} as Meta

const Template: StoryFn<InputProps> = args => <Input placeholder="Digite algo..." {...args} />

export const Default = Template.bind({})
Default.args = {
  name: 'default'
}

export const Password = Template.bind({})
Password.args = {
  name: 'password',
  type: 'password'
}

export const WithError = Template.bind({})
WithError.args = {
  name: 'withError',
  error: 'Esse campo é obrigatório'
}
