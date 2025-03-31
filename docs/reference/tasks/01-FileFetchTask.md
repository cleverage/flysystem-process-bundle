FileFetchTask
========

Perform copy between 2 flysystems storage

Task reference
--------------

* **Service**: [`CleverAge\FlysystemProcessBundle\Task\FileFetchTask`](../src/Task/FileFetchTask.php)

Accepted inputs
---------------

The filename or filenames to copy from.

If the option `file_pattern` is not set the input is used as strict filename(s) to match.

If input is set but not corresponding as any file into `source_filesystem` task failed with UnableToReadFile exception.

If FileFetchTask is the first task of you process and you want to use input, don't forgive to set the `entry_point` task name at process level

Possible outputs
----------------

Filename of copied file.

Options
-------

| Code                     |   Type   | Required | Default | Description                                                                                                                                  |
|--------------------------|:--------:|:--------:|:-------:|----------------------------------------------------------------------------------------------------------------------------------------------|
| `source_filesystem`      | `string` |  **X**   |         | The source flysystem/storage.<br/>See config/packages/flysystem.yaml to see configured flysystem/storages.                                   |
| `destination_filesystem` | `string` |  **X**   |         | The source flysystem/storage.<br/>See config/packages/flysystem.yaml to see configured flysystem/storages.                                   |
| `file_pattern`           | `string` |          |  null   | The file_pattern used in preg_match to match into `source_filesystem` list of files. If not set try to use input as strict filename to match |
| `remove_source`          |  `bool`  |          |  false  | If true delete source file after copy                                                                                                        |
| `ignore_missing`         |  `bool`  |          |  true   | Ignore property accessor errors for this source                                                                                              |          


Examples
--------

* Simple fetch task configuration
    - See config/packages/flysystem.yaml to see configured flysystems/storages.
    - copy all .csv files from 'storage.source' to 'storage.destination'
    - remove .csv from 'storage.source' after copy
    - output will be filename of copied files
    - throw Exception when file(s) not found
```yaml
# Task configuration level
code:
  service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
  options:
    source_filesystem: 'storage.source'
    destination_filesystem: 'storage.destination'
    file_pattern: '/.csv$/'
    remove_source: true
    ignore_missing: false
```

* Simple fetch process configuration to cipy a specific file from --input option via <br> ```bin/console cleverage:process:execute my_custom_process --input=foobar.csv -vv```
    - See config/packages/flysystem.yaml to see configured flysystems/storages.
    - copy input file from 'storage.source' to 'storage.destination'
    - remove .csv from 'storage.source' after copy
    - output will be filename of copied file
```yaml
# Full process configuration to use input as filename with the following call
my_custom_process:
  entry_point: copy_from_input
  tasks:
    copy_from_input:
      service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
      options:
        source_filesystem: 'storage.source'
        destination_filesystem: 'storage.destination'
        remove_source: true
```
