/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = merge(common, {
    mode: 'production',
    optimization : {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                parallel: true,
                extractComments: false,
            }),
        ],
    },
});
