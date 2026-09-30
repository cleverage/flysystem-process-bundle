ListContentTask
===============

Lists the content (files and directories) at the root of a Flysystem storage and iterates over it, outputting each
item as a `League\Flysystem\StorageAttributes` object. Items can be filtered with a regular expression on their path.

Task reference
--------------

* **Service**: `CleverAge\FlysystemProcessBundle\Task\ListContentTask`
* **Iterable task**

Accepted inputs
---------------

Input is ignored

Possible outputs
----------------

`League\Flysystem\StorageAttributes`: for each listed item, a `League\Flysystem\FileAttributes` (file) or a
`League\Flysystem\DirectoryAttributes` (directory) object. Useful properties are `path` (relative to the storage
root), `type` (`file` or `dir`), `lastModified`, and for files `fileSize` and `mimeType`
(see [Flysystem directory listings](https://flysystem.thephpleague.com/docs/usage/directory-listings/)).

Use [PropertyGetterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/property_getter_task.md)
with `property: path` to get the path, as expected by [FileFetchTask](file_fetch_task.md) or
[RemoveFileTask](remove_file_task.md).

When the storage is empty, or when no item matches `file_pattern`, no output is produced and the task is skipped.

Options
-------

| Code           | Type           | Required | Default | Description                                                                                                                                                                   |
|----------------|----------------|:--------:|---------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `filesystem`   | `string`       |  **X**   |         | Name of the Flysystem storage to list, as configured under `flysystem.storages` (see [configuration](../../index.md#configuration))                                           |
| `file_pattern` | `string\|null` |          | `null`  | Regular expression (see [preg_match](https://www.php.net/manual/en/function.preg-match.php)) tested on the path of each item. If `null`, every item (files and directories) is output |

Examples
--------

* List CSV files of a storage and output their path

```yaml
# Task configuration level
list:
  service: '@CleverAge\FlysystemProcessBundle\Task\ListContentTask'
  options:
    filesystem: 'remote.storage'
    file_pattern: '/\.csv$/'
  outputs: [get_path]
get_path:
  service: '@CleverAge\ProcessBundle\Task\PropertyGetterTask'
  options:
    property: 'path'
  outputs: [log]
```

* Keep only files (exclude directories) with a
  [FilterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/filter_task.md)

```yaml
# Task configuration level
list:
  service: '@CleverAge\FlysystemProcessBundle\Task\ListContentTask'
  options:
    filesystem: 'remote.storage'
  outputs: [only_files]
only_files:
  service: '@CleverAge\ProcessBundle\Task\FilterTask'
  options:
    match:
      type: 'file'
  outputs: [get_path]
```

Notes
-----

* The listing is not recursive: only the items located at the root of the storage are listed. To list a
  sub-directory, configure a dedicated storage whose root is this directory.
* Unlike [FileFetchTask](file_fetch_task.md) and [RemoveFileTask](remove_file_task.md), directories are not excluded:
  make `file_pattern` specific enough (e.g. on the extension) or filter on `type`.
* The storage is listed once, when the iteration starts; the listing is done again on the next execution of the task,
  once the previous iteration is over.
