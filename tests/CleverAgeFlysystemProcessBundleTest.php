<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/FlysystemProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\FlysystemProcessBundle\Tests;

use CleverAge\FlysystemProcessBundle\CleverAgeFlysystemProcessBundle;
use CleverAge\FlysystemProcessBundle\DependencyInjection\CleverAgeFlysystemProcessExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CleverAgeFlysystemProcessBundle::class)]
#[UsesClass(CleverAgeFlysystemProcessExtension::class)]
class CleverAgeFlysystemProcessBundleTest extends TestCase
{
    public function testPathIsTheBundleRoot(): void
    {
        $path = (new CleverAgeFlysystemProcessBundle())->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path.'/config/services');
    }

    public function testExtensionAlias(): void
    {
        $bundle = new CleverAgeFlysystemProcessBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(CleverAgeFlysystemProcessExtension::class, $extension);
        self::assertSame('cleverage_flysystem_process', $extension->getAlias());
        // Built once
        self::assertSame($extension, $bundle->getContainerExtension());
    }
}
