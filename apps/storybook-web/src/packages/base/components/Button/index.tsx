import { CircularProgress } from '@mui/material'
import React, { ButtonHTMLAttributes } from 'react'
import styled from 'styled-components'

export interface PropsButton extends ButtonHTMLAttributes<HTMLButtonElement> {
  text?: string
  isLoading?: boolean
}

export const Button: React.FC<PropsButton> = (props) => {
  return (
    <ButtonStyle {...props}>
      <span className="emphasis">
        {props.isLoading ? <CircularProgress className="circular" /> : <>{props.text}</>}
      </span>
      {props.isLoading || props.children}
    </ButtonStyle>
  )
}

const ButtonStyle = styled.button`
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

  box-shadow: 0 0.25rem 0.25rem ${({ theme }) => theme.colors.button.shadow};
  border: none;
  border-radius: 0.4rem;
  background-color: ${({ theme }) => theme.colors.button.default};
  color: ${({ theme }) => theme.colors.text.default};
  svg {
    font-size: 1.6rem;
    margin-right: 0.3rem;
  }
  &:hover {
    opacity: 0.8;
  }
  .emphasis {
    text-transform: uppercase;
  }
  .circular {
    width: 1rem !important;
    height: 1rem !important;
    color: ${({ theme }) => theme.colors.text.default};
    svg {
      margin-right: 0;
    }
  }
  &:disabled {
    opacity: 0.7;
  }
`
