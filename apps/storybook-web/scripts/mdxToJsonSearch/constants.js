module.exports = {
  META_OF_COMPONENT_REGEX : /<Meta[^>]*of=\{([^}]+)\}[^>]*\/>/,
  SATISFIES_META_REGEX : /satisfies\s+Meta<[^>]+>/g,
  AS_META_REGEX : /as\s+Meta[^\n]*/g,
  IS_TITLE_OR_SUBTITLE_REGEX : /^#{1,6}\s+(.*)$/gm,
  FILEPATH_STRUCTURE_REGEX : /^packages\/base\/components\//,
  EMPTY_SPACE_REGEX : /\s+/g,
  REVERSE_SLASH_REGEX : /\\/g,
  MDX_EXTENSION_REGEX : /\.mdx$/,
  META_TITLE_REGEX : /<Meta\s+title=['"`](.*?)['"`]\s*\/>/,
  BLOCK_COMMENTS_REGEX : /\/\*.*?\*\//gs,
  BRACES_REGEX : /[{}]/g,
  NON_ALPHANUMERIC_REGEX : /[^\p{L}\p{N}#\s-]/gu
}
