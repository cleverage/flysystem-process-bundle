Remote cleanup
==============

This recipe describes how to purge files from a remote storage, e.g. the archives of the [SFTP import](sftp_import.md)
cookbook, while logging each deleted file.

```yaml
clever_age_process:
    configurations:
        app.remote_cleanup:
            description: 'Purge the archived CSV files of a given year'
            help: "bin/console cleverage:process:execute app.remote_cleanup -c year:2025"
            tasks:
                list:
                    service: '@CleverAge\FlysystemProcessBundle\Task\ListContentTask'
                    options:
                        filesystem: 'remote.storage.archive'
                        file_pattern: '/^catalog_{{ year }}.*\.csv$/'
                    outputs: [only_files]

                only_files:
                    service: '@CleverAge\ProcessBundle\Task\FilterTask'
                    options:
                        match:
                            type: 'file'
                    outputs: [get_path]

                get_path:
                    service: '@CleverAge\ProcessBundle\Task\PropertyGetterTask'
                    options:
                        property: 'path'
                    outputs: [remove]

                remove:
                    service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
                    options:
                        filesystem: 'remote.storage.archive'
```

How it works:
- The [ListContentTask](../reference/tasks/list_content_task.md) iterates over the items at the root of
  `remote.storage.archive` whose path matches `file_pattern`; the `{{ year }}` placeholder is replaced by the `year`
  context value given on the command line. Each item is a `League\Flysystem\StorageAttributes` object.
- `ListContentTask` also lists directories: the
  [FilterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/filter_task.md) keeps only the
  items whose `type` is `file`.
- The [PropertyGetterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/property_getter_task.md)
  extracts the `path` of the file, relative to the storage root.
- The [RemoveFileTask](../reference/tasks/remove_file_task.md) deletes the file given as input, and logs it. A deletion
  failure is logged as a warning and does not stop the process.

When you do not need to act on each file (log, filter on other attributes, etc.), a single
[RemoveFileTask](../reference/tasks/remove_file_task.md) with a `file_pattern` option does the same (it only
considers files):

```yaml
clever_age_process:
    configurations:
        app.remote_cleanup_pattern:
            tasks:
                remove:
                    service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
                    options:
                        filesystem: 'remote.storage.archive'
                        file_pattern: '/^catalog_{{ year }}.*\.csv$/'
```
