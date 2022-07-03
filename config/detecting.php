<?php
return [
    'P0039' => [
        'B051' => [
            'func' => 'ALX',
            'stage' => [
                '检前' => 'FIX_BEFORE',
                '检后' => 'FIX_AFTER',
                '验收' => 'CHECKED',
            ],
            'type' => [
                '继电器检修测试台' => 'ENTIRE'
            ]
        ],
        'B049'=>[
            'func' => 'ALX',
            'stage' => [
                '检前' => 'FIX_BEFORE',
                '检后' => 'FIX_AFTER',
                '验收' => 'CHECKED',
                '检测' => 'FIX_AFTER',
                '检修' => 'FIX_AFTER',
                '返修' => 'FIX_AFTER',
                '工程验收' => 'CHECKED',
            ],
            'type' => [
                '继电器检修测试台' => 'ENTIRE'
            ]
        ]
    ]
];
