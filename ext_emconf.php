<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Walls.io Proxy',
    'description' => 'Cache and Proxy for walls.io, so no Cookie will be set on Client',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '4.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.14-9.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
