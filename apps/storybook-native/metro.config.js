const path = require("path");
const { getDefaultConfig } = require("expo/metro-config");

const defaultConfig = getDefaultConfig(__dirname);

const withStorybook = require("@storybook/react-native/metro/withStorybook");

defaultConfig.projectRoot = __dirname;
defaultConfig.watchFolders = [path.resolve(__dirname, "../../node_modules")];

defaultConfig.resolver.nodeModulesPaths = [
  path.resolve(__dirname, "node_modules"),
  path.resolve(path.resolve(__dirname, "../.."), "node_modules"),
];

module.exports = withStorybook(defaultConfig, {
  enabled: true,
  configPath: path.resolve(__dirname, "./.ondevice"),
});
