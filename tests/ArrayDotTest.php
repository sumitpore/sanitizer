<?php
use Rees\Sanitizer\ArrayDot;

class ArrayDotTest extends PHPUnit_Framework_TestCase
{

    public function testThatIsMultiDimensionalArrayMethodCanCheckIfArrayIsMultiDimensional()
    {
        $array = [
            'foo' => [
                'bar' => [
                    'baz' => 123,
                    'qux' => 456
                ]
            ],
            'comments' => [
                ['id' => 1, 'text' => 'foo'],
                ['id' => 2, 'text' => 'bar'],
                ['id' => 3, 'text' => 'baz'],
            ]
        ];

        $this->assertTrue(ArrayDot::isMultiDimensionalArray($array));

        $array =[
            'foo',
            'bar',
            'baz'
        ];

        $this->assertFalse(ArrayDot::isMultiDimensionalArray($array));
    }

    public function testThatCollapseMethodCanConvertCollectionOfArraysToSingleFlatArray()
    {
        $array = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9]
        ];

        $this->assertEquals(ArrayDot::collapse($array), [1, 2, 3, 4, 5, 6, 7, 8, 9]);
    }

    public function testThatResolveWildCardKeyThrowsExceptionIfKeyEndsWithDot()
    {
        $array = [
            'users' => [
                'subscribers' => [
                    'sumit' => [
                        'id' => '12345',
                        'name' => 'Sumit'
                    ],
                    'tony' => [
                        'id' => '780643'
                    ],
                    'steve' => [
                        'id' => '532678'
                    ]
                ]
            ]
        ];
        $this->setExpectedException('InvalidArgumentException', 'Key can not end with `.`');
        ArrayDot::resolveWildcardKey($array, 'a.id.');
    }

    public function testThatResolveWildCardKeyCanFindAllPossibleKeys()
    {
        
        $array = [
            'users' => [
                'subscribers' => [
                    'sumit' => [
                        'id' => '12345',
                        'name' => 'Sumit'
                    ],
                    'tony' => [
                        'id' => '780643',
                    ],
                    'steve' => [
                        'id' => '532678'
                    ]
                ]
            ]
        ];

        //Passing no key returns array of all dot keys
        $this->assertEquals(ArrayDot::resolveWildcardKey($array), [
            'users.subscribers.sumit.id',
            'users.subscribers.sumit.name',
            'users.subscribers.tony.id',
            'users.subscribers.steve.id'
        ]);

        // Passing no key is similar to passing only *
        $this->assertEquals(ArrayDot::resolveWildcardKey($array), ArrayDot::resolveWildcardKey($array, '*'));

        // Passing a non-wildcard key will return the key as it is if it is a valid key
        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.subscribers'), [
            'users.subscribers'
        ]);

        // Passing invalid keys returns blank array
        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.b.c'), []);

        // Each star represents one level of hierarchy. `id` key is present at 3rd level hierarchy
        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.*.id'), []);

        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.*.*.id'), [
            'users.subscribers.sumit.id',
            'users.subscribers.tony.id',
            'users.subscribers.steve.id'
        ]);

        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.subscribers.*'), [
            'users.subscribers.sumit.id',
            'users.subscribers.sumit.name',
            'users.subscribers.tony.id',
            'users.subscribers.steve.id'
        ]);

        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.subscribers.*.id'), [
            'users.subscribers.sumit.id',
            'users.subscribers.tony.id',
            'users.subscribers.steve.id'
        ]);


        $this->assertEquals(ArrayDot::resolveWildcardKey($array, 'users.*.sumit.*'), [
            'users.subscribers.sumit.id',
            'users.subscribers.sumit.name'
        ]);

        $this->assertEquals(ArrayDot::resolveWildcardKey($array, '*.*.sumit'), [
            'users.subscribers.sumit',
        ]);
    }
}
