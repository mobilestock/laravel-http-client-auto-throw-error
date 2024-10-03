import { ThemeProvider } from 'styled-components'

import { withThemeFromJSXProvider } from '@storybook/addon-themes'
import type { Preview } from '@storybook/react'

import { theme } from '../src/packages/base/theme'

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i
      }
    },
    backgrounds: {
      options: {
        dark: { name: 'Dark', value: '#333' },
        light: { name: 'Light', value: '#F7F9F2' }
      }
    }
  },
  initialGlobals: {
    backgrounds: { value: 'dark' }
  }
}

export const decorators = [
  withThemeFromJSXProvider({
    themes: {
      light: theme
    },
    Provider: ThemeProvider
  })
]

export default preview
