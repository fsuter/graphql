<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Functional\GraphQL;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Exception;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\GraphQL\EntityReader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\GraphQL\Exception\NotSupportedException;
use TYPO3\CMS\Core\GraphQL\Exception\SchemaException;

/**
 * Test case
 */
class EntityReaderTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/graphql',
        'typo3/sysext/graphql/Tests/Functional/GraphQL/Fixtures/Extensions/persistence',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/LiveDefaultEntities.csv');
    }

    public function scalarPropertyQueryProvider()
    {
        return [
            [
                '{
                    tt_content {
                        uid
                        header
                    }
                }',
                [
                    'data' => [
                        'tt_content' => [
                            ['uid' => 513, 'header' => 'Content 2'],
                            ['uid' => 514, 'header' => 'Content 3'],
                            ['uid' => 512, 'header' => 'Content 1']
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        uid
                        scalar_float,
                        scalar_string
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['uid' => 1027, 'scalar_float' => -3.1415, 'scalar_string' => 'String'],
                            ['uid' => 1025, 'scalar_float' => 3.1415, 'scalar_string' => null],
                            ['uid' => 1024, 'scalar_float' => 0, 'scalar_string' => 'String'],
                            ['uid' => 1026, 'scalar_float' => 0, 'scalar_string' => null],
                            ['uid' => 1028, 'scalar_float' => 0, 'scalar_string' => null]
                        ]
                    ]
                ]
            ],
            [
                '{
                    pages {
                        title
                    }
                }',
                [
                    'data' => [
                        'pages' => [
                            ['title' => 'Page 1'],
                            ['title' => 'Page 1.1'],
                            ['title' => 'Page 1.2']
                        ]
                    ]
                ]
            ],
            [
                '{
                    sys_category {
                        uid
                    }
                }',
                [
                    'data' => [
                        'sys_category' => [
                            ['uid' => 32],
                            ['uid' => 33],
                            ['uid' => 34]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider scalarPropertyQueryProvider
     */
    public function readScalarProperty(string $query, array $expected)
    {
        $reader = new EntityReader();
        $result = $reader->execute($query);
        $this->assertEquals($expected, $result);
    }

    public function relationPropertyQueryProvider()
    {
        return [
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_inline_11_file_reference {
                            title
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_inline_11_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_inline_11_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_inline_11_file_reference' => [
                                    ['title' => 'File reference 1']
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_inline_11_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_inline_11_file_reference' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_inline_1n_file_reference {
                            title
                            description
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_inline_1n_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_inline_1n_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_inline_1n_file_reference' => [
                                    ['title' => 'File reference 2', 'description' => null],
                                    ['title' => 'File reference 3', 'description' => null]
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_inline_1n_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_inline_1n_file_reference' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_inline_1n_csv_file_reference {
                            title
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_inline_1n_csv_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_inline_1n_csv_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_inline_1n_csv_file_reference' => [
                                    ['title' => 'File reference 4'],
                                    ['title' => 'File reference 5']
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_inline_1n_csv_file_reference' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_inline_1n_csv_file_reference' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_inline_mn_mm_content {
                            header
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_inline_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_inline_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_inline_mn_mm_content' => [
                                    ['header' => 'Content 1'],
                                    ['header' => 'Content 2'],
                                    ['header' => 'Content 3']
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_inline_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_inline_mn_mm_content' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_inline_mn_symmetric_entity {
                            peer {
                                title
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_inline_mn_symmetric_entity' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_inline_mn_symmetric_entity' => [
                                    [
                                        'peer' => [
                                            ['title' => 'Entity 2']
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_inline_mn_symmetric_entity' => [
                                    [
                                        'peer' => [
                                            ['title' => 'Entity 2']
                                        ]
                                    ],
                                    [
                                        'peer' => [
                                            ['title' => 'Entity 3']
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_inline_mn_symmetric_entity' => [
                                    [
                                        'peer' => [
                                            ['title' => 'Entity 3']
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_inline_mn_symmetric_entity' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_select_1n_page {
                            title
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_select_1n_page' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_select_1n_page' => [
                                    ['title' => 'Page 1.1']
                                ]
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_select_1n_page' => []
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_select_1n_page' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_select_1n_page' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_select_mn_csv_category {
                            title
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_select_mn_csv_category' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_select_mn_csv_category' => [
                                    ['title' => 'Category 1.1'],
                                    ['title' => 'Category 1.2']
                                ]
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_select_mn_csv_category' => []
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_select_mn_csv_category' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_select_mn_csv_category' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_select_mn_mm_content {
                            header
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_select_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_select_mn_mm_content' => [
                                    ['header' => 'Content 1'],
                                    ['header' => 'Content 2'],
                                    ['header' => 'Content 3']
                                ]
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_select_mn_mm_content' => [
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_select_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_select_mn_mm_content' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_group_1n_content_page {
                            ... on tt_content {
                                header
                            }
                            ... on pages {
                                title
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_group_1n_content_page' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_group_1n_content_page' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_group_1n_content_page' => []
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_group_1n_content_page' => [
                                    ['title' => 'Page 1.2']
                                ]
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_group_1n_content_page' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_group_mn_csv_content_page {
                            ... on tt_content {
                                header
                            }
                            ... on pages {
                                title
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_group_mn_csv_content_page' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_group_mn_csv_content_page' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_group_mn_csv_content_page' => []
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_group_mn_csv_content_page' => [
                                    ['header' => 'Content 2'],
                                    ['title' => 'Page 1.1'],
                                    ['header' => 'Content 3'],
                                    ['title' => 'Page 1.2'],
                                    ['header' => 'Content 1'],
                                    ['title' => 'Page 1']
                                ]
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_group_mn_csv_content_page' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_group_mn_mm_content_page {
                            ... on tt_content {
                                header
                            }
                            ... on pages {
                                title
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 4',
                                'relation_group_mn_mm_content_page' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_group_mn_mm_content_page' => []
                            ],
                            [
                                'title' => 'Entity 1',
                                'relation_group_mn_mm_content_page' => []
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_group_mn_mm_content_page' => [
                                    ['title' => 'Page 1.2'],
                                    ['title' => 'Page 1'],
                                    ['header' => 'Content 3'],
                                    ['header' => 'Content 1'],
                                    ['title' => 'Page 1.1']
                                ]
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_group_mn_mm_content_page' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider relationPropertyQueryProvider
     */
    public function readRelationProperty(string $query, array $expected)
    {
        $reader = new EntityReader();
        $result = $reader->execute($query);
        $this->assertEquals($expected, $result);
    }

    public function orderResultQueryProvider()
    {
        return [
            [
                '{
                    tx_persistence_entity (order: "title descending") {
                        title
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['title' => 'Entity 5'],
                            ['title' => 'Entity 4'],
                            ['title' => 'Entity 3'],
                            ['title' => 'Entity 2'],
                            ['title' => 'Entity 1']
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity (order: "scalar_string ascending, title ascending") {
                        scalar_string,
                        title
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['scalar_string' => '', 'title' => 'Entity 2'],
                            ['scalar_string' => '', 'title' => 'Entity 3'],
                            ['scalar_string' => '', 'title' => 'Entity 5'],
                            ['scalar_string' => 'String', 'title' => 'Entity 1'],
                            ['scalar_string' => 'String', 'title' => 'Entity 4'],
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity (order: "scalar_string on tx_persistence_entity descending, title ascending") {
                        scalar_string,
                        title
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['scalar_string' => 'String', 'title' => 'Entity 1'],
                            ['scalar_string' => 'String', 'title' => 'Entity 4'],
                            ['scalar_string' => '', 'title' => 'Entity 2'],
                            ['scalar_string' => '', 'title' => 'Entity 3'],
                            ['scalar_string' => '', 'title' => 'Entity 5']
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity (order: "title ascending") {
                        title
                        relation_select_mn_mm_content (order: "bodytext ascending, header descending") {
                            header
                            bodytext
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'title' => 'Entity 1',
                                'relation_select_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 2',
                                'relation_select_mn_mm_content' => [
                                    ['header' => 'Content 2', 'bodytext' => ''],
                                    ['header' => 'Content 3', 'bodytext' => 'Lorem ipsum dolor...'],
                                    ['header' => 'Content 1', 'bodytext' => 'Lorem ipsum dolor...']
                                ]
                            ],
                            [
                                'title' => 'Entity 3',
                                'relation_select_mn_mm_content' => [
                                ]
                            ],
                            [
                                'title' => 'Entity 4',
                                'relation_select_mn_mm_content' => []
                            ],
                            [
                                'title' => 'Entity 5',
                                'relation_select_mn_mm_content' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity {
                        uid
                        relation_group_mn_csv_content_page (order: "pid ascending, title on pages ascending, header on tt_content descending") {
                            uid
                            ... on pages {
                                title
                            }
                            ... on tt_content {
                                header
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'uid' => '1027',
                                'relation_group_mn_csv_content_page' => []
                            ],
                            [
                                'uid' => '1025',
                                'relation_group_mn_csv_content_page' => []
                            ],
                            [
                                'uid' => '1024',
                                'relation_group_mn_csv_content_page' => []
                            ],
                            [
                                'uid' => '1026',
                                'relation_group_mn_csv_content_page' => [
                                    ['uid' => '128', 'title' => 'Page 1'],
                                    ['uid' => '514', 'header' => 'Content 3'],
                                    ['uid' => '513', 'header' => 'Content 2'],
                                    ['uid' => '512', 'header' => 'Content 1'],
                                    ['uid' => '129', 'title' => 'Page 1.1'],
                                    ['uid' => '130', 'title' => 'Page 1.2'],
                                ]
                            ],
                            [
                                'uid' => '1028',
                                'relation_group_mn_csv_content_page' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity (order: "title descending") {
                        uid
                        relation_group_mn_mm_content_page (order: "pid ascending, title on pages ascending, header on tt_content descending") {
                            uid
                            ... on pages {
                                title
                            }
                            ... on tt_content {
                                header
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            [
                                'uid' => '1028',
                                'relation_group_mn_mm_content_page' => []
                            ],
                            [
                                'uid' => '1027',
                                'relation_group_mn_mm_content_page' => []
                            ],
                            [
                                'uid' => '1026',
                                'relation_group_mn_mm_content_page' => [
                                    ['uid' => '128', 'title' => 'Page 1'],
                                    ['uid' => '130', 'title' => 'Page 1.2'],
                                    ['uid' => '129', 'title' => 'Page 1.1'],
                                    ['uid' => '514', 'header' => 'Content 3'],
                                    ['uid' => '512', 'header' => 'Content 1'],
                                ]
                            ],
                            [
                                'uid' => '1025',
                                'relation_group_mn_mm_content_page' => []
                            ],
                            [
                                'uid' => '1024',
                                'relation_group_mn_mm_content_page' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider orderResultQueryProvider
     */
    public function orderResult(string $query, array $expected)
    {
        $reader = new EntityReader();
        $result = $reader->execute($query);
        $this->assertEquals($expected, $result);
    }

    public function filterRestrictedQueryProvider()
    {
        return [
            [
                '{
                    tx_persistence_entity(filter: "scalar_string = `String`") {
                        uid
                    }
                }',
                [],
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['uid' => 1027],
                            ['uid' => 1024]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity(filter: "not uid = 1026") {
                        uid
                    }
                }',
                [],
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['uid' => 1027],
                            ['uid' => 1025],
                            ['uid' => 1024],
                            ['uid' => 1028]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity(filter: "scalar_float = 3.1415 or scalar_integer = 1") {
                        uid
                    }
                }',
                [],
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['uid' => 1027],
                            ['uid' => 1025],
                            ['uid' => 1026]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity(filter: "scalar_float = -3.1415 or scalar_float = 3.1415 and l10n_state = null") {
                        uid
                    }
                }',
                [],
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['uid' => 1027],
                            ['uid' => 1025]
                        ]
                    ]
                ]
            ],
            [
                '{
                    tx_persistence_entity(filter: "scalar_text = `` and not (scalar_float = -3.1415 or scalar_integer = null)") {
                        uid
                    }
                }',
                [],
                [
                    'data' => [
                        'tx_persistence_entity' => [
                            ['uid' => 1025],
                            ['uid' => 1024],
                            ['uid' => 1026]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider filterRestrictedQueryProvider
     */
    public function readFilterRestricted(string $query, array $bindings, array $expected)
    {
        $reader = new EntityReader();
        $result = $reader->execute($query, $bindings);
        $this->assertEquals($expected, $result);
    }

    public function contextRestrictedQueryProvider()
    {
        return [];
    }

    /**
     * @test
     * @dataProvider contextRestrictedQueryProvider
     */
    public function readContextRestricted(string $query, Context $context, array $expected)
    {
        $reader = new EntityReader();
        $result = $reader->execute($query, [], $context);
        $this->assertEquals($expected, $result);
    }

    public function unsupportedQueryProvider()
    {
        return [
            [
                '{
                    tx_persistence_entity(order: "relation_select_mn_mm_content ascending") {
                        title
                        relation_select_mn_mm_content {
                            header
                        }
                    }
                }',
                NotSupportedException::class,
                1560598442
            ],
            [
                '{
                    tx_persistence_entity(order: "relation_select_mn_mm_content.header ascending") {
                        title
                        relation_select_mn_mm_content {
                            header
                        }
                    }
                }',
                NotSupportedException::class,
                1560598442
            ],
            [
                '{
                    tx_persistence_entity (order: "title on String ascending") {
                        title
                    }
                }',
                NotSupportedException::class,
                1560598849
            ],
            [
                '{
                    tx_persistence_entity (order: "title on Entity ascending") {
                        title
                    }
                }',
                NotSupportedException::class,
                1560648120
            ]
        ];
    }

    /**
     * @test
     * @dataProvider unsupportedQueryProvider
     */
    public function throwUnsupported(string $query, string $exceptionClass, int $exceptionCode)
    {
        try {
            $reader = new EntityReader();
            $reader->execute($query);
        } catch (Exception $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertEquals($exceptionCode, $exception->getCode());
        }
    }

    public function invalidQueryProvider()
    {
        return [
            [
                '{
                    tx_persistence_entity (order: "unknown ascending") {
                        title
                    }
                }',
                SchemaException::class,
                1560645175
            ],
            [
                '{
                    tx_persistence_entity (order: "title on unknown ascending") {
                        title
                    }
                }',
                SchemaException::class,
                1560598849
            ],
            [
                '{
                    tx_persistence_entity {
                        title
                        relation_select_mn_mm_content (order: "title on tx_persistence_entity ascending") {
                            header
                        }
                    }
                }',
                SchemaException::class,
                1560655028
            ]
        ];
    }

    /**
     * @test
     * @dataProvider invalidQueryProvider
     */
    public function throwInvalid(string $query, string $exceptionClass, int $exceptionCode)
    {
        try {
            $reader = new EntityReader();
            $reader->execute($query);
        } catch (Exception $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertEquals($exceptionCode, $exception->getCode());
        }
    }
}
