import React, { ReactNode, createContext, useContext, useState } from 'react'

import { api } from '../services/api'

export interface PropsMarker {
  lat: number
  lng: number
}

export interface PropsCidadesRequest {
  tem_ponto: boolean
  id: number
  nome: string
  uf: string
  latitude: number
  longitude: number
  label?: string
}

export interface PropsDadosClienteParaEndereco {
  razao_social: string
  telefone: string
  id_cidade: number
  cidade: string
  uf: string
}

export interface PropsVerificacaoEndereco extends PropsDadosClienteParaEndereco {
  cidade: string
  endereco: string
  uf: string
  endereco_completo: string
  numero: string
  tipo_entrega: null | 'MS' | 'ML' | 'PE'
  esta_verificado: boolean
  id_tipo_entrega: number | null
  enderecos_validacao: Array<string>
  fora_raio_entregador: boolean
  id_cidade: number
}

export interface PropsEntregadorLocal {
  latitude: number
  longitude: number
  raio: number
}

export interface PropsEntregadorProximo {
  latitude_origem: number
  longitude_origem: number
  latitude_destino: number
  longitude_destino: number
  distancia: number
  raio: number
}

export interface PropsEntregadoresProximos {
  entregadores_locais: Array<PropsEntregadorLocal>
  entregador_proximo: PropsEntregadorProximo
  coordernada_colaborador: PropsMarker
}

export type EnderecoAutoCompleteType = {
  logradouro: string
  numero?: string
  bairro: string
  cidade: string
  uf: string
  cep: string
  endereco_formatado?: string
  idCidade: number
}

export interface listarEnderecosClienteProps {
  id: number
  idCidade: number
  idUsuario: number
  apelido?: string
  esta_verificado: boolean
  eh_endereco_padrao: boolean
  logradouro: string
  numero: string
  complemento?: string
  ponto_de_referencia?: string
  bairro: string
  cidade: string
  uf: string
  cep: string
  latitude: string
  longitude: string
  nome_destinatario: string
  telefone_destinatario: string
}

export interface DadosNovoEnderecoProps {
  id_cidade: number
  nome_destinatario: string
  telefone_destinatario: string
  eh_endereco_padrao: boolean
  logradouro: string
  numero: string
  bairro: string
  complemento?: string
  ponto_de_referencia?: string
  apelido?: string
  cep?: string
  id_colaborador?: number
  id_endereco?: number
}

interface PropsEnderecoContext {
  verificaEnderecoCliente: () => Promise<PropsVerificacaoEndereco>
  buscarEntregadoresProximos: () => Promise<PropsEntregadoresProximos>
  novoEndereco: (dados: DadosNovoEnderecoProps) => Promise<number>
  autoCompletarEndereco: (endereco: string, cidadeEstado?: string) => Promise<EnderecoAutoCompleteType[]>
  listarEnderecosCliente: (idColaborador?: number | null) => Promise<listarEnderecosClienteProps[]>
  definirEnderecoSelecionado: (endereco: listarEnderecosClienteProps) => void
  definirEnderecoPadrao: (id_endereco: number) => Promise<void>
  excluirEndereco: (id_endereco: number) => Promise<void>
  enderecoSelecionado: listarEnderecosClienteProps | null
}

const useEnderecoContext = createContext<PropsEnderecoContext>({} as PropsEnderecoContext)

export const EnderecoProvider = ({ children }: { children: ReactNode }): JSX.Element => {
  const [enderecoSelecionado, setEnderecoSelecionado] = useState<listarEnderecosClienteProps | null>(null)

  async function verificaEnderecoCliente(): Promise<PropsVerificacaoEndereco> {
    const { data } = await api.get<PropsVerificacaoEndereco>('api_cliente/cliente/endereco/buscar_dados')
    return data
  }
  async function buscarEntregadoresProximos(): Promise<PropsEntregadoresProximos> {
    const resultado = await api.get<PropsEntregadoresProximos>('api_cliente/entregadores_proximos')
    return resultado.data
  }
  async function autoCompletarEndereco(endereco: string, cidadeEstado?: string): Promise<EnderecoAutoCompleteType[]> {
    const { data } = await api.get<EnderecoAutoCompleteType[]>(
      `/api_cliente/autocomplete_endereco?endereco=${endereco}&cidade_estado=${cidadeEstado}`
    )
    return data
  }
  async function novoEndereco(dados: DadosNovoEnderecoProps) {
    const { data } = await api.post<number>('api_cliente/cliente/endereco/novo', dados)
    return data
  }
  async function listarEnderecosCliente(idColaborador?: number | null): Promise<listarEnderecosClienteProps[]> {
    let url = 'api_cliente/cliente/endereco/listar'

    if (idColaborador) {
      url += `/${idColaborador}`
    }

    const { data } = await api.get<listarEnderecosClienteProps[]>(url)
    return data
  }
  function definirEnderecoSelecionado(endereco: listarEnderecosClienteProps) {
    setEnderecoSelecionado(endereco)
  }
  async function definirEnderecoPadrao(id_endereco: number) {
    await api.post('api_cliente/cliente/endereco/definir_padrao', { id_endereco })
  }
  async function excluirEndereco(id_endereco: number) {
    await api.delete(`api_cliente/cliente/endereco/excluir/${id_endereco}`)
  }

  return (
    <useEnderecoContext.Provider
      value={{
        verificaEnderecoCliente,
        buscarEntregadoresProximos,
        autoCompletarEndereco,
        novoEndereco,
        listarEnderecosCliente,
        definirEnderecoSelecionado,
        definirEnderecoPadrao,
        excluirEndereco,
        enderecoSelecionado
      }}
    >
      {children}
    </useEnderecoContext.Provider>
  )
}

export function useEndereco(): PropsEnderecoContext {
  return useContext(useEnderecoContext)
}
