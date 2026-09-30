SFTP import
===========

This recipe describes a typical import flow from a remote SFTP server: download every CSV file of an `incoming`
directory to a local storage, read each file line by line, then archive the remote file into an `archive` directory
and delete the local copy.

It uses the following storages:

```yaml
# config/packages/flysystem.yaml
parameters:
    local.storage.dir: '%kernel.project_dir%/var/storage/local'

flysystem:
    storages:
        local.storage:
            adapter: 'local'
            options:
                directory: '%local.storage.dir%'

        remote.storage.incoming:
            adapter: 'sftp'
            options:
                host: '%env(string:SFTP_HOST)%'
                username: '%env(string:SFTP_USERNAME)%'
                password: '%env(string:SFTP_PASSWORD)%'
                root: '%env(string:SFTP_ROOT)%/incoming'

        remote.storage.archive:
            adapter: 'sftp'
            options:
                host: '%env(string:SFTP_HOST)%'
                username: '%env(string:SFTP_USERNAME)%'
                password: '%env(string:SFTP_PASSWORD)%'
                root: '%env(string:SFTP_ROOT)%/archive'
```

```yaml
clever_age_process:
    configurations:
        app.sftp_import:
            description: 'Import the CSV files dropped on the SFTP server'
            tasks:
                fetch:
                    service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
                    options:
                        source_filesystem: 'remote.storage.incoming'
                        destination_filesystem: 'local.storage'
                        file_pattern: '/\.csv$/'
                    outputs: [read, archive] # "archive" is executed once "read" has read the whole file

                read:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask'
                    options:
                        base_path: '%local.storage.dir%'
                        delimiter: ';'
                    outputs: [import_line]

                import_line:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask' # Replace with your own transformation and writer tasks
                    options:
                        level: info
                        message: 'Imported line'

                archive:
                    service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
                    options:
                        source_filesystem: 'remote.storage.incoming'
                        destination_filesystem: 'remote.storage.archive'
                        remove_source: true
                    outputs: [cleanup_local]

                cleanup_local:
                    service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
                    options:
                        filesystem: 'local.storage'
```

How it works:
- The first [FileFetchTask](../reference/tasks/file_fetch_task.md) (`fetch`) is iterable: it copies the files matching
  `file_pattern` one at a time from `remote.storage.incoming` to `local.storage`, and outputs the path of each file
  (e.g. `products.csv`) relative to the storage root. Each file goes through all the following tasks before the next
  one is downloaded. As `ignore_missing` defaults to `true`, the process ends without error when there is no file to
  import; set it to `false` to make the process fail instead.
- The [InputCsvReaderTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/input_csv_reader_task.md)
  reads the local copy: its `base_path` option is the directory of `local.storage`, so that the relative path becomes a
  local path. It iterates over the lines of the file, sent to the
  [LoggerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/logger_task.md): replace it
  with your own tasks (e.g. a
  [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  and a writer).
- Outputs are executed depth-first, in order: once the whole file has been read, the second
  [FileFetchTask](../reference/tasks/file_fetch_task.md) (`archive`) receives the same path as input and moves the
  remote file from `remote.storage.incoming` to `remote.storage.archive` (`remove_source: true`). Using two storages
  with different roots on the same server allows moving files between remote directories.
- The [RemoveFileTask](../reference/tasks/remove_file_task.md) finally deletes the local copy, whose path is the
  output of `archive`.

Note that if a line cannot be imported with the default `stop` error strategy, the process stops before the file is
archived: it will be imported again on the next execution. If your import takes a long time, the SFTP server may
close the idle connection used by `fetch`: see [SFTP stale connection](../troubleshooting.md).
