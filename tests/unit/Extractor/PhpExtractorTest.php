<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhpExtractorTest extends TestCase
{
    public function testExtract(): void
    {
        $this->assertSame(0, '');
    }
}
