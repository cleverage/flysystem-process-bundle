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
 */
class ListContentTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected ?array $fsContent = null;

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
        if (null === $this->fsContent || null === key($this->fsContent)) {
            $filesystem = $this->storages->get($this->getOption($state, 'filesystem'));
            $pattern = $this->getOption($state, 'file_pattern');

            $this->fsContent = $this->getFilteredFilesystemContents($filesystem, $pattern);
        }

        if (null === key($this->fsContent)) {
            $state->setSkipped(true);
            $this->fsContent = null;
        } else {
            $state->setOutput(current($this->fsContent));
        }
    }

    public function next(ProcessState $state): bool
    {
        if (!\is_array($this->fsContent)) {
            return false;
        }

        next($this->fsContent);

        return null !== key($this->fsContent);
    }

    /**
     * @throws FilesystemException
     */
    protected function getFilteredFilesystemContents(FilesystemOperator $filesystem, ?string $pattern = null): array
    {
        $results = [];
        foreach ($filesystem->listContents('') as $item) {
            if (null === $pattern || preg_match($pattern, (string) $item['path'])) {
                $results[] = $item;
            }
        }

        return $results;
    }
}
