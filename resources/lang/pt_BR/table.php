<?php

return [

    'columns' => [

        'tags' => [
            'more' => 'e :count mais',
        ],

        'messages' => [
            'copied' => 'Copiado',
        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Marcar/desmarcar todos os itens para ações em massa.',
        ],

        'bulk_select_record' => [
            'label' => 'Marcar/desmarcar o item :key para ações em massa.',
        ],

        'search_query' => [
            'label' => 'Procurar',
            'placeholder' => 'Procurar',
        ],

    ],

    'pagination' => [

        'label' => 'Paginação',

        'overview' => '{1} Exibindo 1 resultado|[2,*] Exibindo :first a :last de :total resultados',

        'fields' => [

            'records_per_page' => [

                'label' => 'por página',

                'options' => [
                    'all' => 'Todas',
                ],

            ],

        ],

        'buttons' => [

            'go_to_page' => [
                'label' => 'Ir para página :page',
            ],

            'next' => [
                'label' => 'Próximo',
            ],

            'previous' => [
                'label' => 'Anterior',
            ],

        ],

    ],

    'buttons' => [

        'disable_reordering' => [
            'label' => 'Concluir a reordenação de registros',
        ],

        'enable_reordering' => [
            'label' => 'Reordenar registros',
        ],

        'filter' => [
            'label' => 'Filtrar',
        ],

        'open_actions' => [
            'label' => 'Ações abertas',
        ],

        'toggle_columns' => [
            'label' => 'Alternar colunas',
        ],

    ],

    'empty' => [

        'heading' => 'Sem registros',

        'buttons' => [

            'reset_column_searches' => [
                'label' => 'Limpar pesquisa de colunas',
            ],

        ],

    ],

    'filters' => [

        'buttons' => [

            'remove' => [
                'label' => 'Remover filtro',
            ],

            'remove_all' => [
                'label' => 'Remover todos os filtros',
                'tooltip' => 'Remover todos os filtros',
            ],

            'reset' => [
                'label' => 'Limpar filtros',
            ],

        ],

        'indicator' => 'Filtros ativos',

        'multi_select' => [
            'placeholder' => 'Todos',
        ],

        'select' => [
            'placeholder' => 'Todos',
        ],

        'trashed' => [

            'label' => 'Registros excluídos',

            'only_trashed' => 'Somente registros excluídos',

            'with_trashed' => 'Exibir registros excluídos',

            'without_trashed' => 'Não exibir registros excluídos',

        ],

    ],

    'reorder_indicator' => 'Arraste e solte os registros na ordem.',

    'selection_indicator' => [

        'selected_count' => '1 registro selecionado.|:count registros selecionados.',

        'buttons' => [

            'select_all' => [
                'label' => 'Selecione todos os :count',
            ],

            'deselect_all' => [
                'label' => 'Desselecionar todos',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Ordenar por',
            ],

            'direction' => [

                'label' => 'Direção de ordenação',

                'options' => [
                    'asc' => 'Ascendente',
                    'desc' => 'Descendente',
                ],

            ],

        ],

    ],

];
