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
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Iterate over the content of a filesystem
 */
class ListContentTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    use FilesystemOptionTrait;

    /** @var  MountManager */
    protected $mountManager;

    /** @var array|null */
    protected $fsContent = null;

    /**
     * ListContentTask constructor.
     *
     * @param MountManager $mountManager
     */
    public function __construct(MountManager $mountManager)
    {
        $this->mountManager = $mountManager;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $this->configureFilesystemOption($resolver, 'filesystem');

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['null', 'string']);
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        if ($this->fsContent === null || key($this->fsContent) === null) {
            $filesystem = $this->getFilesystem($state, 'filesystem');
            $pattern = $this->getOption($state, 'file_pattern');

            $this->fsContent = $this->getFilteredFilesystemContents($filesystem, $pattern);
        }

        if (key($this->fsContent) === null) {
            $state->setSkipped(true);
            $this->fsContent = null;
        } else {
            $state->setOutput(current($this->fsContent));
        }
    }

    public function next(ProcessState $state)
    {
        if (!is_array($this->fsContent)) {
            return false;
        }

        next($this->fsContent);

        return key($this->fsContent) !== null;
    }


    /**
     * @param FilesystemInterface $filesystem
     * @param string|null         $pattern
     *
     * @return array
     */
    protected function getFilteredFilesystemContents(FilesystemInterface $filesystem, $pattern = null): array
    {
        $results = [];
        foreach ($filesystem->listContents() as $item) {
            if ($pattern === null || \preg_match($pattern, $item['path'])) {
                $results[] = $item;
            }
        }

        return $results;
    }

    /**
     * @return MountManager
     */
    protected function getMountManager(): MountManager
    {
        return $this->mountManager;
    }

}
