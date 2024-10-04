import React, { PropsWithChildren } from 'react'
import { ActivityIndicator, StyleProp, TextStyle, TouchableHighlightProps } from 'react-native'
import styled, { css } from 'styled-components/native'

import { theme } from '../../utils/theme'

export interface PropsButton extends TouchableHighlightProps {
  text?: string
  isLoading?: boolean
  textStyle?: StyleProp<TextStyle>
}

export const Button: React.FC<PropsWithChildren<PropsButton>> = props => {
  return (
    <ButtonStyle {...props}>
      {props.isLoading ? (
        <ActivityIndicator color={theme.colors.decorator.pure} size={25} />
      ) : (
        <>{props.children ? props.children : <Text style={props.textStyle}>{props.text}</Text>}</>
      )}
    </ButtonStyle>
  )
}

const ButtonStyle = styled.TouchableHighlight`
  background-color: ${theme.colors.button.base};
  min-height: ${theme.layout.height(2)}px;
  justify-content: center;
  align-items: center;
  border-radius: ${theme.layout.size(1)}px;
  margin: ${theme.layout.size(1)}px;
  padding: ${theme.layout.size(1)}px;

  ${({ disabled }) =>
    disabled &&
    css`
      opacity: 0.5;
    `}
`

const Text = styled.Text`
  color: ${theme.colors.text.secondary};
  font-size: ${theme.fonts.size(3)}px;
  text-align: center;
`
