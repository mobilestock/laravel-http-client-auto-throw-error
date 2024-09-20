/** @format */

import React, { PropsWithChildren } from 'react'
import { ActivityIndicator, StyleProp, TextStyle, TouchableHighlightProps } from 'react-native'
import styled, { css } from 'styled-components/native'

import { globalTema } from '../../utils/theme-native'

export interface PropsBotao extends TouchableHighlightProps {
  text?: string
  isLoading?: boolean
  textStyle?: StyleProp<TextStyle>
}

export const Botao: React.FC<PropsWithChildren<PropsBotao>> = props => {
  return (
    <EstiloBotao {...props}>
      {props.isLoading ? (
        <ActivityIndicator color={globalTema.cores.branco} size={25} />
      ) : (
        <>{props.children ? props.children : <Texto style={props.textStyle}>{props.text}</Texto>}</>
      )}
    </EstiloBotao>
  )
}
const EstiloBotao = styled.TouchableHighlight`
  background-color: ${({ theme }) => theme.cores.corSecundaria};
  min-height: ${({ theme }) => theme.layout.height(2)}px;
  justify-content: center;
  align-items: center;
  border-radius: ${({ theme }) => theme.layout.size(2)}px;
  margin: ${({ theme }) => theme.layout.size(1)}px;

  ${({ disabled }) =>
    disabled &&
    css`
      opacity: 0.5;
    `}
`

const Texto = styled.Text`
  color: ${({ theme }) => theme.cores.branco};
  font-size: ${({ theme }) => theme.fonts.size(16)}px;
  text-align: center;
`
