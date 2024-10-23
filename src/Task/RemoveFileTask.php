<?php declare(strict_types=1);
/**
 * This file is part of the CleverAge/FlysystemProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\FlysystemProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Remove a file from a filesystem
 */
class RemoveFileTask extends AbstractConfigurableTask
{
    use FilesystemOptionTrait;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureFilesystemOption($resolver, 'filesystem');
    }

    public function execute(ProcessState $state): void
    {
        $filesystem = $this->getFilesystem($state, 'filesystem');
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
