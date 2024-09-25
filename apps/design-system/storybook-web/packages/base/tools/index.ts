import { BaseSyntheticEvent } from "react"

export default {
  imageOnError(event: BaseSyntheticEvent<Event, EventTarget & HTMLImageElement>): void {
      event.target.src = '/resources/images/broken-image.png'
  }
}

