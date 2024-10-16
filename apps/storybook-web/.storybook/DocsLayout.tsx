import React from 'react'
import { ThemeProvider } from 'styled-components'

import { DocsContainer, DocsContainerProps } from '@storybook/addon-docs'

import '../public/fonts/fonts.css'
import { theme } from '../src/packages/base/utils/theme'
import SearchComponent from './SearchComponent'

interface DocsLayoutProps extends DocsContainerProps {
  children: React.ReactNode
}

const docTheme = {
  base: 'dark' as 'dark',
  colorPrimary: theme.colors.container.pure,
  colorSecondary: theme.colors.button.anchor,
  appBg: theme.colors.container.default,
  appContentBg: theme.colors.container.default,
  appPreviewBg: theme.colors.container.default,
  appBorderColor: theme.colors.container.outline.default,
  appBorderRadius: 5,
  textColor: theme.colors.text.default,
  textInverseColor: theme.colors.alert.tip,
  barTextColor: theme.colors.text.default,
  barSelectedColor: theme.colors.text.default,
  barBg: theme.colors.container.default,
  inputBg: theme.colors.container.default,
  inputBorder: theme.colors.container.outline.default,
  inputTextColor: theme.colors.alert.tip,
  inputBorderRadius: 15,
  fontBase: '"Arial", sans-serif',
  fontCode: '"JetBrains Mono", monospace',
  textMutedColor: theme.colors.alert.tip,
  barHoverColor: theme.colors.container.purpleShadow,
  buttonBg: theme.colors.container.default,
  buttonBorder: theme.colors.container.outline.default,
  buttonTextColor: theme.colors.text.default,
  buttonBorderRadius: 5,
  booleanBg: theme.colors.container.default,
  booleanSelectedBg: theme.colors.container.outline.default
}

const DocsLayout: React.FC<DocsLayoutProps> = ({ children, context }) => {
  return (
    <ThemeProvider theme={theme}>
      <SearchComponent />
      <DocsContainer context={context} theme={docTheme}>
        {children}
      </DocsContainer>
    </ThemeProvider>
  )
}

export default DocsLayout
