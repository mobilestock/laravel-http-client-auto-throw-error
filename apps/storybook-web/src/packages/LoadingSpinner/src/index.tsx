import React, { InputHTMLAttributes } from 'react'
import { FaSyncAlt } from 'react-icons/fa'
import styled from 'styled-components'

const LoadingSpinner: React.FC<InputHTMLAttributes<HTMLInputElement>> = () => {
  return (
    <Spinner>
      <FaSyncAlt />
    </Spinner>
  )
}

const Spinner = styled.div`
  color: ${({ theme }) => theme.colors.text.default};
  height: 1.5rem;
  width: 1.5rem;
  @keyframes spin {
    from {
      transform: rotate(-360deg);
    }
    to {
      transform: rotate(0deg);
    }
  }
  svg {
    animation-name: spin;
    animation-duration: 2s;
    animation-iteration-count: infinite;
    animation-timing-function: linear;
  }
`

export default LoadingSpinner
