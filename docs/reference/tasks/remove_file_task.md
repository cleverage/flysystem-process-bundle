RemoveFileTask
==============

Deletes files from a Flysystem storage: either every file matching a regular expression, or the file given as input.

Task reference
--------------

* **Service**: `CleverAge\FlysystemProcessBundle\Task\RemoveFileTask`

Accepted inputs
---------------

* When `file_pattern` is set, the input is ignored (but the deletion is run again each time the task is executed).
* Otherwise, `string`: path of the file to delete, relative to the root of the `filesystem` storage. An empty input
  throws an `\UnexpectedValueException` (`No pattern neither input provided for the Task`). A list of paths is not
  supported: iterate over it first (e.g. with
  [InputIteratorTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/input_iterator_task.md)).
  A `StorageAttributes` output of [ListContentTask](list_content_task.md) must be converted to its `path` first.

Possible outputs
----------------

No output is set.

Options
-------

| Code           | Type           | Required | Default | Description                                                                                                                                                                                                    |
|----------------|----------------|:--------:|---------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `filesystem`   | `string`       |  **X**   |         | Name of the Flysystem storage to delete files from, as configured under `flysystem.storages` (see [configuration](../../index.md#configuration))                                                             |
| `file_pattern` | `string\|null` |          | `null`  | Regular expression (see [preg_match](https://www.php.net/manual/en/function.preg-match.php)) tested on the path of each file at the root of `filesystem`: every matching file is deleted. If `null` (or empty), the file path is taken from the input |

Examples
--------

* Delete the file given as input
  - `bin/console cleverage:process:execute my_process --input=foobar.csv`

```yaml
# Process configuration level
my_process:
  entry_point: remove_from_input
  tasks:
    remove_from_input:
      service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
      options:
        filesystem: 'remote.storage'
```

* Delete every CSV file of a storage

```yaml
# Task configuration level
purge:
  service: '@CleverAge\FlysystemProcessBundle\Task\RemoveFileTask'
  options:
    filesystem: 'remote.storage'
    file_pattern: '/\.csv$/'
```

Notes
-----

* `file_pattern` is only tested on the files located at the root of the storage (the listing is not recursive and
  directories are ignored).
* Deletion errors do not stop the process: each deleted file is logged with the `info` level (`Deleted input file`),
  and a deletion failure (`League\Flysystem\FilesystemException`) is logged with the `warning` level, with the file
  path in the log context. Deleting a file that does not exist is not an error for most adapters (e.g. `local`,
  `sftp`): it is logged as deleted.
* The storage is resolved on each execution: an unknown storage name makes the task fail when it is executed.
* See the [Remote cleanup](../../cookbooks/remote_cleanup.md) cookbook.
