<?php

namespace Tests\Unit;

use App\Http\Controllers\Logistics\ManageBookingV2Controller;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class ManageBookingBankInstrumentTest extends TestCase
{
    public function test_documentary_number_is_mapped_for_documentary_collection(): void
    {
        $data = $this->bankInstrumentData([
            'bank_instrument_type' => 'documentary_collection',
            'documentary_number' => 'DOC-2026-0041',
        ]);

        $this->assertSame('documentary_collection', $data['bank_instrument_type']);
        $this->assertSame('DOC-2026-0041', $data['documentary_number']);
    }

    public function test_documentary_number_is_cleared_for_other_instrument_types(): void
    {
        $data = $this->bankInstrumentData([
            'bank_instrument_type' => 'open_account',
            'documentary_number' => 'DOC-2026-0041',
        ]);

        $this->assertSame('open_account', $data['bank_instrument_type']);
        $this->assertNull($data['documentary_number']);
    }

    private function bankInstrumentData(array $input): array
    {
        $controller = new ManageBookingV2Controller;
        $method = new ReflectionMethod($controller, 'bankInstrumentData');

        return $method->invoke($controller, Request::create('/', 'POST', $input));
    }
}
