<?php
return [
    'directories'=> [
        'source' => [
            'lib/Livewire' => [
                'composer' => [
                    'name'   => 'livewire/livewire',
                    'version'   => '~3.6.4',
                    'version-lock'   => '3.6.4',
                    'base-path'   => 'src'
                ],
                'glob' => '**/*.php',
                'ignore' => [
                    '{Wi,Attr,hel,Imp,Form}*',
                    'Livewire.php',
                    'Attributes/**/*',
                    '!Attributes/{Locked,On}.php',
                    'Drawer/{ImplicitRouteBinding,Regexes}*',
                    'Exceptions/{Event,Livewire,Root}*',
                    'Features/**/*',
                    '!Features/Support{Attributes,Events,LifecycleHooks,Locales,NestingComponents,Redirects,FormObjects,Validation,LockedProperties}/**/*',
                    'Features/SupportEvents/TestsEvents.php',
                    'Features/SupportRedirects/TestsRedirects.php',
                    'Mechanisms/CompileLivewireTags/**/*',
                    'Mechanisms/ExtendBlade/**/*',
                    'Mechanisms/HandleComponents/Synthesizers/{Carbon,Collection,Stringable}*',
                    'Mechanisms/HandleComponents/{BaseRenderless,CorruptComponent,ViewContext}*',
                    'Mechanisms/RenderComponent.php',
                    'Component.php',
                    'LivewireServiceProvider.php'
                ]
            ]
        ],
        'augmentation' => [
            'portman/Livewire'
        ],
        'output' => 'dist'
    ],
    'transformations' => [
        'Livewire\\' => [
            'rename' => 'Magewirephp\\Magewire\\',
            'children' => [
                'LivewireManager' => [
                    'rename' => 'MagewireManager'
                ],
                'LivewireServiceProvider' => [
                    'rename' => 'MagewireServiceProvider'
                ],
                'Features\\SupportRedirects\\HandlesRedirects' => [
                    'remove-methods' => [
                        'redirectAction',
                        'redirectRoute',
                        'redirectIntended'
                    ]
                ]
            ]
        ],
        'Magewirephp\\Magewire\\' => [
            'file-doc-block' => '/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */',
        ]
    ],
    'post-processors' => [
        'rector' => true,
        'php-cs-fixer' => true
    ]
];
