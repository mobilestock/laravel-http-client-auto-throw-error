import { useField } from '@unform/core'
import React, { useCallback, useEffect, useRef, useState } from 'react'
import { StyleProp, StyleSheet, TextInputProps, TextStyle } from 'react-native'
import { TextInput } from 'react-native-gesture-handler'
import Feather from 'react-native-vector-icons/Feather'
import styled from 'styled-components/native'
import { globalTema } from '../../utils/theme-native'
import { Button } from '../Button/index.android'

interface InputProps extends TextInputProps {
  name: string
  label?: string
  labelStyles?: StyleProp<TextStyle>
  containerStyles?: StyleProp<TextStyle>
  errorMessageStyles?: StyleProp<TextStyle>
  errorInputStyles?: StyleProp<TextStyle>
  foco?: boolean
  format?: (value: string) => string
}
interface InputReference extends TextInput {
  value: string
}

export const GlobalTextInputUnform: React.FC<InputProps> = props => {
  const [visualizaSenha, setVisualizaSenha] = useState<boolean>(!!props.secureTextEntry)
  const inputRef = useRef<InputReference>(null)
  const { fieldName, registerField, defaultValue = '', error } = useField(props.name)
  useEffect(() => {
    if (inputRef.current) inputRef.current.value = defaultValue
  }, [defaultValue])

  useEffect(() => {
    registerField({
      path: undefined,
      name: fieldName,
      ref: inputRef.current,
      getValue() {
        if (inputRef.current) return inputRef.current.value
        return ''
      },
      setValue(ref, value) {
        if (inputRef.current) {
          inputRef.current.setNativeProps({ text: value })
          inputRef.current.value = value
        }
      },
      clearValue() {
        if (inputRef.current) {
          inputRef.current.setNativeProps({ text: '' })
          inputRef.current.value = ''
        }
      }
    })
    if (props.foco) {
      inputRef.current?.focus()
    }
  }, [fieldName, registerField, props.foco])
  const handleChangeText = useCallback(
    (value: string) => {
      if (inputRef.current) inputRef.current.value = value
      if (props.onChangeText) props.onChangeText(value)
      if (props.format && inputRef.current) {
        inputRef.current.value = props.format(value)
        inputRef.current.setNativeProps({ text: props.format(value) })
      }
    },
    [props.onChangeText]
  )
  return (
    <Container>
      {props.label && <Label style={props.labelStyles}>{props.label}</Label>}
      <ContainerInput
        style={[
          { borderWidth: 1, borderColor: 'transparent' },
          error
            ? [props?.errorInputStyles, props.style, styles.errorBorder]
            : [
                props.style,
                {
                  padding: 0
                }
              ]
        ]}
      >
        <Input
          ref={inputRef}
          onChangeText={handleChangeText}
          defaultValue={defaultValue}
          {...props}
          secureTextEntry={visualizaSenha}
          style={[
            props.style,
            {
              borderWidth: 0,
              padding: 5
            }
          ]}
        />
        {props.secureTextEntry && (
          <BotaoEsconderSenha
            onPress={() => setVisualizaSenha(!visualizaSenha)}
            underlayColor="transparent"
            style={{
              borderWidth: 0
            }}
          >
            <Icone error={!!error?.length} name={!visualizaSenha ? 'eye-off' : 'eye'} />
          </BotaoEsconderSenha>
        )}
      </ContainerInput>
      <TextoPequeno style={props?.errorMessageStyles}>{error}</TextoPequeno>
    </Container>
  )
}
const styles = StyleSheet.create({
  errorBorder: {
    borderColor: globalTema.cores.alerta80,
    color: globalTema.cores.alerta80
  },
  errorLabel: {
    color: globalTema.cores.alerta80
  },
  errorText: {
    color: globalTema.cores.alerta80,
    marginBottom: 10
  }
})
const Input = styled(TextInput)`
  flex: 1;
  min-height: ${({ theme }) => theme.layout.height(6)}px;
`
const Container = styled.View``
const ContainerInput = styled.View`
  flex-direction: row;
  overflow: hidden;
`
const BotaoEsconderSenha = styled(Button)`
  margin: 0;
  min-width: ${({ theme }) => theme.layout.width(12)}px;
  background-color: transparent;
`
const Icone = styled(Feather)<{ error: boolean }>`
  font-size: ${({ theme }) => theme.fonts.size(20)}px;
  color: ${({ theme, error }) => (error ? theme.cores.alerta80 : theme.cores.texto)};
`
const Label = styled.Text`
  color: ${({ theme }) => theme.cores.preto};
`
const TextoPequeno = styled.Text`
  color: ${({ theme }) => theme.cores.alerta80};
`
