import { useField } from '@unform/core'
import React, { useEffect, useRef } from 'react'

import { Input, InputProps } from '../Input/index.web'

interface FormInputProps extends InputProps {
  name: string
}

export function FormInput(props: FormInputProps): JSX.Element {
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
