RemoveFileTask
========

Remove a file from a flysystem storage

Task reference
--------------

* **Service**: [`CleverAge\FlysystemProcessBundle\Task\RemoveFileTask`](../src/Task/RemoveFileTask.php)

Accepted inputs
---------------

The filename of the file to remove on `filesystem`.

When filename is deleted add a info log.

If filename not found or cannot be deleted on `filesystem` add a warning log.

Possible outputs
----------------

None

Options
-------

| Code         |    Type    |  Required  |  Default  | Description                                                                                                 |
|--------------|:----------:|:----------:|:---------:|-------------------------------------------------------------------------------------------------------------|
| `filesystem` |  `string`  |   **X**    |           | The source flysystem/storage. <br/>See config/packages/flysystem.yaml to see configured flysystem/storages. |

Examples
--------
* Simple process to remove a file on 'storage.source' via <br>```bin/console cleverage:process:execute my_custom_process --input=foobar.csv -vv``` 
    - see config/packages/flysystem.yaml to see configured flysystems/storages.
    - remove file with name passed as input 
```yaml
# 
my_custom_process:
  entry_point: remove_from_input
  tasks:
    remove_from_input:
      service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
      options:
        filesystem: 'storage.source'
```
