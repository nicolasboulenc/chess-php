<?php
declare(strict_types=1);
namespace Test;

require("pgn.php");

final class PGNTest extends \PHPUnit\Framework\TestCase
{
    public function testCanBeCreatedFromValidEmailAddress(): void
    {
        $this->assertInstanceOf(
            \Chess\PGN::class,
            new \Chess\PGN()
        );
    }
}
