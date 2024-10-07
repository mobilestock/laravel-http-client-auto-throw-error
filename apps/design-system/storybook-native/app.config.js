export default ({ config }) => ({
  ...config,
  name: "storybook-native",
  slug: "storybook-native",
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
  android: {
    adaptiveIcon: {
      backgroundColor: "#FFFFFF",
    },
    package: "br.com.mobile.storybook.native"
  },
  scheme: "app-storybook-native",
  plugin: ["expo-router"],
});
