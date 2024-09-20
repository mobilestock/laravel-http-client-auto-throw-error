/** @format */

import { CircularProgress } from '@mui/material'
import React, { ButtonHTMLAttributes } from 'react'
import styled from 'styled-components'

export interface PropsBotao extends ButtonHTMLAttributes<HTMLButtonElement> {
  texto?: string
  isLoading?: boolean
}

export const Botao: React.FC<PropsBotao> = (props) => {
  return (
    <BotaoStyle {...props}>
      <span className="destaque">
        {props.isLoading ? <CircularProgress className="circular" /> : <>{props.texto}</>}
      </span>
      {props.isLoading || props.children}
    </BotaoStyle>
  )
}

const BotaoStyle = styled.button`
  display: flex;
  align-items: center;
  justify-content: center;

  width: 100%;
  min-height: 3rem;
  padding: 0.9rem 1rem;

  font-size: 0.9rem;
  font-family: 'Open Sans', sans-serif;
  font-weight: 400;

  cursor: pointer;

  box-shadow: 0 0.25rem 0.25rem var(--cor-sombra);
  border: none;
  border-radius: 0.4rem;
  background-color: var(--cor-primaria);
  color: var(--branco);
  svg {
    font-size: 1.6rem;
    margin-right: 0.3rem;
  }
  &:hover {
    opacity: 0.8;
  }
  .destaque {
    text-transform: uppercase;
  }
  .circular {
    width: 1rem !important;
    height: 1rem !important;
    color: var(--branco);
    svg {
      margin-right: 0;
    }
  }
  &:disabled {
    opacity: 0.7;
  }
`
