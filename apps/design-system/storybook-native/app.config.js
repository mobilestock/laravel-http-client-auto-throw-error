export default ({ config }) => ({
  ...config,
  name: "storybook-native",
  slug: "expo-template-blank-typescript",
  version: "1.0.0",
  orientation: "portrait",
  extra: {
    storybookEnabled: process.env.STORYBOOK_ENABLED,
  },
  splash: {
    resizeMode: "contain",
    backgroundColor: "#ffffff",
  },
  updates: {
    fallbackToCacheTimeout: 0,
  },
  assetBundlePatterns: ["**/*"],
  ios: {
    supportsTablet: true,
  },
  android: {
    adaptiveIcon: {
      backgroundColor: "#FFFFFF",
    },
  },
  scheme: "app-storybook-native",
  plugin: ["expo-router"],
});
