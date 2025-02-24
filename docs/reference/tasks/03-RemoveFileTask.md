RemoveFileTask
========

Remove a file from a flysystem storage

Task reference
--------------

* **Service**: [`CleverAge\FlysystemProcessBundle\Task\RemoveFileTask`](../src/Task/RemoveFileTask.php)

Accepted inputs
---------------

The filename of the file to remove on `filesystem`.

If the option `file_pattern` is not set the input is used as strict filename(s) to match.

When filename is deleted add a info log.

If filename not found or cannot be deleted on `filesystem` add a warning log.

Possible outputs
----------------

None

Options
-------

| Code           |    Type    | Required |  Default  | Description                                                                                                                                  |
|----------------|:----------:|:--------:|:---------:|----------------------------------------------------------------------------------------------------------------------------------------------|
| `filesystem`   |  `string`  |  **X**   |           | The source flysystem/storage. <br/>See config/packages/flysystem.yaml to see configured flysystem/storages.                                  |
| `file_pattern` |  `string`  |          |   null    | The file_pattern used in preg_match to match into `source_filesystem` list of files. If not set try to use input as strict filename to match |


Examples
--------
* Simple process to remove a file on 'storage.source' via <br>```bin/console cleverage:process:execute input_process --input=foobar.csv -vv``` 
    - see config/packages/flysystem.yaml to see configured flysystems/storages.
    - remove file with name passed as input 
```yaml
# 
input_process:
  entry_point: remove_from_input
  tasks:
    remove_from_input:
      service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
      options:
        filesystem: 'storage.source'
```

* Simple process to remove a file on 'storage.source' via <br>```bin/console cleverage:process:execute pattern_process -vv```
  - see config/packages/flysystem.yaml to see configured flysystems/storages.
  - remove files filtered by file_pattern
```yaml
# 
pattern_process:
  tasks:
    remove_from_file_pattern:
      service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
      options:
        filesystem: 'storage.source'
        file_pattern: '/.csv$/'
```
