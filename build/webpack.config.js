import path from 'path';
import { existsSync } from 'fs';
import { createRequire } from 'module';
import glob from 'fast-glob';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import CopyWebpackPlugin from 'copy-webpack-plugin';
import RemoveEmptyScriptsPlugin from 'webpack-remove-empty-scripts';
import { WebpackManifestPlugin } from 'webpack-manifest-plugin';
import BrowserSyncPlugin from 'browser-sync-webpack-plugin';

export default (env, argv) => {

    // ----------------------------------------------
    // BASIC CONFIGURATION
    // ----------------------------------------------
    process.env.NODE_ENV = argv.mode;

    const isProduction = argv.mode === 'production';

    // Set context to theme root (parent of build folder).
    const themeRoot = path.resolve();
    const srcDir = path.join(themeRoot, '_src');

    // Load .env.js if it exists
    // Note: .env.js must use CommonJS syntax (module.exports) for webpack config compatibility
    let envConfig = {};
    const envPath = path.join(themeRoot, '.env.js');

    if (existsSync(envPath)) {
        try {
            const require = createRequire(import.meta.url);
            const loaded = require(envPath);
            envConfig = loaded.default || loaded;
        } catch (error) {
            // If it's an ES module error, we can't load it synchronously
            const isESModuleError = error.code === 'ERR_REQUIRE_ESM' ||
                                   error.message?.includes('ES Module') ||
                                   error.message?.includes('not supported');

            if (isESModuleError) {
                // .env.js is ES module - can't load synchronously in webpack config
                // Silently skip (will use default 'localhost' proxy)
            } else if (error.code !== 'MODULE_NOT_FOUND') {
                console.warn('Warning: Could not load .env.js:', error.message);
            }
        }
    }

    // Define global path.
    const PATHS = {
        root: themeRoot,
        src: srcDir,
        assets: path.join(themeRoot, 'assets'),
        images: path.join(srcDir, 'images'),
        fonts: path.join(srcDir, 'fonts'),
        static: path.join(srcDir, 'static'),
        build: path.join(themeRoot, 'build'),
    };

    // Define global bundles configuration.
    const BUNDLES = [
        'main',
        'editor',
        'admin',
    ];

    // ----------------------------------------------
    // HELPERS
    // ----------------------------------------------

    // normalize component paths (components-wholegrain -> components).
    // Normalise backslashes to forward slashes first so this works on Windows
    // (path.relative() returns backslashes there, which broke the merge into assets/components/).
    const normalizeComponentPath = (filePath) => filePath.replace(/\\/g, '/').replace('components-wholegrain/', 'components/');

    // build webpack entries from glob pattern.
    const buildEntries = (globPattern, filterFn = null) => {
        const files = glob.sync(globPattern, { cwd: themeRoot });
        const entries = {};

        files.forEach((file) => {
            // Apply optional filter.
            if (filterFn && !filterFn(file)) {
                return;
            }

            const parsed = path.parse(file);
            let entryPath = path.join(parsed.dir, parsed.name);

            // Normalise to remove alternative component directories.
            if (entryPath.includes('_src/components-')) {
                entryPath = normalizeComponentPath(entryPath);
            }

            // Strip '_src'.
            entryPath = path.relative(PATHS.src, entryPath);

            entries[entryPath] = `./${file}`;
        });

        return entries;
    };

    // create copy pattern for component directories.
    const createComponentCopyPattern = (dir) => ({
        from: `_src/${dir}/**/*`,
        to: ({ context, absoluteFilename }) => {
            return normalizeComponentPath(path.relative(PATHS.src, absoluteFilename));
        },
        filter: (resourcePath) => {
            // Exclude compiled JS and SCSS files.
            return !resourcePath.match(/\.(js|scss|sass)$/);
        },
    });

    // ----------------------------------------------
    // COLLECT ENTRY POINTS
    // ----------------------------------------------

    // Build main entry points from global bundles
    const mainEntries = {};
    BUNDLES.forEach((name) => {
        mainEntries[`${name}`] = `./_src/${name}.js`;
        mainEntries[`${name}-styles`] = `./_src/${name}.scss`;
    });

    // Dynamically find all component JS.
    const componentJsEntries = buildEntries('_src/components*/**/scripts/*.js');

    // Dynamically find component SCSS.
    const componentScssEntries = buildEntries(
        '_src/components*/**/styles/*.scss',
        (file) => {
            const base = path.basename(file); // safer than checking full path.
            return !BUNDLES.some(bundle => base === `${bundle}.scss`);
        }
    );

    // Gather them all together.
    const entries = {
        ...mainEntries,
        ...componentJsEntries,
        ...componentScssEntries,
    };

    // ----------------------------------------------
    // BUILD!
    // ----------------------------------------------
    return {
        context: themeRoot,
        entry: entries,
        optimization: {
            minimize: isProduction,
        },
        watchOptions: {
            ignored: /node_modules/,
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: (pathData) => {
                    const { name } = pathData.chunk;

                    // Map JS entry names to CSS output paths using BUNDLES config.
                    const bundle = BUNDLES.find(bundle => name === `${bundle}-styles`);
                    if (bundle) {
                        return `${bundle}.[contenthash].css`;
                    }

                    // Component styles: components/button/styles/main -> components/button/styles/main.[hash].css.
                    return `${name}.[contenthash].css`;
                },
            }),

            // Copy all component files (PHP, JSON, etc.) from both component directories.
            new CopyWebpackPlugin({
                patterns: [
                    // Normalize components-wholegrain -> components in output.
                    ...['components', 'components-wholegrain'].map(createComponentCopyPattern),

                    // Copy images (just copy, no optimization).
                    {
                        from: '**/*',
                        to: 'images/[path][name][ext]',
                        context: PATHS.images,
                    },

                    // Copy static assets (fonts, etc).
                    {
                        from: '**/*',
                        to: 'static/[path][name][ext]',
                        context: PATHS.static,
                    },
                ],
            }),

            new RemoveEmptyScriptsPlugin(),

            // Generate manifest file. Enables enqueuing built assets with hashed filenames (for caching).
            new WebpackManifestPlugin({
                map: (fileDescriptor) => {
                    // Clean up core CSS bundle names in manifest.
                    if (fileDescriptor.name.includes('-styles.css')) {
                        fileDescriptor.name = fileDescriptor.name.replace('-styles.css', '.css');
                    }

                    return fileDescriptor;
                }
            }),

            // BrowserSync for live reloading (development only)
            ...(isProduction ? [] : [
                new BrowserSyncPlugin(
                    {
                        host: 'localhost',
                        port: 3000,
                        proxy: envConfig.BROWSERSYNC_PROXY || 'localhost',
                        files: [
                            '**/*.php',
                            'assets/**/*.css',
                            'assets/**/*.js',
                            '_src/**/*.scss', // Watch source SCSS files
                        ],
                        watchOptions: {
                            ignoreInitial: true,
                        },
                        reloadDelay: 1000,
                        notify: false,
                        open: false,
                        // Inject CSS changes without full page reload
                        injectChanges: true,
                    },
                    {
                        // Enable reload when webpack finishes compilation
                        reload: true,
                    }
                )
            ])
        ],
        module: {
            rules: [
                // SCSS/SASS Loader - Extract to CSS.
                {
                    test: /\.(scss|sass)$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                importLoaders: 2,
                                sourceMap: !isProduction,
                                url: false, // Don't process URLs in CSS (images already copied).
                            },
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                sourceMap: !isProduction,
                                postcssOptions: {
                                    config: path.resolve(PATHS.build, 'postcss.config.js'),
                                },
                            },
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: !isProduction,
                                sassOptions: {
                                    includePaths: [PATHS.src, 'node_modules/'],
                                    silenceDeprecations: ['import', 'global-builtin', 'color-functions'], // Ignore deprecation about @import.
                                },
                                additionalData: (content, loaderContext) => {
                                    const resourcePath = loaderContext.resourcePath.replace(/\\/g, '/');

                                    // Prepend core imports for component files.
                                    if (resourcePath.includes('/components')) {
                                        // Normalise to forward slashes so the @import resolves on Windows
                                        // (Sass treats backslashes as escape characters).
                                        const corePath = PATHS.src.replace(/\\/g, '/');
                                        return `@import '${corePath}/core.scss';\n${content}`;
                                    }

                                    // Lookup table: which glob imports apply to which file.
                                    const scssExpansions = {
                                        'main.scss': [
                                            { search: `@import './components*/**/styles/main.scss';`, pattern: 'components*/**/styles/main.scss' }
                                        ],
                                        'editor.scss': [
                                            { search: `@import './components*/**/styles/main.scss';`, pattern: 'components*/**/styles/main.scss' },
                                            { search: `@import './components*/**/styles/editor.scss';`, pattern: 'components*/**/styles/editor.scss' },
                                            { search: `@import './components*/**/styles/block.scss';`, pattern: 'components*/**/styles/block.scss' }
                                        ],
                                        'admin.scss': [
                                            { search: `@import './components*/**/styles/admin.scss';`, pattern: 'components*/**/styles/admin.scss' }
                                        ]
                                    };

                                    const fileName = path.basename(resourcePath);

                                    if (!scssExpansions[fileName]) {
                                        return content;
                                    }

                                    // Expand glob imports deterministically.
                                    let output = content;
                                    scssExpansions[fileName].forEach(({ search, pattern }) => {
                                        const files = glob.sync(pattern, { cwd: srcDir });
                                        const imports = files.map(file => `@import './${file}';`).join('\n');

                                        output = output.replace(search, imports);
                                    });

                                    return output;
                                },
                            },
                        },
                    ],
                },
                // JavaScript Loader
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: [
                        // Support glob imports (like './components*/**/scripts/main.js').
                        'glob-import-loader',
                    ],
                },
            ],
        },
        output: {
            filename: (pathData) => {
                const { name } = pathData.chunk;
                // All JS files without content hashing.
                return `${name}.[contenthash].js`;
            },
            path: PATHS.assets,
            clean: true, // Clean assets folder before each build.
        },
        externals: {
            jquery: 'jQuery',
        },
        devtool: isProduction ? false : 'source-map',
        stats: {
            preset: 'errors-warnings',
            timings: true, // Show milliseconds.
        },
    };
}
