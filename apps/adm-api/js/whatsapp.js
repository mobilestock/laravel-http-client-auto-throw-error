class MensagensWhatsApp {
    urlWhatsApp = 'https://api.whatsapp.com/send/?phone=55'
    telefone = 0
    mensagem = ''
    resultado = ''
  
    constructor({mensagem,telefone}) {
      this.setMensagem(mensagem || '')
      this.setTelefone(telefone || 0)
  
      this.resultado = `${this.urlWhatsApp}${this.telefone}&text=${this.mensagem}`
    }
  
    setMensagem(mensagem) {
      const mensagemConvertidaParaURL = encodeURIComponent(mensagem)
      this.mensagem = mensagemConvertidaParaURL
    }
  
    setTelefone(telefone) {
      const telefoneTratado = telefone.replace(/[^0-9]/g, '')
      this.telefone = parseInt(telefoneTratado)
    }
  }