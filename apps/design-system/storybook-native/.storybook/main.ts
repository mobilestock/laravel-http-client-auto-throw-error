import path, { dirname, join } from "path";
import { Configuration } from "webpack";

/**
 * @format
 * @type{import("@storybook/react-webpack5").StorybookConfig}
 */

/**
 * This function is used to resolve the absolute path of a package.
 * It is needed in projects that use Yarn PnP or are set up within a monorepo.
 */
function getAbsolutePath(value: string) {
  return dirname(require.resolve(join(value, "package.json")));
}

module.exports = {
  stories: ["../components/**/*.stories.@(js|jsx|ts|tsx)"],
  addons: [
    getAbsolutePath("@storybook/addon-links"),
    getAbsolutePath("@storybook/addon-essentials"),
    getAbsolutePath("@storybook/addon-interactions"),
    getAbsolutePath("@storybook/addon-react-native-web"),
    getAbsolutePath("@storybook/addon-themes"),
  ],
  framework: {
    name: getAbsolutePath("@storybook/react-webpack5"),
    options: {},
  },
  docs: {
    autodocs: true,
    },
  webpackFinal: async (config: Configuration) => {
    config.resolve = {
          ...config.resolve,
          alias: {
          ...config.resolve?.alias,
          'react': path.resolve(__dirname, '../node_modules/react'),
          'react-dom': path.resolve(__dirname, '../node_modules/react-dom')
        },
      };
      return config;
  },
};
