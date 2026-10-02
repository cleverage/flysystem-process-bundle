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
 * Copy (or move) file from one filesystem to another, using Flysystem
 * Either get files using a file regexp, or take files from input.
 */
class FileFetchTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    protected FilesystemOperator $sourceFS;

    protected FilesystemOperator $destinationFS;

    /**
     * Files of the current input, null when no iteration is in progress.
     *
     * @var list<string>|null
     */
    protected ?array $matchingFiles = null;

    /**
     * @param ServiceLocator<FilesystemOperator> $storages
     */
    public function __construct(protected readonly ServiceLocator $storages)
    {
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function initialize(ProcessState $state): void
    {
        // Configure options
        parent::initialize($state);

        /** @var string $sourceFilesystemOption */
        $sourceFilesystemOption = $this->getOption($state, 'source_filesystem');
        $this->sourceFS = $this->storages->get($sourceFilesystemOption);
        /** @var string $destinationFilesystemOption */
        $destinationFilesystemOption = $this->getOption($state, 'destination_filesystem');
        $this->destinationFS = $this->storages->get($destinationFilesystemOption);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws FilesystemException
     */
    public function execute(ProcessState $state): void
    {
        // The files are listed once per input, when its iteration starts
        $this->matchingFiles ??= $this->findMatchingFiles($state);

        $file = current($this->matchingFiles);
        if (false === $file) {
            $this->matchingFiles = null;
            $state->setSkipped(true);

            return;
        }

        /** @var bool $removeSourceOption */
        $removeSourceOption = $this->getOption($state, 'remove_source');
        $this->doFileCopy($state, $file, $removeSourceOption);
        $state->setOutput($file);
    }

    public function next(ProcessState $state): bool
    {
        if (null === $this->matchingFiles) {
            return false;
        }
        if (false !== next($this->matchingFiles)) {
            return true;
        }

        // End of the iteration: the next input lists its files again
        $this->matchingFiles = null;

        return false;
    }

    /**
     * @return list<string>
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    protected function findMatchingFiles(ProcessState $state): array
    {
        /** @var bool $ignoreMissing */
        $ignoreMissing = $this->getOption($state, 'ignore_missing');
        $matchingFiles = [];

        /** @var ?string $filePattern */
        $filePattern = $this->getOption($state, 'file_pattern');
        if (null !== $filePattern && '' !== $filePattern) {
            foreach ($this->sourceFS->listContents('/') as $file) {
                if ('file' === $file->type() && preg_match($filePattern, $file->path())) {
                    $matchingFiles[] = $file->path();
                }
            }
        } else {
            $input = $state->getInput();
            if (null === $input || '' === $input || [] === $input) {
                throw new \UnexpectedValueException('No pattern neither input provided for the Task');
            }
            /** @var list<string> $files */
            $files = \is_array($input) ? array_values($input) : [$input];
            foreach (array_unique($files) as $file) {
                if ($this->sourceFS->fileExists($file)) {
                    $matchingFiles[] = $file;
                } elseif (!$ignoreMissing) {
                    throw new \UnexpectedValueException("File {$file} not found in source filesystem");
                }
            }
        }
        if ([] === $matchingFiles && !$ignoreMissing) {
            throw new \UnexpectedValueException('File(s) not found in source filesystem');
        }

        return $matchingFiles;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    protected function doFileCopy(ProcessState $state, string $filename, bool $removeSource): bool|string|null
    {
        $buffer = $this->sourceFS->readStream($filename);

        // A write failure is not caught: the task fails (the error strategy applies) and the source is kept
        try {
            $this->destinationFS->writeStream($filename, $buffer);
        } finally {
            if (\is_resource($buffer)) {
                fclose($buffer);
            }
        }

        if ($removeSource) {
            $this->sourceFS->delete($filename);
        }

        return $filename;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['source_filesystem', 'destination_filesystem']);
        $resolver->setAllowedTypes('source_filesystem', 'string');
        $resolver->setAllowedTypes('destination_filesystem', 'string');

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['string', 'null']);

        $resolver->setDefault('remove_source', false);
        $resolver->setAllowedTypes('remove_source', 'boolean');

        $resolver->setDefault('ignore_missing', true);
        $resolver->setAllowedTypes('ignore_missing', 'boolean');
    }
}
