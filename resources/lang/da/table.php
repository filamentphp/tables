<?php

return [

    'columns' => [

        'text' => [
            'more_list_items' => 'og :count flere',
        ],

    ],

    'fields' => [

        'search' => [
            'label' => 'Søg',
            'placeholder' => 'Søg',
        ],

    ],

    'pagination' => [

        'label' => 'Paginering Navigation',

        'overview' => 'Viser :first til :last af :total resultater',

        'fields' => [

            'records_per_page' => [

                'label' => 'per side',

                'options' => [
                    'all' => 'Alle',
                ],

            ],

        ],

        'buttons' => [

            'go_to_page' => [
                'label' => 'Gå til side :page',
            ],

            'next' => [
                'label' => 'Næste',
            ],

            'previous' => [
                'label' => 'Forrige',
            ],

        ],

    ],

    'buttons' => [

        'filter' => [
            'label' => 'Filtrer',
        ],

        'open_bulk_actions' => [
            'label' => 'Åbn handlinger',
        ],

    ],

    'empty' => [
        'heading' => 'Ingen resultater',
    ],

    'selection_indicator' => [

        'buttons' => [

            'select_all' => [
                'label' => 'Vælg alle :count',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Sorter efter',
            ],

            'direction' => [

                'label' => 'Sorteringsretning',

                'options' => [
                    'asc' => 'Stigende',
                    'desc' => 'Faldende',
                ],

            ],

        ],

    ],

];
