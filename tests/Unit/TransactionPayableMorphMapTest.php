<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\TestCase;

class TransactionPayableMorphMapTest extends TestCase
{
    public function test_transaction_payable_aliases_resolve_to_model_classes(): void
    {
        $this->assertSame(Customer::class, Relation::getMorphedModel('customer'));
        $this->assertSame(Employee::class, Relation::getMorphedModel('employee'));
        $this->assertSame(Vendor::class, Relation::getMorphedModel('vendor'));
    }
}
