import { BaseSyntheticEvent } from "react"

export default {
  imageOnError(event: BaseSyntheticEvent<Event, EventTarget & HTMLImageElement>): void {
      event.target.src = '/resources/images/broken-image.png'
  },
  sanitizeString(texto: string): string {
    const textoFormatado = texto
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\-\-+/g, '')
      .replace(/(^-+|-+$)/, '')
      .replace(/[^a-z\s]/gi, '')

    return textoFormatado
  },
}

