<?php

return [
    'base_domain' => env('SHOP_BASE_DOMAIN', 'shop.bazardev.com'),
    'reserved' => [
        'www', 'app', 'admin', 'api', 'mail', 'shop', 'store', 'stores', 'blog', 'support', 'help', 'dashboard',
        'login', 'register', 'static', 'cdn', 'test', 'demo', 'bazardev', 'root', 'ftp', 'smtp', 'internal',
        'staging', 'dev', 'billing', 'account', 'accounts', 'status', 'seller', 'sellers', 'checkout', 'cart',
    ],
];
