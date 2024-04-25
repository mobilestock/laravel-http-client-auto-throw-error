function formataDataHora(datetime) {
  const formatadorData = new Intl.DateTimeFormat('pt-BR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  })

  const formatadorHora = new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })

  const dataFormatada = formatadorData.format(datetime)
  const horaFormatada = formatadorHora.format(datetime)

  return `${dataFormatada} ${horaFormatada}`
}
