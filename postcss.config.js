module.exports = {
    plugins: [
        require('postcss-import'),
        require('postcss-clean')({ level: 2 }) // Minifies CSS
    ]
};
  