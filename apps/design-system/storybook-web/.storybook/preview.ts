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
              ['Estados', 'Estilos CSS', 'Funções', 'Tipagens', 'Wrappers'],
            ],
            'Button',
            'Avatar',
            'LoadingSpinner',
            'Input',
            'FormInput',
            'SelectCity',
          ],
        ],
      },
    },
    controls: {
      disableSaveFromUI: true,
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
    docs: {
      theme: {
        base: 'dark',
        colorPrimary: theme.colors.container.pure,
        colorSecondary: theme.colors.button.anchor,
        appBg: theme.colors.container.default,
        appContentBg: theme.colors.container.default,
        appPreviewBg: theme.colors.container.default,
        appBorderColor: theme.colors.container.outline.default,
        appBorderRadius: 5,
        textColor: theme.colors.text.default,
        textInverseColor: theme.colors.text.regular,
        barTextColor: theme.colors.text.default,
        barSelectedColor: theme.colors.text.default,
        barBg: theme.colors.container.default,
        inputBg: theme.colors.container.default,
        inputBorder: theme.colors.container.outline.default,
        inputTextColor: theme.colors.alert.tip,
        inputBorderRadius: 15,
        fontBase: '"Arial", sans-serif',
        fontCode: '"JetBrains Mono", monospace',
      },
    },
    backgrounds: {
      options: {
        dark: { name: 'Dark', value: theme.colors.container.default },
        light: { name: 'Light', value: theme.colors.container.pure },
      },
    },
  },
  initialGlobals: {
    backgrounds: { value: 'light' },
  },
}

export const decorators = [
  withThemeFromJSXProvider({
    themes: {
      dark: theme,
    },
    defaultTheme: 'dark',
    Provider: ThemeProvider,
  }),
]

export default preview
