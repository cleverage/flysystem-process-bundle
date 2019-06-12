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

use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Tools to use filesystem inside task configurations
 */
trait FilesystemOptionTrait
{
    protected function configureFilesystemOption(OptionsResolver $resolver, $optionName)
    {
        $resolver->setRequired($optionName);
        $resolver->setAllowedTypes($optionName, 'string');
        $resolver->setNormalizer($optionName, function (Options $options, $value) {
            return $this->getMountManager()->getFilesystem($value);
        });
    }

    protected function getFilesystem(ProcessState $state, $optionName): FilesystemInterface
    {
        return $this->getOption($state, $optionName);
    }

    abstract protected function getMountManager(): MountManager;

    /**
     * @see \CleverAge\ProcessBundle\Model\AbstractConfigurableTask::getOption
     *
     * @param ProcessState $state
     * @param string             $code
     *
     * @return mixed
     */
    abstract protected function getOption(ProcessState $state, $code);
}
