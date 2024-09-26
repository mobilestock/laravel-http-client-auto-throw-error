import { Autocomplete, Box, ClickAwayListener } from '@mui/material'
import { useField } from '@unform/core'
import { SyntheticEvent, useEffect, useRef, useState } from 'react'
import { MdStore as Store } from 'react-icons/md'
import styled from 'styled-components'

import tools from '../../tools'

interface PropsCidadesRequest {
  tem_ponto: boolean
  id: number
  nome: string
  uf: string
  latitude: number
  longitude: number
  label: string
}

interface PropsAutoSelect {
  name: string
  label?: string
  defaultValue?: string
  placeholder?: string
  showErrorMessage?: boolean
  onChangeInput: (value: unknown) => void
  fetchCities: (value: string) => Promise<PropsCidadesRequest[]>
}

export const SelectCity = ({
  name,
  label,
  defaultValue,
  placeholder,
  showErrorMessage,
  onChangeInput,
  fetchCities
}: PropsAutoSelect): JSX.Element => {
  const [resultado, setResultado] = useState<PropsCidadesRequest[]>([])
  const [timer, setTimer] = useState<NodeJS.Timeout | null>(null)
  const [campoDeBusca, setCampoDeBusca] = useState<string>('')
  const [valor, setValor] = useState<PropsCidadesRequest | null>(null)
  const [estaBuscando, setEstaBuscando] = useState(false)
  const [loading, setLoading] = useState(false)
  const { fieldName, registerField, error } = useField(name)
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (estaBuscando) buscaCidadePorNome()
  }, [estaBuscando])

  useEffect(() => {
    if (defaultValue && resultado.length < 1) buscaCidadePorId()
  }, [defaultValue])

  useEffect(() => {
    registerField({
      name: fieldName,
      ref: inputRef,
      getValue: ref => {
        return ref?.current?.value
      },
      setValue: (ref, value) => {
        ref.current.value = value
      }
    })
  }, [fieldName, registerField])

  async function buscaCidadePorNome() {
    try {
      const stringPesquisa = tools.sanitizaString(campoDeBusca.trim().toLowerCase())
      if (stringPesquisa?.length <= 2) return
      setResultado([])

      const data = await fetchCities(stringPesquisa)
      setResultado(data)
    } catch (error) {
      console.error(error)
    } finally {
      setEstaBuscando(false)
      setLoading(false)
    }
  }

  async function buscaCidadePorId() {
    try {
      setCampoDeBusca('')
      setResultado([])

      const data = await fetchCities(defaultValue?.toString() ?? '')
      setCampoDeBusca(data[0]?.label ?? '')
      setResultado(data)
      onChangeInput(data[0])
    } catch (error) {
      console.error(error)
    } finally {
      setEstaBuscando(false)
    }
  }

  function selecionaCidade(selected: PropsCidadesRequest) {
    onChangeInput(selected)
    setValor(selected)
    setCampoDeBusca(selected?.label || '')
  }

  function verificaVazio() {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const inputCidade: any = document.getElementsByName('cidade')[0]
    if (!inputCidade.value) {
      onChangeInput(null)
      setValor(null)
      setCampoDeBusca('')
    }
  }

  return (
    <CidadeDiv>
      <div className="formDiv">
        <ClickAwayListener onClickAway={() => verificaVazio()}>
          <Autocomplete
            defaultValue={valor || null}
            loading={loading}
            noOptionsText="Cidade não encontrada"
            options={resultado}
            value={valor}
            getOptionLabel={option => option.label || ''}
            onChange={(_event: SyntheticEvent<Element, Event>, newValue: PropsCidadesRequest | null) => {
              if (newValue) selecionaCidade(newValue)
            }}
            onInput={() => {
              setLoading(true)
              if (timer) {
                clearTimeout(timer)
              }
              const timerNow = setTimeout(() => {
                setEstaBuscando(!estaBuscando)
              }, 500)
              setTimer(timerNow)
            }}
            onInputChange={(_event, newValue) => {
              setCampoDeBusca(newValue)
            }}
            renderOption={(params, option) => {
              return (
                <Box component="li" {...params} key={option.id} >
                  {option.tem_ponto && <Store style={{ paddingTop: '0.3rem' }} />}
                  {option.label}
                </Box>
              )
            }}
            renderInput={params => (
              <AutoCompleteInput isError={!!error} ref={params.InputProps.ref}>
                {label && <label>{label}</label>}
                <input name="cidade" type="text" placeholder={placeholder} {...params.inputProps} />
                {showErrorMessage && error && <span className="error">{error}</span>}
              </AutoCompleteInput>
            )}
          />
        </ClickAwayListener>
      </div>
    </CidadeDiv>
  )
}

const AutoCompleteInput = styled.div<{ isError: boolean }>`
  label {
    font-family: 'Open Sans', sans-serif;
    font-style: normal;
    font-weight: 400;
    font-size: 1rem;
    line-height: 0.9375rem;
  }
  input {
    margin-top: 0.2rem;
    margin-bottom: 0.1rem;
    height: 3rem;
    box-shadow: 0px 4px 4px ${props => (props.isError ? 'rgba(101, 13, 73, 0.25)' : 'rgba(0, 0, 0, 0.25)')};
    border: none;
    background-color: ${props => (props.isError ? 'var(--alerta20)' : 'var(--branco)')};
    width: 100%;
    padding: 0 1rem;
  }
  .error {
    font-family: 'Open Sans', sans-serif;
    font-style: normal;
    font-weight: 400;
    font-size: 1rem;
    color: var(--vermelho80);
    line-height: 0.9375rem;
    margin-top: 0.3rem;
    width: 100%;
    height: 1.5rem;
  }
`
const CidadeDiv = styled.div`
  display: flex;
  .formDiv {
    width: 100%;
  }
  .moreDiv {
    display: flex;
    margin-top: 1.54rem;
    background: #ffffff;
    height: 3rem;
    width: 3rem;
    box-shadow: 0px 4px 4px rgb(0 0 0 / 25%);
    justify-content: center;
    align-items: center;
  }
  .optionsCidade:hover {
    background: var(--background);
    cursor: pointer;
  }
  .moreDiv:hover {
    cursor: pointer;
  }
`
