import { SyntheticEvent, useEffect, useRef, useState } from 'react'
import { MdStore as Store } from 'react-icons/md'
import styled from 'styled-components'

import { Autocomplete, Box, ClickAwayListener } from '@mui/material'
import { useField } from '@unform/core'

import { tools } from '../../tools'

interface CityRequestProps {
  tem_ponto: boolean
  id: number
  nome: string
  uf: string
  latitude: number
  longitude: number
  label: string
}

interface SelectCityProps {
  name: string
  label?: string
  defaultValue?: string
  placeholder?: string
  showErrorMessage?: boolean
  onChangeInput: (value: unknown) => void
  fetchCities: (value: string) => Promise<CityRequestProps[]>
}

export const SelectCity = ({
  name,
  label,
  defaultValue,
  placeholder,
  showErrorMessage,
  onChangeInput,
  fetchCities
}: SelectCityProps): JSX.Element => {
  const [result, setResult] = useState<CityRequestProps[]>([])
  const [timer, setTimer] = useState<NodeJS.Timeout | null>(null)
  const [search, setSearch] = useState<string>('')
  const [value, setValue] = useState<CityRequestProps | null>(null)
  const [isSearching, setIsSearching] = useState(false)
  const [loading, setLoading] = useState(false)
  const { fieldName, registerField, error } = useField(name)
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (isSearching) getCityByName()
  }, [isSearching])

  useEffect(() => {
    if (defaultValue && result.length < 1) getCityById()
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

  async function getCityByName() {
    try {
      const searchString = tools.sanitizeString(search.trim().toLowerCase())
      if (searchString?.length <= 2) return
      setResult([])

      const data = await fetchCities(searchString)
      setResult(data)
    } catch (error) {
      console.error(error)
    } finally {
      setIsSearching(false)
      setLoading(false)
    }
  }

  async function getCityById() {
    try {
      setSearch('')
      setResult([])

      const data = await fetchCities(defaultValue?.toString() ?? '')
      setSearch(data[0]?.label ?? '')
      setResult(data)
      onChangeInput(data[0])
    } catch (error) {
      console.error(error)
    } finally {
      setIsSearching(false)
    }
  }

  function selectCity(selected: CityRequestProps) {
    onChangeInput(selected)
    setValue(selected)
    setSearch(selected?.label || '')
  }

  function checkEmpty() {
    const cityInput: any = document.getElementsByName('cidade')[0]
    if (!cityInput.value) {
      onChangeInput(null)
      setValue(null)
      setSearch('')
    }
  }

  return (
    <CityDiv>
      <div className="formDiv">
        <ClickAwayListener onClickAway={() => checkEmpty()}>
          <Autocomplete
            defaultValue={value || null}
            loading={loading}
            noOptionsText="Cidade não encontrada"
            options={result}
            value={value}
            getOptionLabel={option => option.label || ''}
            onChange={(_event: SyntheticEvent<Element, Event>, newValue: CityRequestProps | null) => {
              if (newValue) selectCity(newValue)
            }}
            onInput={() => {
              setLoading(true)
              if (timer) {
                clearTimeout(timer)
              }
              const timerNow = setTimeout(() => {
                setIsSearching(!isSearching)
              }, 500)
              setTimer(timerNow)
            }}
            onInputChange={(_event, newValue) => {
              setSearch(newValue)
            }}
            renderOption={(params, option) => {
              return (
                <Box component="li" {...params} key={option.id}>
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
    </CityDiv>
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
const CityDiv = styled.div`
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
