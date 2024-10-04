import { Meta, StoryObj } from '@storybook/react'
import { Form } from '@unform/web'

import { FormInput } from './index'

const meta = {
  title: 'Componentes/FormInput/Form',
  component: FormInput,
  decorators: [
    Story => {
      return (
        // @ts-expect-error @ts-ignore
        <Form onSubmit={data => console.log(data)}>
          <Story
            name="default"
            placeholder="Digite algo..."
            style={{ border: '1px solid black', borderRadius: '5px' }}
          />
        </Form>
      )
    }
  ],
  parameters: {
    layout: 'centered'
  },
  argTypes: {
    name: {
      control: 'text',
      defaultValue: 'inputName'
    },
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
    }
  }
} satisfies Meta<typeof FormInput>

export default meta
type Story = StoryObj<typeof meta>

export const Default: Story = {
  args: {
    name: 'default',
    placeholder: 'Digite algo...'
  }
}
