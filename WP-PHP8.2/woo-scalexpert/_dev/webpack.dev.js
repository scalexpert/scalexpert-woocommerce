/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    mode: 'development',
    devtool: 'source-map',
});
