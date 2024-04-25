function formataTelefone(value) {
  value = value.toString()
  const valueRaw = value.replace(/[^0-9]/g, '')
  let numberFormat = value.replace(/[^0-9]/g, '')

  if (valueRaw.length > 2) {
    numberFormat = '(' + numberFormat.slice(0, 2) + ')' + ' ' + numberFormat.slice(2, numberFormat.length)
  }
  if (valueRaw.length > 6 && valueRaw.length <= 10) {
    numberFormat = numberFormat.slice(0, 9) + ' ' + numberFormat.slice(9, numberFormat.length)
  }
  if (valueRaw.length > 10) {
    numberFormat = numberFormat.slice(0, 10) + '-' + numberFormat.slice(10, numberFormat.length)
  }
  if (valueRaw.length <= 11 && numberFormat.length <= 15) {
    return numberFormat
  } else {
    return numberFormat.slice(0,15)
  }
}