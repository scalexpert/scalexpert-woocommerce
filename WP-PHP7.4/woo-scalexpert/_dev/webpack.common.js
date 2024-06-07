/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssoWebpackPlugin = require('csso-webpack-plugin').default;

module.exports = {
    entry: {
        scalexpert: ['./js/front.js', './scss/front.scss'],
        admin: ['./scss/admin.scss'],
        // admin: ['./js/admin.js', './scss/admin.scss'],
    },
    output: {
        path: path.resolve(__dirname, '../assets/js'),
        filename: '[name].js',
    },
    resolve: {
        preferRelative: true,
    },

    module: {
        rules: [
            {
                test: /\.js/,
                loader: 'esbuild-loader',
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader',
                    'sass-loader',
                ],
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
                generator: {
                    filename: '../img/[hash][ext]',
                },
            },
            {
                test: /\.css$/i,
                use: [MiniCssExtractPlugin.loader, 'style-loader', 'css-loader', 'postcss-loader'],
            },
        ],
    },
    externals: {
        $: '$',
        jquery: 'jQuery',
    },
    plugins: [
        new MiniCssExtractPlugin({filename: path.join('..', 'css', '[name].css')}),
        new CssoWebpackPlugin({
            forceMediaMerge: true,
        }),
    ]
};
