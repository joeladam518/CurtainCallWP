const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

/**
 * Override webpack.config.js, without editing the file directly.
 **/
mix.webpackConfig({
    externals: {
        "jquery": "jQuery"
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '>': path.resolve(__dirname, 'resources/sass'),
        },
    },
});

/**
 *  Will be passed to Webpack's ProvidePlugin.
**/
// mix.autoload({
//     jquery: [ 'jQuery', 'window.jQuery' ], // ['$', 'jQuery', 'window.jQuery'],
// });

/**
 *  mix.options({
 *    extractVueStyles: false, // Extract .vue component styling to file, rather than inline.
 *    globalVueStyles: file, // Variables file to be imported in every component.
 *    processCssUrls: true, // Process/optimize relative stylesheet url()'s. Set to false, if you don't want them touched.
 *    purifyCss: false, // Remove unused CSS selectors.
 *    uglify: {}, // Uglify-specific options. https://webpack.github.io/docs/list-of-plugins.html#uglifyjsplugin
 *    postCss: [] // Post-CSS options: https://github.com/postcss/postcss/blob/master/docs/plugins.md
 *  });
**/
mix.options({
    processCssUrls: false
});

mix.js('resources/js/admin/plugin.js', 'dist/admin/js/curtain-call-wp-admin.js');
mix.sass('resources/sass/admin/plugin.scss', 'dist/admin/css/curtain-call-wp-admin.css');

mix.js('resources/js/frontend/plugin.js', 'dist/frontend/js/curtain-call-wp-frontend.js');
mix.sass('resources/sass/frontend/plugin.scss', 'dist/frontend/css/curtain-call-wp-frontend.css');

mix.setPublicPath('dist');