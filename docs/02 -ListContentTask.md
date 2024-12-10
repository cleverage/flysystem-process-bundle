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

| Code | Type     |     Required      | Default | Description                                                    |
| ---- |----------|:-----------------:|-|----------------------------------------------------------------|
| `filesystem` | `string` |       **X**       || The source flysystem/storage.<br/>See config/packages/flysystem.yaml to see configured flysystem/storages.                                 |
| `file_pattern` | `string` ||| he file_parttern used in preg_match to match into `filesystem` |

Examples
--------

```yaml
# Task configuration level
code:
  service: '@CleverAge\FlysystemProcessBundle\Task\ListContentTask'
  description: >
    List .csv files from storage.source.
    See config/packages/flysystem.yaml to see configured flysystem/storages.
  outputs: get_file_path
  options:
    filesystem: 'storage.source'
    file_pattern: '/.csv$/'
```
