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

namespace CleverAge\FlysystemProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Tools to use filesystem inside task configurations.
 */
trait FilesystemOptionTrait
{
    protected function configureFilesystemOption(OptionsResolver $resolver, string|array $optionName): void
    {
        $resolver->setRequired($optionName);
        $resolver->setAllowedTypes($optionName, 'string');
        $resolver->setNormalizer($optionName, fn (Options $options, $value) => new Filesystem($value));
    }

    protected function getFilesystem(ProcessState $state, string $optionName): FilesystemOperator
    {
        return $this->getOption($state, $optionName);
    }

    /**
     * @throws \InvalidArgumentException
     */
    abstract protected function getOption(ProcessState $state, string $code): mixed;
}
