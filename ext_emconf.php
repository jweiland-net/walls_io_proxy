<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Walls.io Proxy',
    'description' => 'Cache and Proxy for walls.io, so no Cookie will be set on Client',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '6.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.38-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
