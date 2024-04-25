function removeAcentos(texto) {
  if (!texto) return '';

  const textoFormatado = texto
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/\-\-+/g, "")
    .replace(/(^-+|-+$)/, "");

  return textoFormatado;
}
