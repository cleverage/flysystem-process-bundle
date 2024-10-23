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
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Iterate over the content of a filesystem
 */
class ListContentTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    use FilesystemOptionTrait;

    protected ?array $fsContent = null;

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureFilesystemOption($resolver, 'filesystem');

        $resolver->setDefault('file_pattern', null);
        $resolver->setAllowedTypes('file_pattern', ['null', 'string']);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     */
    public function execute(ProcessState $state): void
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

    public function next(ProcessState $state): bool
    {
        if (!is_array($this->fsContent)) {
            return false;
        }

        next($this->fsContent);

        return key($this->fsContent) !== null;
    }

    /**
     * @throws FilesystemException
     */
    protected function getFilteredFilesystemContents(FilesystemOperator $filesystem, string $pattern = null): array
    {
        $results = [];
        foreach ($filesystem->listContents('') as $item) {
            if ($pattern === null || \preg_match($pattern, $item['path'])) {
                $results[] = $item;
            }
        }

        return $results;
    }
}
