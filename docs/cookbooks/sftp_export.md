SFTP export
===========

This recipe describes a typical export flow to a remote SFTP server: write a CSV file locally, then upload it to the
remote server and remove the local file.

It uses the `local.storage` storage (whose directory is the `local.storage.dir` parameter) described in the
[SFTP import](sftp_import.md) cookbook, and a `remote.storage.outgoing` SFTP storage.

```yaml
clever_age_process:
    configurations:
        app.sftp_export:
            description: 'Export the catalog to the SFTP server'
            tasks:
                data:
                    service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask' # Replace with your own reader
                    options:
                        output:
                            - { sku: 'SKU-001', name: 'Product 1', price: '12.50' }
                            - { sku: 'SKU-002', name: 'Product 2', price: '8.90' }
                    outputs: [write]

                write:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask'
                    options:
                        file_path: '%local.storage.dir%/catalog_{date_time}.csv'
                        headers: [sku, name, price]
                    outputs: [relative_path]

                relative_path:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            callback:
                                callback: basename
                    outputs: [upload]

                upload:
                    service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
                    options:
                        source_filesystem: 'local.storage'
                        destination_filesystem: 'remote.storage.outgoing'
                        remove_source: true
                    outputs: [log_upload]

                log_upload:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
                    options:
                        level: info
                        message: 'File uploaded'
```

How it works:
- The [ConstantIterableOutputTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/constant_iterable_output_task.md)
  stands for your own reader (database, API...): it outputs the lines one at a time.
- The [CsvWriterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/csv_writer_task.md)
  is blocking: it writes every line in a file located in the `local.storage` directory, then outputs the absolute path
  of this file once all lines have been written.
- [FileFetchTask](../reference/tasks/file_fetch_task.md) expects a path relative to the root of its source storage:
  the [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  keeps only the file name with the
  [callback](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/callback_transformer.md)
  transformer (`basename`).
- The [FileFetchTask](../reference/tasks/file_fetch_task.md) copies the file from `local.storage` to
  `remote.storage.outgoing`, then deletes the local file (`remove_source: true`), and outputs the file name to the
  [LoggerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/logger_task.md).

The same flow can target any Flysystem adapter (FTP, Amazon S3, Azure...): only the `remote.storage.outgoing` storage
configuration changes.
