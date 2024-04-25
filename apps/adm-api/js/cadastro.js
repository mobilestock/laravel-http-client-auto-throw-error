import pt from 'https://cdn.jsdelivr.net/npm/vuetify@2.5.8/lib/locale/pt.js'

var app = new Vue({
  el: '#app',
  vuetify: new Vuetify({
    lang: {
      locales: { pt },
      current: 'pt',
    },
  }),
  data() {
    return {
      nome_colaborador: '',
      id_usuario: 0,
      id_colaborador: document.getElementById('id_colaborador').value,
      bounce: 0,
      loading: false,
      carregandoZerandoEstoque: false,
      snackbar: {
        open: false,
        color: 'error',
        text: '',
      },
      informacoes_cadastro: [],
      editando: false,
      dialog_confirmacao: false,
      dialog_confirmacaoZerarEstoque: false,
      dialog_procurarEndereco: false,
      dialog_alterarEndereco: false,
      dialog_apagarEndereco: {
        exibir: false,
        id_endereco: null,
      },
      regimes: [
        { text: 'Juridico', value: 1 },
        { text: 'Fisico', value: 2 },
      ],
      seller_bloqueado: false,
      dialog_permissoes: false,
      loading_autocomplete: false,
      loading_buscando_enderecos: false,
      loading_alterando_endereco_padrao: null,
      loading_apagando_endereco: null,
      loading_buscando_por_endereco: false,
      loading_buscando_por_cidade: false,
      enderecoNovoEndereco: false,
      enderecosCliente: [],
      enderecosEncontrados: [],
      enderecoSelecionado: [],
      buscarPorEndereco: '',
      buscarPorCidade: '',
      permissoes: [],
      permissoes_usuario: [],
      alterarCidade: false,
      cidade: '',
      listaCidadesEncontradas: [],
      tipo_acesso_principal: [
        { text: 'Cliente', value: 'C' },
        { text: 'Fornecedor', value: 'F' },
        { text: 'Transporte', value: 'T' },
      ],
      acesso: '',
      cnpjFormatado: '',
      cpfFormatado: '',
      telefoneFormatado: '',
      loading_alterar_endereco: false,
      enderecoRegras: {
        endereco: [(v) => !!v || 'O endereço é obrigatório'],
        numero: [(v) => !!v || 'O número é obrigatório'],
        bairro: [(v) => !!v || 'O bairro é obrigatório'],
        cep: [(v) => !!v || 'O CEP é obrigatório'],
        cidade: [(v) => !!v || 'A cidade é obrigatória'],
        estado: [(v) => !!v || 'O estado é obrigatório'],
      },
    }
  },
  methods: {
    enqueueSnackbar(texto = 'Erro, contate a equipe de T.I.', cor = 'error') {
      this.snackbar = {
        open: true,
        color: cor,
        text: texto,
      }
    },
    debounce(funcao, atraso) {
      clearTimeout(this.bounce)
      this.bounce = setTimeout(() => {
        funcao()
        this.bounce = null
      }, atraso)
    },
    async buscaInformacoesCadastro() {
      try {
        this.loading = true
        const resposta = await api.get(`api_administracao/cadastro/busca/colaboradores/${this.id_colaborador}`)
        this.informacoes_cadastro = resposta.data
        this.cidade = {
          nome: `${this.informacoes_cadastro.cidade} ${this.informacoes_cadastro.uf}`,
          id: this.informacoes_cadastro.id_cidade,
        }
        this.cnpjFormatado = formataCnpj(this.informacoes_cadastro.cnpj)
        this.cpfFormatado = formataCpf(this.informacoes_cadastro.cpf)
        this.telefoneFormatado = formataTelefone(this.informacoes_cadastro.telefone)
        this.nome_colaborador = this.informacoes_cadastro.razao_social
        this.id_usuario = this.informacoes_cadastro.id_usuario
        if (this.informacoes_cadastro.eh_perfil_de_seller) {
          MobileStockApi(`api_administracao/fornecedor/verifica_seller_bloqueado/${this.id_colaborador}`)
            .then((resp) => resp.json())
            .then((json) => {
              this.seller_bloqueado = json.data
            })
        }
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Erro ao buscar informações do cadastro',
        )
      } finally {
        this.loading = false
      }
    },

    async alteraUsuario(dados) {
      try {
        this.loading = true

        const campos = dados.target.elements

        const camposNaoObrigatorios = ['cnpj', 'cpf', 'complemento', 'ponto_de_referencia', 'senha']

        Array.from(dados.target.querySelectorAll('input')).forEach((campo) => {
          if (
            !camposNaoObrigatorios.includes(campo.name) &&
            campo.type !== 'hidden' &&
            !campo.readOnly &&
            campo.value == ''
          ) {
            console.error(`O campo ${campo.name} não foi preenchido`)
            throw new Error('Todos os campos devem ser preenchidos')
          }
        })

        const telefoneFormatado = campos.telefone.value.replace(/[^0-9]/g, '')
        const cpfFormatado = campos.cpf.value.replace(/[^0-9]/g, '')
        const cnpjFormatado = campos.cnpj.value.replace(/[^0-9]/g, '')
        let senha_alterada = false
        let regime = this.informacoes_cadastro.regime
        const podeRemoverSenha = this.informacoes_cadastro.permissao.every((permissao) => permissao == 10)

        if (this.regime_selecionado) {
          regime = this.regime_selecionado
        }

        if (!regime) {
          throw new Error('O regime deve ser selecionado')
        }

        if (regime == 2 && cpfFormatado.length != 11) {
          throw new Error('O regime está definido como Físico. O CPF está inválido')
        }

        if (regime == 1 && cnpjFormatado.length != 14) {
          throw new Error('O regime está definido como Jurídico. O CNPJ está inválido')
        }

        if (telefoneFormatado.length != 11) {
          throw new Error('O telefone está inválido')
        }

        if ((this.informacoes_cadastro.senha || '') !== campos.senha.value) {
          senha_alterada = true
        }

        if (campos.senha.value === '' && !podeRemoverSenha) {
          throw new Error('Essa conta não pode ficar desprotegida. Defina uma senha')
        }

        await api.post('api_administracao/cadastro/edita_usuario', {
          colaborador: campos.razao_social.value,
          senha: campos.senha.value,
          cnpj: cnpjFormatado,
          cpf: cpfFormatado,
          telefone: telefoneFormatado,
          email: campos.email.value,
          regime: regime,
          usuario_meulook: campos.usuario_meulook.value,
          nome: campos.nome.value,
          id_usuario: this.id_usuario,
          senha_alterada: senha_alterada,
          id_colaborador: this.id_colaborador,
        })

        this.enqueueSnackbar(`O usuário ${this.informacoes_cadastro.razao_social} foi alterado com sucesso`, 'success')
        this.buscaInformacoesCadastro()
        this.editando = false
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar o usuário')
      } finally {
        this.loading = false
      }
    },
    async zerarEstoqueResponsavel() {
      try {
        this.carregandoZerandoEstoque = true
        await api.put(`api_administracao/fornecedor/zerar_estoque_responsavel/${this.id_colaborador}`)

        this.enqueueSnackbar('Estoque zerado com sucesso', 'success')
        this.dialog_confirmacaoZerarEstoque = false
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao tentar zerar estoque')
      } finally {
        this.carregandoZerandoEstoque = false
      }
    },
    defineRegime(dados) {
      this.regime_selecionado = dados.value
    },
    desbloqueiaSeller() {
      try {
        this.loading = true
        MobileStockApi(`api_administracao/fornecedor/desbloqueia_seller/${this.id_colaborador}`, {
          method: 'POST',
        })
          .then((resp) => resp.json())
          .then((json) => {
            if (json.status) location.reload()
          })
      } catch (error) {
        this.snackbar.text = error || 'Erro ao desbloquear Seller'
        this.snackbar.open = true
      } finally {
        this.loading = false
      }
    },
    bloqueiaSeller() {
      try {
        this.loading = true
        MobileStockApi(`api_administracao/fornecedor/bloqueia_seller/${this.id_colaborador}`, {
          method: 'POST',
        })
          .then((resp) => resp.json())
          .then((json) => {
            if (json.status) location.reload()
          })
      } catch (error) {
        this.snackbar.text = error || 'Erro ao bloquear Seller'
        this.snackbar.open = true
      } finally {
        this.loading = false
      }
    },
    buscaPermissoes() {
      try {
        this.loading = true
        MobileStockApi(`api_administracao/cadastro/busca_permissoes/${this.id_colaborador}`)
          .then((resp) => resp.json())
          .then((json) => {
            if (!json.status || !json.data) {
              throw new Error(json.message)
            }
            json.data.todas_permissoes.forEach((element) => {
              this.permissoes.push({
                text: `${element.nome}`,
                value: `${element.nivel_value}`,
              })
            })
            let permissoes = json.data.permissoes_usuario.permissao.split(',')
            json.data.todas_permissoes.forEach((element) => {
              permissoes.map((permissao) => {
                if (element.nivel_value == permissao) {
                  this.permissoes_usuario.push({
                    text: `${element.nome}`,
                    value: `${element.nivel_value}`,
                  })
                }
              })
            })
            this.dialog_permissoes = true
          })
      } catch (error) {
        this.snackbar.text = error || 'Erro ao buscar permissões'
        this.snackbar.open = true
      } finally {
        this.loading = false
      }
    },
    abreModalConfirmaçao(item) {
      this.dialog_confirmacao = true
      this.acesso = item.value
    },
    deletaPermissao() {
      try {
        this.loading = true
        this.dialog_permissoes = false
        MobileStockApi(`api_administracao/cadastro/acesso/delete`, {
          method: 'POST',
          body: JSON.stringify({
            id: this.id_usuario,
            acesso: this.acesso,
          }),
        })
          .then((resp) => resp.json())
          .then((json) => {
            if (json.status) location.reload()
          })
      } catch (error) {
        this.snackbar.text = error || 'Erro ao deletar permissão'
        this.snackbar.open = true
      } finally {
        this.loading = false
      }
    },
    async gerenciaPermissoes(e) {
      this.dialog_permissoes = false
      this.loading = true
      try {
        if (e?.target[1]['_value']?.value != undefined) {
          await api.post('api_administracao/cadastro/permissao', {
            id_usuario: this.id_usuario,
            nova_permissao: e.target[1]['_value'].value,
          })
        }

        if (e?.target[3]['_value']?.value != undefined) {
          await api.patch('api_administracao/cadastro/acesso_principal', {
            id_usuario: this.id_usuario,
            acesso: e.target[3]['_value'].value,
          })
        }

        if (e?.target[5]['_value']?.value != undefined) {
          await MobileStockApi(`api_administracao/cadastro/acesso/tipo`, {
            method: 'POST',
            body: JSON.stringify({
              id: this.id_colaborador,
              tipo: e.target[5]['_value'].value,
            }),
          })
        }

        location.reload()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao gerenciar permissões')
      } finally {
        this.loading = false
      }
    },
    formataCnpj(cnpj) {
      this.cnpjFormatado = formataCnpj(cnpj)
    },
    formataCpf(cpf) {
      this.cpfFormatado = formataCpf(cpf)
    },
    formataTelefone(telefone) {
      this.telefoneFormatado = formataTelefone(telefone)
    },
    selecionarEndereco(item) {
      this.enderecoSelecionado = item
      this.dialog_procurarEndereco = false
      this.dialog_alterarEndereco = true
    },
    async buscarPorEnderecoFn(pesquisa) {
      try {
        this.loading_buscando_por_endereco = true

        let query = new URLSearchParams({
          endereco: pesquisa,
          cidade_estado: this.cidade.nome,
        }).toString()

        const response = await api.get(`api_cliente/autocomplete_endereco?${query}`)

        if (response.data.length > 0) this.enderecosEncontrados = response.data
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar endereço')
      } finally {
        this.loading_buscando_por_endereco = false
      }
    },
    async buscarPorCidadeFn(pesquisa) {
      try {
        this.loading_buscando_por_cidade = true

        const response = await api.get(`api_administracao/cidades/pontos?pesquisa=${pesquisa}`)

        this.listaCidadesEncontradas = response.data.map((item) => {
          return {
            value: item.id,
            text: `${item.nome} ${item.uf}`,
          }
        })
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao buscar cidade')
      } finally {
        this.loading_buscando_por_cidade = false
      }
    },
    selecionaCidade(item) {
      const cidade = this.listaCidadesEncontradas.find((cidade) => cidade.id === item.id)

      this.cidade = {
        nome: `${cidade.text}`,
        id: cidade.value,
      }

      this.alterarCidade = false
      this.buscarPorCidade = ''
      this.buscarPorEndereco = ''
      this.enderecosEncontrados = []
      this.enderecoSelecionado = []
    },
    alterarCidadeFn() {
      this.alterarCidade = !this.alterarCidade
    },
    async alterarEndereco(dados) {
      try {
        this.loading_alterar_endereco = true

        const campos = dados.target.elements

        const camposNaoObrigatorios = ['editar_complemento', 'editar_ponto_de_referencia', 'editar_apelido']

        Array.from(dados.target.querySelectorAll('input')).forEach((campo) => {
          if (!camposNaoObrigatorios.includes(campo.name) && campo.value == '') {
            console.error(`O campo de endereço ${campo.name} não foi preenchido`)
            throw new Error('Todos os campos obrigatórios devem ser preenchidos')
          }
        })

        const body = {
          endereco: campos.editar_endereco.value,
          bairro: campos.editar_bairro.value,
          numero: campos.editar_numero.value,
          cep: campos.editar_cep.value,
          complemento: campos.editar_complemento.value,
          ponto_de_referencia: campos.editar_ponto_de_referencia.value,
          apelido: campos.editar_apelido.value,
          id_cidade: this.cidade.id,
          id_colaborador: this.id_colaborador,
          eh_endereco_padrao: 1,
        }

        await api.post('api_cliente/cliente/endereco/novo', body)

        this.buscaInformacoesCadastro()

        this.snackbar.text = `O endereço do usuário ${this.informacoes_cadastro.razao_social} foi alterado com sucesso`
        this.snackbar.color = 'success'
        this.snackbar.open = true

        this.limpar()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar o endereço usuário')
      } finally {
        this.loading_alterar_endereco = false
      }
    },
    async listarEnderecos() {
      try {
        this.loading_buscando_enderecos = true
        const enderecos = await api.get(
          `api_cliente/cliente/endereco/listar/${this.informacoes_cadastro.id_colaborador}`,
        )

        this.enderecosCliente = enderecos.data
        this.dialog_procurarEndereco = true
      } catch (error) {
        this.enqueueSnackbar(
          error?.response?.data?.message || error?.message || 'Erro ao recuperar os endereços do usuário',
        )
      } finally {
        this.loading_buscando_enderecos = false
      }
    },
    async definirEnderecoPadrao(endereco) {
      try {
        this.loading_alterando_endereco_padrao = endereco.id
        this.loading_alterar_endereco = true
        await api.post('api_cliente/cliente/endereco/definir_padrao', {
          id_endereco: endereco.id,
          id_colaborador: this.id_colaborador,
        })
        await this.listarEnderecos()
        this.buscaInformacoesCadastro()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar o endereço usuário')
      } finally {
        this.loading_alterando_endereco_padrao = null
        this.loading_alterar_endereco = false
      }
    },
    async apagarEndereco(id_endereco) {
      try {
        this.fecharModalApagarEndereco()
        this.loading_apagando_endereco = id_endereco
        this.loading_alterar_endereco = true
        await api.delete(`api_cliente/cliente/endereco/excluir/${id_endereco}?id_colaborador=${this.id_colaborador}`)
        await this.listarEnderecos()
        this.buscaInformacoesCadastro()
      } catch (error) {
        this.enqueueSnackbar(error?.response?.data?.message || error?.message || 'Erro ao atualizar o endereço usuário')
      } finally {
        this.loading_apagando_endereco = null
        this.loading_alterar_endereco = false
      }
    },
    abrirModalApagarEndereco(endereco) {
      this.dialog_apagarEndereco = {
        exibir: true,
        id_endereco: endereco.id,
      }
    },
    fecharModalApagarEndereco() {
      this.dialog_apagarEndereco = {
        exibir: false,
        id_endereco: null,
      }
    },
    limpar() {
      this.enderecosEncontrados = []
      this.enderecoSelecionado = []
      this.buscarPorEndereco = ''
      this.buscarPorCidade = ''
      this.editando = false
      this.dialog_procurarEndereco = false
      this.alterarCidade = false
      this.enderecoNovoEndereco = false
      this.dialog_alterarEndereco = false
    },
  },
  watch: {
    buscarPorEndereco(pesquisa) {
      if (!pesquisa) return
      this.debounce(async () => await this.buscarPorEnderecoFn(pesquisa), 1000)
    },
    buscarPorCidade(pesquisa) {
      if (!pesquisa) return
      this.debounce(async () => await this.buscarPorCidadeFn(pesquisa), 1000)
    },
  },
  async mounted() {
    this.buscaInformacoesCadastro()
  },
})
