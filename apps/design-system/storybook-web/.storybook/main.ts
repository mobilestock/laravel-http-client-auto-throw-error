import type { StorybookConfig } from '@storybook/react-vite';
import { dirname, join } from "path";
import { UserConfig } from 'vite';

/**
 * This function is used to resolve the absolute path of a package.
 * It is needed in projects that use Yarn PnP or are set up within a monorepo.
 */
function getAbsolutePath(value: string): string {
  return dirname(require.resolve(join(value, 'package.json')));
}

const config: StorybookConfig = {
  stories: [
    // TODO: Add your stories here
    "../src/**/*.mdx",
    "../src/**/**/**/**/*.mdx",
    "../src/**/**/**/**/*.stories.tsx"
  ],
  addons: [
    getAbsolutePath('@storybook/addon-onboarding'),
    getAbsolutePath('@storybook/addon-links'),
    getAbsolutePath('@storybook/addon-essentials'),
    getAbsolutePath('@storybook/addon-themes')
  ],
  framework: {
    name: getAbsolutePath('@storybook/react-vite'),
    options: {}
  },
  async viteFinal(config: UserConfig) {
    config.resolve = {
      ...config.resolve,
      alias: {
        ...config.resolve?.alias,
        react: join(__dirname, "../node_modules/react"),
      },
    };
    return config;
  },
};

export default config;
