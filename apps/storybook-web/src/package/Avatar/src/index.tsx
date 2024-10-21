import React, { ImgHTMLAttributes } from 'react'
import styled from 'styled-components'

import LazyImage from './LazyImage'

const Avatar: React.FC<ImgHTMLAttributes<HTMLImageElement>> = props => <Image alt="Avatar image" {...props} />

const Image = styled(LazyImage)`
  border-radius: 50%;
`

export default Avatar
