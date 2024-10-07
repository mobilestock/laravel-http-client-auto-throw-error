import { withBackgrounds } from "@storybook/addon-ondevice-backgrounds";
import { withThemeFromJSXProvider } from '@storybook/addon-themes';
import type { Preview } from "@storybook/react";
import { ThemeProvider } from 'styled-components';
import { theme } from '../src/packages/base/utils/theme';

const preview: Preview = {
  decorators: [withBackgrounds, withThemeFromJSXProvider({
    themes: {
      light: theme
    },
    Provider: ThemeProvider
  })],

  parameters: {
    backgrounds: {
      default: "plain",
      values: [
        { name: "plain", value: "white" },
        { name: "warm", value: "hotpink" },
        { name: "cool", value: "deepskyblue" },
      ],
    },
    actions: { argTypesRegex: "^on[A-Z].*" },
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/,
      },
    },
  },
};

export default preview;
