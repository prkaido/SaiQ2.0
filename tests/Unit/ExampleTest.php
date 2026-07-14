<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that demonstrates true equals true (example only)
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test basic arithmetic
     */
    public function test_basic_arithmetic(): void
    {
        $this->assertEquals(2, 1 + 1);
        $this->assertNotEquals(3, 1 + 1);
    }

    /**
     * Test string operations
     */
    public function test_string_operations(): void
    {
        $string = 'hello';
        
        $this->assertEquals('hello', $string);
        $this->assertStringContainsString('ell', $string);
        $this->assertStringStartsWith('hel', $string);
    }

    /**
     * Test array operations
     */
    public function test_array_operations(): void
    {
        $array = [1, 2, 3, 4, 5];
        
        $this->assertContains(3, $array);
        $this->assertCount(5, $array);
        $this->assertIsArray($array);
    }
}
