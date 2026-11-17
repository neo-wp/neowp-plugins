const path = require('path');

module.exports = {
    entry: './index.js',
    output: {
        path: path.resolve(__dirname, '..', 'neowp'),
        filename: 'neo-draw--editor-xml-formatter.min.js'
    },
    mode: 'production'
};
