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
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Remove a file from a filesystem.
 */
class RemoveFileTask extends AbstractConfigurableTask
{
    /**
     * @param ServiceLocator<FilesystemOperator> $storages
     */
    public function __construct(protected LoggerInterface $logger, protected readonly ServiceLocator $storages)
    {
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('filesystem');
        $resolver->setAllowedTypes('filesystem', 'string');
    }

    public function execute(ProcessState $state): void
    {
        /** @var string $filesystemOption */
        $filesystemOption = $this->getOption($state, 'filesystem');
        $filesystem = $this->storages->get($filesystemOption);
        /** @var string $filePath */
        $filePath = $state->getInput();

        try {
            $filesystem->delete($filePath);
            $result = true;
        } catch (FilesystemException) {
            $result = false;
        }

        if ($result) {
            $this->logger->info('Deleted input file', ['file' => $filePath]);
        } else {
            $this->logger->warning('Failed to deleted input file', ['file' => $filePath]);
        }
    }
}
