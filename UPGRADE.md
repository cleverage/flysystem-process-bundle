Upgrade Guide
=============

## v3.3

Unless stated otherwise, the deprecations below trigger a notice (`Since cleverage/flysystem-process-bundle 3.3: ...`)
and will be removed in 4.0.

### Dependencies

- `cleverage/process-bundle` `^5.4` is required: follow its
  [upgrade guide](https://github.com/cleverage/process-bundle/blob/main/UPGRADE.md#v54).
- `league/flysystem-bundle` `^3.2` is required (was `^3.0`).

### Configuration key

The configuration key of the bundle is `cleverage_flysystem_process`: `clever_age_flysystem_process` is no longer
recognized. The bundle has no configuration, so remove the key: configuring one under `cleverage_flysystem_process` is
deprecated (the keys were ignored), it will be rejected in 4.0.

### Task codes and service ids

Reference the tasks by their code instead of their service:

```yaml
tasks:
    fetch:
        # Before (deprecated)
        service: '@CleverAge\FlysystemProcessBundle\Task\FileFetchTask'
        # After
        task: flysystem_file_fetch
```

| Former service id (deprecated alias)            | Class             | Code                     |
|-------------------------------------------------|-------------------|--------------------------|
| `cleverage_flysystem_process.task.file_fetch`   | `FileFetchTask`   | `flysystem_file_fetch`   |
| `cleverage_flysystem_process.task.list_content` | `ListContentTask` | `flysystem_list_content` |
| `cleverage_flysystem_process.task.remove_file`  | `RemoveFileTask`  | `flysystem_remove_file`  |

The service id of the tasks is their class name. Fetching them from the service container (`$container->get()`) is
deprecated: they will be private in 4.0.

### Final classes

`FileFetchTask`, `ListContentTask`, `RemoveFileTask` and `CleverAgeFlysystemProcessBundle` are marked `@final`, they
will be `final` in 4.0 (extending them triggers a deprecation with the Symfony `DebugClassLoader`).
`CleverAgeFlysystemProcessExtension` is `@internal`.

### Exceptions

The bundle throws exceptions implementing `ProcessExceptionInterface` of the process bundle, extending the SPL
exceptions thrown before: existing `catch` blocks keep working.
