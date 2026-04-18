<?php

namespace Tests\Unit;

use App\Support\InboundMessageTimestamp;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InboundMessageTimestampTest extends TestCase
{
    #[Test]
    public function it_parses_seconds_and_respects_app_timezone(): void
    {
        config(['app.timezone' => 'Asia/Dhaka']);

        $c = InboundMessageTimestamp::parse(1_700_000_000);

        $this->assertInstanceOf(Carbon::class, $c);
        $this->assertSame('Asia/Dhaka', $c->timezone->getName());
        $this->assertSame(1_700_000_000, $c->utc()->unix());
    }

    #[Test]
    public function it_parses_millisecond_epoch(): void
    {
        config(['app.timezone' => 'UTC']);

        $c = InboundMessageTimestamp::parse(1_700_000_000_000);

        $this->assertSame(1_700_000_000, $c->unix());
    }

    #[Test]
    public function it_returns_null_for_invalid(): void
    {
        $this->assertNull(InboundMessageTimestamp::parse(null));
        $this->assertNull(InboundMessageTimestamp::parse(''));
        $this->assertNull(InboundMessageTimestamp::parse(0));
        $this->assertNull(InboundMessageTimestamp::parse(-1));
    }
}
