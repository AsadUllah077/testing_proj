<?php

namespace Tests\Unit;

use App\Http\Controllers\Logistics\ManageBookingV2Controller;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class ChildBookingContainerNumbersTest extends TestCase
{
    public function test_multiple_container_numbers_are_trimmed_deduplicated_and_empty_values_removed(): void
    {
        $containerNumbers = $this->normalize([
            'container_nos' => [' MSCU-1001 ', '', 'MAEU-2002', 'MSCU-1001'],
        ]);

        $this->assertSame(['MSCU-1001', 'MAEU-2002'], $containerNumbers);
    }

    public function test_legacy_single_container_number_is_still_supported(): void
    {
        $containerNumbers = $this->normalize([
            'container_no' => 'MSCU-1001',
        ]);

        $this->assertSame(['MSCU-1001'], $containerNumbers);
    }

    private function normalize(array $input): array
    {
        $controller = new ManageBookingV2Controller;
        $method = new ReflectionMethod($controller, 'normalizeChildContainerNumbers');

        return $method->invoke($controller, Request::create('/', 'POST', $input));
    }
}
