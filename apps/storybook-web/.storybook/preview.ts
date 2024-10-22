import { ThemeProvider } from 'styled-components'

import { withThemeFromJSXProvider } from '@storybook/addon-themes'
import type { Preview } from '@storybook/react'

import '../public/fonts/fonts.css'
import { theme } from '../src/packages/base/utils/theme'
import DocsLayout from './DocsLayout'

const preview: Preview = {
  parameters: {
    options: {
      storySort: {
        includeNames: true,
        order: [
          'Introdução',
          ['Empacotamentos'],
          'Design',
          'Layouts',
          'Componentes',
          [
            'Como criar um componente',
            [
              'Regras e Objetivos',
              'Como criar um componente extensível',
              'Reutilização de código',
              ['Estados', 'Estilos CSS', 'Funções', 'Tipagens', 'Wrappers']
            ],
            'Button',
            'Avatar',
            'LoadingSpinner',
            'Input',
            'FormInput',
            'SelectCity'
          ]
        ]
      }
    },
    controls: {
      disableSaveFromUI: true,
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i
      }
    },
    docs: {
      container: DocsLayout,
      toc: {
        headingSelector: 'h1, h2'
      }
    }
  }
}

export const decorators = [
  withThemeFromJSXProvider({
    themes: {
      dark: theme
    },
    defaultTheme: 'dark',
    Provider: ThemeProvider
  })
]

export default preview
