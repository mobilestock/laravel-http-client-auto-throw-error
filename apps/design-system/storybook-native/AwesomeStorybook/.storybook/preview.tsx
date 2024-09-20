/** @format */

import type { Preview } from '@storybook/react'

import { withThemeFromJSXProvider } from '@storybook/addon-themes'
import { createGlobalStyle, ThemeProvider } from 'styled-components'

import { globalTema } from '../theme'

const GlobalStyles = createGlobalStyle`
  body {
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  }
  `

const preview: Preview = {
  parameters: {
    actions: { argTypesRegex: '^on[A-Z].*' },
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/,
      },
    },
  },
}

export const decorators = [
  withThemeFromJSXProvider({
    themes: {
      light: globalTema,
    },
    Provider: ThemeProvider,
  }),
]

export default preview
