<?php

return [
    // Fase 1 utiliza renderizado Vue en el navegador, sin servidor SSR.
    'ssr' => ['enabled' => false],
    'pages' => [
        'ensure_pages_exist' => true,
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['vue'],
    ],
    'devtools' => ['enabled' => false],
];
