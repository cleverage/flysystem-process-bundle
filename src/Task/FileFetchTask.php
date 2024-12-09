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
use League\Flysystem\Filesystem;
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

    protected array $matchingFiles = [];

    /**
     * @param MountManager|null $mountManager
     */
    public function __construct(protected readonly ServiceLocator $storages)
    {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function initialize(ProcessState $state): void
    {
        // Configure options
        parent::initialize($state);

        $this->sourceFS = $this->storages->get($this->getOption($state, 'source_filesystem'));
        $this->destinationFS = $this->storages->get($this->getOption($state, 'destination_filesystem'));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @throws FilesystemException
     */
    public function execute(ProcessState $state): void
    {
        $this->findMatchingFiles($state);

        $file = current($this->matchingFiles);
        if (!$file) {
            $state->setSkipped(true);

            return;
        }

        $this->doFileCopy($state, $file, $this->getOption($state, 'remove_source'));
        $state->setOutput($file);
    }

    /**
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    public function next(ProcessState $state): bool
    {
        $this->findMatchingFiles($state);

        return false === next($this->matchingFiles) ? false : true;
    }

    /**
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    protected function findMatchingFiles(ProcessState $state): void
    {
        $filePattern = $this->getOption($state, 'file_pattern');
        if ($filePattern) {
            foreach ($this->sourceFS->listContents('/') as $file) {
                if ('file' === $file['type']
                    && preg_match($filePattern, (string) $file['path'])
                    && !\in_array($file['path'], $this->matchingFiles, true)) {
                    $this->matchingFiles[] = $file['path'];
                }
            }
        } else {
            $input = $state->getInput();
            if (!$input) {
                throw new \UnexpectedValueException('No pattern neither input provided for the Task');
            }
            if (\is_array($input)) {
                foreach ($input as $file) {
                    if (!\in_array($file, $this->matchingFiles, true)) {
                        $this->matchingFiles[] = $file;
                    }
                }
            } elseif (!\in_array($input, $this->matchingFiles, true)) {
                $this->matchingFiles[] = $input;
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    protected function doFileCopy(ProcessState $state, string $filename, bool $removeSource): bool|string|null
    {
        $buffer = $this->sourceFS->readStream($filename);

        try {
            $this->destinationFS->writeStream($filename, $buffer);
            $result = true;
        } catch (FilesystemException) {
            $result = false;
        }

        if (\is_resource($buffer)) {
            fclose($buffer);
        }

        if ($removeSource) {
            $this->sourceFS->delete($filename);
        }

        return $result ? $filename : null;
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
    }
}
