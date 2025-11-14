export default () => {
    plugins: [
        require('autoprefixer'),
        require('cssnano')({
            preset: [
                'default',
                {
                    safe: true,
                    zindex: false,
                    discardComments: {
                        removeAll: true,
                    },
                    autoprefixer: false,
                    calc: false,
                    mergeIdents: false,
                    reduceIdents: false,
                },
            ],
        }),
    ]
}
