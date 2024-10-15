/** @type{import("@storybook/react-webpack5").StorybookConfig} */
module.exports = {
  stories: { directory: "../src", files: "**/*.stories.tsx" },
  addons: [
    "@storybook/addon-links",
    "@storybook/addon-essentials",
    "@chromatic-com/storybook",
  ],
  docs: {},
  typescript: {
    reactDocgen: "react-docgen-typescript",
  },
};
