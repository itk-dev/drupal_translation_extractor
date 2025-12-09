<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class HmmTest extends TestCase
{
  public function testHmm(): void
    {
        $string = 'user@example.com';

        $this->assertSame($string, '');
    }
  }
