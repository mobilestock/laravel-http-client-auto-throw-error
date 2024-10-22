import path from 'path'
import { Configuration } from 'webpack'

const config = {
  stories: { directory: '../src', files: '**/*.stories.tsx' },
  addons: [
    '@storybook/addon-essentials',
    '@storybook/addon-interactions',
    '@storybook/addon-react-native-web',
    '@storybook/addon-themes',
    '@storybook/addon-ondevice-notes/register'
  ],
  framework: {
    name: '@storybook/react-webpack5',
    options: {}
  },
  docs: {
    autodocs: true
  },
  webpackFinal: async (config: Configuration) => {
    config.resolve = {
      ...config.resolve,
      alias: {
        ...config.resolve?.alias,
        react: path.resolve(__dirname, '../node_modules/react'),
        'react-dom': path.resolve(__dirname, '../node_modules/react-dom')
      }
    }
    return config
  }
}

export default config
