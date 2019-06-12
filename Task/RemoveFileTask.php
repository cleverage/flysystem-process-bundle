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
use League\Flysystem\MountManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Remove a file from a filesystem
 */
class RemoveFileTask extends AbstractConfigurableTask
{
    use FilesystemOptionTrait;

    /** @var  MountManager */
    protected $mountManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * RemoveFileTask constructor.
     *
     * @param MountManager    $mountManager
     * @param LoggerInterface $logger
     */
    public function __construct(MountManager $mountManager, LoggerInterface $logger)
    {
        $this->mountManager = $mountManager;
        $this->logger = $logger;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $this->configureFilesystemOption($resolver, 'filesystem');
    }

    public function execute(ProcessState $state)
    {
        $filesystem = $this->getFilesystem($state, 'filesystem');
        $filePath = $state->getInput();

        $success = $filesystem->delete($filePath);

        if ($success) {
            $this->logger->info('Deleted input file', ['file' => $filePath]);
        } else {
            $this->logger->warning('Failed to deleted input file', ['file' => $filePath]);
        }
    }

    protected function getMountManager(): MountManager
    {
        return $this->mountManager;
    }

}
