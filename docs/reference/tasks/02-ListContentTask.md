ListContentTask
========

List files of a flysystem storage

Task reference
--------------

* **Service**: [`CleverAge\FlysystemProcessBundle\Task\ListContentTask`](../src/Task/ListContentTask.php)

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

League\Flysystem\StorageAttributes

Options
-------

| Code           |    Type    | Required  |  Default  | Description                                                                                                |
|----------------|:----------:|:---------:|:---------:|------------------------------------------------------------------------------------------------------------|
| `filesystem`   |  `string`  |   **X**   |           | The source flysystem/storage.<br/>See config/packages/flysystem.yaml to see configured flysystem/storages. |
| `file_pattern` |  `string`  |           |           | The file_parttern used in preg_match to match into `filesystem`                                            |

Examples
--------
* Simple list task configuration from a filesystem
    - see config/packages/flysystem.yaml to see configured flysystems/storages.
    - list all .csv files from 'storage.source'
    - output will be League\Flysystem\StorageAttributes representation of copied files
```yaml
# Task configuration level
code:
  service: '@CleverAge\FlysystemProcessBundle\Task\ListContentTask'
  options:
    filesystem: 'storage.source'
    file_pattern: '/.csv$/'
```
