import { Meta, StoryFn } from '@storybook/react'
import { Form } from '@unform/web'
import { FormInput, InputProps } from '../../../packages/base/index'

export default {
  title: 'Componentes/FormInput/Form',
  component: FormInput,
  parameters: {
    layout: "centered"
  },
  argTypes: {
    name: {
      control: 'text',
      defaultValue: 'inputName',
    },
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
  },
} as Meta

const Template: StoryFn<InputProps> = () => (
  // @ts-expect-error @ts-ignore
  <Form onSubmit={(data) => console.log(data)}>
    <FormInput
      name='default'
      placeholder='Digite algo...'
      style={{ border: '1px solid black', borderRadius: '5px' }}
    />
  </Form>
)

export const Default = Template.bind({})
Default.args = {
  name: 'default',
}
