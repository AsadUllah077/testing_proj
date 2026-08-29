<?php

namespace Tests\Unit;

use Tests\TestCase;

class ResolveActivityFieldValueTest extends TestCase
{
    public function test_it_formats_array_activity_values_as_safe_readable_json(): void
    {
        $value = [
            ['key' => 'RAM', 'value' => '16 GB'],
            ['key' => 'Storage', 'value' => '512 GB'],
        ];

        $this->assertSame(
            '[{"key":"RAM","value":"16 GB"},{"key":"Storage","value":"512 GB"}]',
            resolveActivityFieldValue('specifications', $value)
        );
    }

    public function test_it_formats_an_empty_array_using_the_existing_empty_value_placeholder(): void
    {
        $this->assertSame('---', resolveActivityFieldValue('specifications', []));
    }

    public function test_it_preserves_existing_scalar_activity_values(): void
    {
        $this->assertSame('Ready for Sale', resolveActivityFieldValue('status', 'Ready for Sale'));
    }
}
