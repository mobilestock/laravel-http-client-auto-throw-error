import React from 'react'
import { ThemeProvider } from 'styled-components/native'
import { theme } from '../utils/theme'

global.app = function (children: React.ReactNode) {
  return <ThemeProvider theme={theme}>{children}</ThemeProvider>
}
