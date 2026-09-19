<?php

return [
    'cache.location' => storage_path('framework/cache'),
    'cache.life' => 3600,
    'cache.disabled' => true,
    'ssl_check.disabled' => false,
    'strip_html_tags.disabled' => false,
    'strip_html_tags.tags' => [
        'base', 'blink', 'body', 'doctype', 'embed', 'font', 'form', 'frame', 'frameset', 'html', 'iframe', 'input',
        'marquee', 'meta', 'noscript', 'object', 'param', 'script', 'style',
    ],
    'strip_attribute.disabled' => false,
    'strip_attribute.tags' => [
        'bgsound', 'class', 'expr', 'id', 'style', 'onclick', 'onerror', 'onfinish', 'onmouseover', 'onmouseout',
        'onfocus', 'onblur', 'lowsrc', 'dynsrc',
    ],
    'curl.options' => null,
    'curl.timeout' => 20,
    'user_agent' => null,
];
