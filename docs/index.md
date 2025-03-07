## Prerequisite

CleverAge/ProcessBundle must be [installed](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#installation.

## Installation

Make sure Composer is installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and install it using composer:

```bash
composer require cleverage/flysystem-process-bundle
```

Remember to add the following line to config/bundles.php (not required if Symfony Flex is used)

```php
CleverAge\FlysystemProcessBundle\CleverAgeFlysystemProcessBundle::class => ['all' => true],
```

Configure at least one flysytem/storage into `config/packages/flysytem.yaml`

```yaml
#config/packages/flysytem.yaml
flysystem:
  storages:
    storage.source: # This is the identifier of flysytem/storage
    adapter: 'local'
    options:
      directory: '%kernel.project_dir%/var/storage/source'
```

See https://github.com/thephpleague/flysystem-bundle?tab=readme-ov-file for more sample configuration (sftp, ftp, amazon s3 ...)


## Reference

- Tasks
  - [FileFetchTask](reference/tasks/01-FileFetchTask.md)
  - [ListContentTask](reference/tasks/02-ListContentTask.md)
  - [RemoveFileTask](reference/tasks/03-RemoveFileTask.md)
