import { useEffect, useRef } from 'react'

import { useField } from '@unform/core'

import Input, { InputProps } from '@mobilestock/input'

interface FormInputProps extends InputProps {
  name: string
}

export default function FormInput(props: FormInputProps) {
  const { fieldName, defaultValue, registerField, error } = useField(props.name)
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    registerField({
      name: fieldName,
      ref: inputRef,
      getValue: ref => ref.current.value,
      setValue: (ref, value) => {
        ref.current.value = value
      },
      clearValue: ref => {
        ref.current.value = ''
      }
    })
  }, [fieldName, registerField])

  return <Input ref={inputRef} defaultValue={defaultValue} error={error} {...props} />
}
