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
 * Remove files from a filesystem.
 */
class RemoveFileTask extends AbstractConfigurableTask
{
    private FilesystemOperator $filesystem;

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

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['string', 'null']);
    }

    public function execute(ProcessState $state): void
    {
        /** @var string $filesystemOption */
        $filesystemOption = $this->getOption($state, 'filesystem');
        $this->filesystem = $this->storages->get($filesystemOption);

        /** @var ?string $filePattern */
        $filePattern = $this->getOption($state, 'file_pattern');
        if ($filePattern) {
            foreach ($this->filesystem->listContents('/') as $file) {
                if ('file' === $file->type() && preg_match($filePattern, $file->path())) {
                    $this->deleteFile($file->path());
                }
            }
        } else {
            /** @var ?string $input */
            $input = $state->getInput();
            if (!$input) {
                throw new \UnexpectedValueException('No pattern neither input provided for the Task');
            }

            $this->deleteFile($input);
        }
    }

    private function deleteFile(string $filePath): void
    {
        try {
            $this->filesystem->delete($filePath);
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
