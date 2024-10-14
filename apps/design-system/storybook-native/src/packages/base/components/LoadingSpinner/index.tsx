import React from 'react'
import { ActivityIndicator, ViewProps } from 'react-native'
import styled from 'styled-components/native'

interface LoadingSpinnerProps extends ViewProps {
  title?: string
}
export const LoadingSpinner: React.FC<LoadingSpinnerProps> = props => {
  return (
    <Container {...props}>
      {props.children || (
        <>
          <Loading size="large" />
          <Text>{props.title}</Text>
        </>
      )}
    </Container>
  )
}
const Container = styled.View`
  justify-content: center;
  align-items: center;
  position: relative;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: ${({ theme }) => theme.colors.decorator.pure};
`
const Text = styled.Text``
const Loading = styled(ActivityIndicator)`
  color: ${({ theme }) => theme.colors.decorator.shadow};
`
