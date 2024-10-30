import React from 'react'
import { ActivityIndicator, ViewProps } from 'react-native'
import styled, { useTheme } from 'styled-components/native'

interface LoadingSpinnerProps extends ViewProps {
  title?: string
}

const LoadingSpinner: React.FC<LoadingSpinnerProps> = (props) => {
  const theme = useTheme()
  return (
    <Container testID="loading-spinner-container" {...props}>
      {props.children || (
        <>
          <Loading testID="activity-indicator" size="large" color={theme.colors.container.shadow} />
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
  background-color: ${({ theme }) => theme.colors.container.default};
`
const Text = styled.Text``
const Loading = styled(ActivityIndicator)``

export default LoadingSpinner
