<?php

namespace Tests\Unit;

use App\Support\IndoNumber;
use PHPUnit\Framework\TestCase;

class IndoNumberTest extends TestCase
{
    public function test_decimal_trims_unneeded_zeroes(): void
    {
        $this->assertSame('2', IndoNumber::decimal(2));
        $this->assertSame('1.000', IndoNumber::decimal(1000));
        $this->assertSame('2,5', IndoNumber::decimal(2.5));
        $this->assertSame('2.500,5', IndoNumber::decimal(2500.5));
        $this->assertSame('2.500,55', IndoNumber::decimal(2500.55));
    }

    public function test_rupiah_uses_natural_decimal_format(): void
    {
        $this->assertSame('Rp 2.500,00', IndoNumber::rupiah(2500));
        $this->assertSame('Rp 2.500,50', IndoNumber::rupiah(2500.5));
    }
}
