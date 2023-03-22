<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Walls.io Proxy',
    'description' => 'Cache and Proxy for walls.io, so no Cookie will be set on Client',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '5.2.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.29-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
