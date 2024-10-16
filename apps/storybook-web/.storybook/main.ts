import type { StorybookConfig } from '@storybook/nextjs';

const config: StorybookConfig = {
  stories: ['../src/**/*.mdx', '../src/**/*.stories.tsx'],
  addons: [
    '@storybook/addon-designs',
    '@storybook/addon-links',
    '@storybook/addon-essentials',
    '@storybook/addon-themes'
  ],
  framework: {
    name: "@storybook/nextjs",
    options: {}
  },
  staticDirs: ['../public'],
  webpackFinal: async (config) => {
    config.optimization = {
      ...config.optimization,
      splitChunks: {
        chunks: 'all',
      },
      minimize: true,
    };
    config.performance = {
      ...config.performance,
      maxEntrypointSize: 51200000,
      maxAssetSize: 51200000,
    }
    return config
  },
}

export default config
