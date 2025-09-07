<?php

declare(strict_types=1);

namespace Tests\Unit;

use CMS\Models\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function testGetPublishedContentByType()
    {
        // This test would require a database connection and data.
        // For now, we'll just check that the method exists.
        $this->assertTrue(method_exists(Content::class, 'getPublishedContentByType'));
    }

    public function testGetForAdminSorting()
    {
        // This test would require a database connection and data.
        // For now, we'll just check that the method exists.
        $this->assertTrue(method_exists(Content::class, 'getForAdmin'));
    }
}
