import { ChangeEvent, InputHTMLAttributes, MutableRefObject, forwardRef, useEffect, useState } from 'react'
import { MdOutlineVisibility, MdOutlineVisibilityOff } from 'react-icons/md'
import styled from 'styled-components'

import Button from '@mobilestockweb/button'

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: undefined | string | JSX.Element
  error?: string
  autoSubmitTelefone?: boolean
  format?: (value: string) => string
}

const Input = forwardRef<HTMLInputElement, InputProps>(function InputRef(
  { type = 'text', label, error, format, autoSubmitTelefone, name, ...rest }: InputProps,
  ref: MutableRefObject<HTMLInputElement>
) {
  const [isPassword, setIsPassword] = useState<boolean>(true)
  const [inputType, setInputType] = useState<InputHTMLAttributes<HTMLInputElement>['type']>(type)

  useEffect(() => {
    if (type === 'password') {
      setInputType(isPassword ? 'password' : 'text')
    }
  }, [isPassword, type])

  function onChange(event: ChangeEvent<HTMLInputElement>): void {
    let result = event.target.value
    if (format) {
      result = format(event.target.value)
    }
    if (event.target.type === 'tel' && autoSubmitTelefone && result.length === 15) {
      if (ref.current.form) {
        ref.current.form.requestSubmit()
      }
      ref.current.blur()
    }

    event.target.value = result
  }

  return (
    <ContainerInput $isError={!!error} $show={type !== 'hidden'}>
      {label && <label htmlFor={name}>{label}</label>}
      <div>
        <input id={name} onChange={onChange} ref={ref} type={inputType} {...rest} />
        {type === 'password' && (
          <ButtonIcon onClick={() => setIsPassword(old => !old)} type="button">
            {isPassword ? <MdOutlineVisibilityOff /> : <MdOutlineVisibility />}
          </ButtonIcon>
        )}
      </div>
      {error && <div className="erro">{error}</div>}
    </ContainerInput>
  )
})

const ContainerInput = styled.div<{ $isError: boolean; $show: boolean }>`
  display: ${({ $show }) => ($show ? 'block' : 'none')};
  label {
    display: ${({ $show }) => ($show ? 'block' : 'none')};
    font-family: 'Open Sans', sans-serif;
    font-size: 1rem;
    font-style: normal;
    font-weight: 400;
    line-height: 1rem;
  }
  div {
    display: flex;
    height: 3rem;
    margin-top: 0.3rem;
    position: relative;
    input {
      background-color: ${$isError =>
        $isError ? ({ theme }) => theme.colors.container.outline.default : ({ theme }) => theme.colors.text.contrast};
      border: none;
      -webkit-appearance: none;
      box-shadow: 0 0.25rem 0.25rem
        ${$isError =>
          $isError ? ({ theme }) => theme.colors.container.outline.default : ({ theme }) => theme.colors.text.contrast};
      display: ${({ $show }) => ($show ? 'flex' : 'none')};
      height: 100%;
      padding: 0 1rem;
      width: 100%;
    }
  }
  .erro {
    color: ${({ theme }) => theme.colors.alert.urgent};
    display: ${({ $show })=> ($show ? 'block' : 'none')};
    height: 1.5rem;
    margin-bottom: 1.5rem;
    margin-top: 0.3rem;
    width: 100%;
  }
`

const ButtonIcon = styled(Button)`
  background-color: transparent !important;
  border: none;
  box-shadow: none !important;
  color: ${({ theme }) => theme.colors.text.default};
  margin: 0 !important;
  padding: 0.5rem 1rem;
  position: absolute;
  right: 0;
  width: fit-content;
  svg {
    min-height: 1.9rem;
    min-width: 1.9rem;
  }
  @media (max-width: 325px) {
    padding: 0.5rem 0.6rem !important;
    svg {
      font-size: 2rem;
      width: 23px !important;
      height: 23px !important;
    }
  }
  @media (min-width: 326px) {
    svg {
      font-size: 1.8rem;
      width: 25px !important;
      height: 25px !important;
    }
  }
`

export default Input
