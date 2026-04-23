<?php
return [
    'directories'=> [
        'source' => [
            'portman/lib/Livewire' => [
                'composer' => [
                    'name'   => 'livewire/livewire',
                    'version'   => '~3.7.11',
                    'version-lock'   => '3.7.11',
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
                    '!Features/Support{Attributes,Events,LifecycleHooks,Locales,NestingComponents,Redirects,FormObjects,Validation,LockedProperties,Streaming}/**/*',
                    'Features/SupportEvents/TestsEvents.php',
                    'Features/SupportRedirects/TestsRedirects.php',
                    'Mechanisms/CompileLivewireTags/**/*',
                    'Mechanisms/ExtendBlade/**/*',
                    'Mechanisms/HandleComponents/Synthesizers/{Carbon,Collection,Stringable}*',
                    'Mechanisms/HandleComponents/{BaseRenderless,CorruptComponent,ViewContext}*',
                    'Mechanisms/RenderComponent.php',
                    'Component.php',
                    'LivewireServiceProvider.php',
                    'Features/SupportRedirects/Redirector.php'
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
                    'rename' => 'MagewireManager',
                    'remove-methods' => [
                        'setProvider',
                        'provide',
                        'component',
                        'componentHook',
                        'propertySynthesizer',
                        'directive',
                        'precompiler',
                        'new',
                        'isDiscoverable',
                        'resolveMissingComponent',
                        'snapshot',
                        'fromSnapshot',
                        'listen',
                        'current',
                        'findSynth',
                        'updateProperty',
                        'isLivewireRequest',
                        'componentHasBeenRendered',
                        'forceAssetInjection',
                        'setUpdateRoute',
                        'getUpdateUri',
                        'setScriptRoute',
                        'useScriptTagAttributes',
                        'withUrlParams',
                        'withQueryParams',
                        'withCookie',
                        'withCookies',
                        'withHeaders',
                        'withoutLazyLoading',
                        'test',
                        'visit',
                        'actingAs',
                        'isRunningServerless',
                        'addPersistentMiddleware',
                        'setPersistentMiddleware',
                        'getPersistentMiddleware',
                        'originalUrl',
                        'originalPath',
                        'originalMethod'
                    ]
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
        'rector' => false,
        'php-cs-fixer' => false
    ]
];
