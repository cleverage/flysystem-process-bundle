FileFetchTask
=============

Copies (or moves) files from one Flysystem storage to another, and iterates over the copied files, outputting each
file path. Files are either selected with a regular expression on the source storage, or given as input.

Typical use cases: download files from a remote SFTP/FTP/S3 storage to a local storage before reading them, upload
generated files to a remote storage, or archive processed files into another storage.

Task reference
--------------

* **Service**: `CleverAge\FlysystemProcessBundle\Task\FileFetchTask`
* **Iterable task**

Accepted inputs
---------------

* When `file_pattern` is set, the input is ignored.
* Otherwise, `string|array<string>`: path, or list of paths, of the file(s) to copy, relative to the root of the
  `source_filesystem` storage. An empty input throws an `\UnexpectedValueException`
  (`No pattern neither input provided for the Task`).

If the task is the first task of the process and takes its files from the input, set the process `entry_point` to
this task (e.g. `bin/console cleverage:process:execute my_process --input=foobar.csv`).

Possible outputs
----------------

`string`: for each copied file, its path relative to the storage root (the same path is used in the
`source_filesystem` and in the `destination_filesystem`).

When no file matches `file_pattern`, or when no file given as input exists (and `ignore_missing` is `true`), no
output is produced and the task is skipped.

Options
-------

| Code                     | Type           | Required | Default | Description                                                                                                                                                                                                                                                       |
|--------------------------|----------------|:--------:|---------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `source_filesystem`      | `string`       |  **X**   |         | Name of the Flysystem storage to read files from, as configured under `flysystem.storages` (see [configuration](../../index.md#configuration))                                                                                                                    |
| `destination_filesystem` | `string`       |  **X**   |         | Name of the Flysystem storage to write files to, as configured under `flysystem.storages`                                                                                                                                                                         |
| `file_pattern`           | `string\|null` |          | `null`  | Regular expression (see [preg_match](https://www.php.net/manual/en/function.preg-match.php)) tested on the path of each file at the root of `source_filesystem`. If `null` (or empty), the file path(s) are taken from the input                                  |
| `remove_source`          | `bool`         |          | `false` | Delete the file from `source_filesystem` after the copy (move instead of copy)                                                                                                                                                                                    |
| `ignore_missing`         | `bool`         |          | `true`  | If `false`, throw an `\UnexpectedValueException` when no file matches `file_pattern` (`File(s) not found in source filesystem`) or when a file given as input does not exist (`File <path> not found in source filesystem`). If `true`, missing files are skipped |

Examples
--------

* Move all CSV files from a remote storage to a local one
  - each file is copied then deleted from `remote.storage`
  - the output is the path of each copied file, e.g. `products.csv`
  - an exception is thrown if no CSV file is found

```yaml
# Task configuration level
fetch:
  service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
  options:
    source_filesystem: 'remote.storage'
    destination_filesystem: 'local.storage'
    file_pattern: '/\.csv$/'
    remove_source: true
    ignore_missing: false
  outputs: [read]
```

* Copy a file given as input, with contextualized storages
  - `bin/console cleverage:process:execute my_process --input=foobar.csv -c source:remote.storage -c destination:local.storage`

```yaml
# Process configuration level
my_process:
  entry_point: copy_from_input
  tasks:
    copy_from_input:
      service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
      options:
        source_filesystem: '{{ source }}'
        destination_filesystem: '{{ destination }}'
```

* Archive a processed file into another storage
  - the input is the path of a file previously fetched from `remote.storage.incoming`

```yaml
# Task configuration level
archive:
  service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
  options:
    source_filesystem: 'remote.storage.incoming'
    destination_filesystem: 'remote.storage.archive'
    remove_source: true
```

Notes
-----

* Both storages are resolved when the task is initialized, at the start of the process: an unknown storage name makes
  the process fail before any task is executed.
* `file_pattern` is only tested on the files located at the root of `source_filesystem` (the listing is not
  recursive and directories are ignored). The pattern is tested on the path relative to the storage root: to target
  a sub-directory, configure a dedicated storage whose root is this directory.
* With `file_pattern`, the source storage is listed once per input, when the iteration starts: files added to the
  source during the iteration are copied by the next execution of the task. With an SFTP storage and long-running
  downstream tasks, see [SFTP stale connection](../../troubleshooting.md).
* Each input is processed: the files are copied for each input received by the task (e.g. after an iterable task),
  even if they have already been copied during this process execution. A path given several times in the same input
  is copied once.
* The file is written to `destination_filesystem` with the same path, overwriting any existing file. The existence of
  the files given as input is checked in `source_filesystem` (see `ignore_missing`).
* A failure while writing to `destination_filesystem` throws a `League\Flysystem\FilesystemException` (e.g.
  `UnableToWriteFile`): the task's `error_strategy` applies, and with `remove_source: true` the source file is kept.
* To read a copied file with a core task (e.g.
  [InputCsvReaderTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/input_csv_reader_task.md)),
  prefix the output with the directory of the local destination storage (e.g. with the `base_path` option).
* See the [SFTP import](../../cookbooks/sftp_import.md) and [SFTP export](../../cookbooks/sftp_export.md) cookbooks.
