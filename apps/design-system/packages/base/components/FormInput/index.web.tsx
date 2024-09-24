// @issue https://github.com/mobilestock/frontend/issues/12
import { useField } from '@unform/core'
import React, { ChangeEvent, InputHTMLAttributes, useEffect, useRef, useState } from 'react'
import { MdOutlineVisibility, MdOutlineVisibilityOff } from 'react-icons/md'
import styled from 'styled-components'

import { Button } from '../Button/index.web'

interface PropsFormInput extends InputHTMLAttributes<HTMLInputElement> {
  name: string
  label?: undefined | string | JSX.Element
  showErrorMessage?: boolean
  format?: (value: string) => string
}

export function FormInput({ showErrorMessage = false, type = 'text', ...props }: PropsFormInput): JSX.Element {
  const [ocultarSenha, setOcultarSenha] = useState<boolean>(true)
  const [tipoInput, setTipoInput] = useState<InputHTMLAttributes<HTMLInputElement>['type']>(type)
  const { fieldName, defaultValue, registerField, error } = useField(props.name)
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (type === 'password') {
      setTipoInput(ocultarSenha ? 'password' : 'text')
    }
  }, [ocultarSenha])

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

  function onChange(evento: ChangeEvent<HTMLInputElement>): void {
    let resultado = evento.target.value
    if (props.format) {
      resultado = props.format(evento.target.value)
    }

    evento.target.value = resultado
  }

  return (
    <ContainerInput estaErrado={!!error} esconder={type === 'hidden'}>
      {props?.label && <label htmlFor={fieldName}>{props.label}</label>}
      <div>
        <input defaultValue={defaultValue} onChange={onChange} ref={inputRef} type={tipoInput} {...props} />
        {type === 'password' && (
          <BotaoIcone onClick={() => setOcultarSenha(old => !old)} type="button">
            {ocultarSenha ? <MdOutlineVisibilityOff /> : <MdOutlineVisibility />}
          </BotaoIcone>
        )}
      </div>
      {showErrorMessage && error && <div className="erro">{error || ''}</div>}
    </ContainerInput>
  )
}

const ContainerInput = styled.div<{ estaErrado: boolean; esconder: boolean }>`
  display: ${props => (props.esconder ? 'none' : 'block')};

  label {
    display: ${props => (props.esconder ? 'none' : 'block')};
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
      background-color: ${props => (props.estaErrado ? 'var(--alerta20)' : 'var(--branco)')};
      border: none;
      /* Ajustando box-shadow no iphone */
      -webkit-appearance: none;
      box-shadow: 0 0.25rem 0.25rem ${props => (props.estaErrado ? 'var(--alerta20)' : 'var(--cor-sombra)')};
      display: ${props => (props.esconder ? 'none' : 'flex')};
      height: 100%;
      padding: 0 1rem;
      width: 100%;
    }
  }

  .erro {
    color: var(--vermelho80);
    display: ${props => (props.esconder ? 'none' : 'block')};
    height: 1.5rem;
    margin-bottom: 1.5rem;
    margin-top: 0.3rem;
    width: 100%;
  }
`

const BotaoIcone = styled(Button)`
  background-color: transparent !important;
  border: none;
  box-shadow: none !important;
  color: var(--preto);
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
