## Prerequisite

CleverAge/ProcessBundle must be [installed](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#installation).

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

This bundle relies on [league/flysystem-bundle](https://github.com/thephpleague/flysystem-bundle), installed as a
dependency: the bundle `League\FlysystemBundle\FlysystemBundle` must be enabled as well. Adapters other than `local`
require their own package, e.g. `composer require league/flysystem-sftp-v3` for SFTP.

## Configuration

The tasks of this bundle work with the Flysystem **storages** configured in `config/packages/flysystem.yaml`: their
`filesystem`, `source_filesystem` and `destination_filesystem` options take the name of a storage (the key under
`flysystem.storages`). Configure at least one storage:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        local.storage: # Name of the storage, used in the task options
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/local'

        remote.storage:
            adapter: 'sftp'
            options:
                host: '%env(string:SFTP_HOST)%'
                port: 22
                username: '%env(string:SFTP_USERNAME)%'
                password: '%env(string:SFTP_PASSWORD)%'
                root: '%env(string:SFTP_ROOT)%'
```

Paths used and returned by the tasks are relative to the root of the storage (`directory` or `root` option).
Listings (`file_pattern` options, [ListContentTask](reference/tasks/list_content_task.md)) are not recursive: to work
in a sub-directory, configure a dedicated storage whose root is this directory.

See the [flysystem-bundle documentation](https://github.com/thephpleague/flysystem-bundle?tab=readme-ov-file)
for the configuration of other adapters (FTP, Amazon S3, Azure, Google Cloud Storage...).

## Documentation

- Cookbooks
    - [SFTP import](cookbooks/sftp_import.md)
    - [SFTP export](cookbooks/sftp_export.md)
    - [Remote cleanup](cookbooks/remote_cleanup.md)
- Reference
    - Tasks
        - [FileFetchTask](reference/tasks/file_fetch_task.md)
        - [ListContentTask](reference/tasks/list_content_task.md)
        - [RemoveFileTask](reference/tasks/remove_file_task.md)
- [Troubleshooting](troubleshooting.md)
    - [SFTP stale connection on long-running processes](troubleshooting.md#sftp-long-running-process-fails-with-got-packet-type--connection-closed-prematurely)
      (`Got packet type` / `Connection closed prematurely`)
- [CleverAge/ProcessBundle documentation](https://github.com/cleverage/process-bundle/blob/main/docs/index.md)
