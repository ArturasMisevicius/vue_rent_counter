<?php

return [

    'single' => [

        'label' => 'Atkurti',

        'modal' => [

            'heading' => 'Atkurti :label',

            'actions' => [

                'restore' => [
                    'label' => 'Atkurti',
                ],

            ],

        ],

        'notifications' => [

            'restored' => [
                'title' => 'Atkurta',
            ],

        ],

    ],

    'multiple' => [

        'label' => 'Atkurti pasirinktus',

        'modal' => [

            'heading' => 'Atkurti pasirinktus :label',

            'actions' => [

                'restore' => [
                    'label' => 'Atkurti',
                ],

            ],

        ],

        'notifications' => [

            'restored' => [
                'title' => 'Atkurta',
            ],

            'restored_partial' => [
                'title' => 'Atkurta :count iš :total',
                'missing_authorization_failure_message' => 'Neturite leidimo atkurti :count.',
                'missing_processing_failure_message' => ':count nepavyko atkurti.',
            ],

            'restored_none' => [
                'title' => 'Nepavyko atkurti',
                'missing_authorization_failure_message' => 'Neturite leidimo atkurti :count.',
                'missing_processing_failure_message' => ':count nepavyko atkurti.',
            ],

        ],

    ],

];
