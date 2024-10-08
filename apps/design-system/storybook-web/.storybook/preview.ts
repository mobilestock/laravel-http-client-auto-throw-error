import { ThemeProvider } from 'styled-components'

import { withThemeFromJSXProvider } from '@storybook/addon-themes'
import type { Preview } from '@storybook/react'

import '../public/fonts/fonts.css'
import { theme } from '../src/packages/base/utils/theme'

const preview: Preview = {
  parameters: {
    options: {
      storySort: {
        includeNames: true,
        order: [
          'Introdução',
          ['Micro-serviços', 'Documentação de referência da API', 'Empacotamentos'],
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
          ],
          'Users',
          'Lookpay-API'
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
      theme: {
        base: 'dark',
        colorPrimary: theme.colors.background.light,
        colorSecondary: theme.colors.button.base,
        appBg: theme.colors.background.dark,
        appContentBg: theme.colors.background.dark,
        appPreviewBg: theme.colors.background.dark,
        appBorderColor: theme.colors.decorator.outline,
        appBorderRadius: 5,
        textColor: theme.colors.text.secondary,
        textInverseColor: theme.colors.text.primary,
        barTextColor: theme.colors.text.secondary,
        barSelectedColor: theme.colors.text.secondary,
        barBg: theme.colors.background.dark,
        inputBg: theme.colors.background.dark,
        inputBorder: theme.colors.decorator.outline,
        inputTextColor: theme.colors.alert.tip,
        inputBorderRadius: 15,
        fontBase: '"Arial", sans-serif',
        fontCode: '"JetBrains Mono", monospace'
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
