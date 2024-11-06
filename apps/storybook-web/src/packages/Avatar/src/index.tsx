import { ImgHTMLAttributes } from 'react'
import styled from 'styled-components'

import LazyImage from '@mobilestockweb/lazy-image'

export default function Avatar(props: ImgHTMLAttributes<HTMLImageElement>) {
  return <Image alt="Avatar image" {...props} />
}

const Image = styled(LazyImage)`
  border-radius: 50%;
`
