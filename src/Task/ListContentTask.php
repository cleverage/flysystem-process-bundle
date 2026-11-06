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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Iterate over the content of a filesystem.
 *
 * @final since 3.3
 */
class ListContentTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    /**
     * The listing being iterated, read lazily (a huge storage is not loaded in memory).
     *
     * @var \Generator<int, \League\Flysystem\StorageAttributes>|null
     */
    protected ?\Generator $fsContent = null;

    /**
     * @param ServiceLocator<FilesystemOperator> $storages
     */
    public function __construct(protected readonly ServiceLocator $storages)
    {
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('filesystem');
        $resolver->setAllowedTypes('filesystem', 'string');
        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['null', 'string']);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    public function execute(ProcessState $state): void
    {
        if (!$this->fsContent instanceof \Generator || !$this->fsContent->valid()) {
            /** @var string $filesystemOption */
            $filesystemOption = $this->getOption($state, 'filesystem');
            $filesystem = $this->storages->get($filesystemOption);
            /** @var ?string $patternOption */
            $patternOption = $this->getOption($state, 'file_pattern');

            $this->fsContent = $this->getFilteredFilesystemContents($filesystem, $patternOption);
        }

        if (!$this->fsContent->valid()) {
            $state->setSkipped(true);
            $this->fsContent = null;
        } else {
            $state->setOutput($this->fsContent->current());
        }
    }

    public function next(ProcessState $state): bool
    {
        if (!$this->fsContent instanceof \Generator) {
            return false;
        }

        $this->fsContent->next();

        return $this->fsContent->valid();
    }

    /**
     * @return \Generator<int, \League\Flysystem\StorageAttributes>
     *
     * @throws FilesystemException
     */
    protected function getFilteredFilesystemContents(FilesystemOperator $filesystem, ?string $pattern = null): \Generator
    {
        foreach ($filesystem->listContents('') as $item) {
            if (null === $pattern || preg_match($pattern, $item->path())) {
                yield $item;
            }
        }
    }
}
