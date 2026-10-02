RemoveFileTask
==============

Deletes files from a Flysystem storage: either every file matching a regular expression, or the file given as input.

Task reference
--------------

* **Service**: `CleverAge\FlysystemProcessBundle\Task\RemoveFileTask`

Accepted inputs
---------------

* When `file_pattern` is set, the input is ignored (but the deletion is run again each time the task is executed).
* Otherwise, `string|array<string>`: path, or list of paths, of the file(s) to delete, relative to the root of the
  `filesystem` storage. An empty input throws an `\UnexpectedValueException` (`No pattern neither input provided for
  the Task`). A `StorageAttributes` output of [ListContentTask](list_content_task.md) must be converted to its `path`
  first.

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
  and a deletion failure (`League\Flysystem\FilesystemException`) is logged with the `warning` level
  (`Failed to delete input file`), with the file path in the log context. A file given as input that does not exist
  (or is a directory) is not deleted and is logged with the `warning` level (`Input file not found`).
* The storage is resolved on each execution: an unknown storage name makes the task fail when it is executed.
* See the [Remote cleanup](../../cookbooks/remote_cleanup.md) cookbook.
