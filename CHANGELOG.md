Latest
------

### Fixes
* [#75](https://github.com/cleverage/flysystem-process-bundle/issues/75) `FileFetchTask` (`flysystem_file_fetch`): an unknown storage fails at initialization with an explicit message (`Unknown storage "…" (option "…")`, with the known storages) instead of `Service "…" not found`, When the task is executed after a failed initialization (process-bundle v5 goes on), it fails with an explicit message instead of `Typed property … must not be accessed before initialization`, and no longer reuses the storages of a previous initialization. Update documentation, add tests.

v3.3
------

### Changes
* Require `cleverage/process-bundle` `^5.4`.
* Please follow [UPGRADE.md v3.3](UPGRADE.md#v33)
* [#73](https://github.com/cleverage/flysystem-process-bundle/issues/73) Require `league/flysystem-bundle` `^3.2` instead of `^3.0` (3.0 and 3.1 trigger return type deprecations, the tests already required 3.2), the dev requirement is removed.
* [#69](https://github.com/cleverage/flysystem-process-bundle/issues/69) Only `RemoveFileTask` has the `monolog.logger` tag (channel `cleverage_process_task`): `FileFetchTask` and `ListContentTask` have no logger, the tag had no effect on them. Add tests.
* [#67](https://github.com/cleverage/flysystem-process-bundle/issues/67) The configuration key of the bundle (its extension alias) is `cleverage_flysystem_process`, instead of `clever_age_flysystem_process` (no option could be configured there). Configuring a key under `cleverage_flysystem_process` is deprecated: the bundle has no configuration and the keys were ignored without notice (new internal `Configuration`, empty; notice when the container is compiled). They will be rejected in 4.0. Require `symfony/deprecation-contracts`, used directly. Update documentation, add tests.
* [#65](https://github.com/cleverage/flysystem-process-bundle/issues/65) The bundle throws exceptions implementing `ProcessExceptionInterface` of the process bundle instead of raw SPL exceptions (as [cleverage/process-bundle#317](https://github.com/cleverage/process-bundle/issues/317)): new `Exception\UnexpectedValueException`, extending the SPL exceptions thrown before (same messages, the existing `catch` keep working). Add tests.
* [#63](https://github.com/cleverage/flysystem-process-bundle/issues/63) Composer: require PHP `^8.2` instead of `>=8.2` (PHP 9 is no longer accepted before being tested), remove the authorization of the `symfony/flex` plugin, which is not required (the CI installs it globally), as [cleverage/process-bundle#319](https://github.com/cleverage/process-bundle/issues/319).
* [#58](https://github.com/cleverage/flysystem-process-bundle/issues/58) Define the public API (Symfony BC promise, as [cleverage/process-bundle#279](https://github.com/cleverage/process-bundle/issues/279)): `FileFetchTask`, `ListContentTask`, `RemoveFileTask` and `CleverAgeFlysystemProcessBundle` are marked `@final since 3.3`, they will be `final` in 4.0: extending them triggers a deprecation with the Symfony `DebugClassLoader`. `CleverAgeFlysystemProcessExtension` is marked `@internal`.
* [#57](https://github.com/cleverage/flysystem-process-bundle/issues/57) `CleverAgeFlysystemProcessExtension::findServices()` loads the service definition files with a glob pattern instead of a `Finder` (same files, as [cleverage/process-bundle#271](https://github.com/cleverage/process-bundle/issues/271)), `symfony/finder` is no longer required. Add tests.
* [#55](https://github.com/cleverage/flysystem-process-bundle/issues/55) Use the PHP 8.3 to 8.5 functions through the Symfony polyfills ([cleverage/process-bundle#299](https://github.com/cleverage/process-bundle/issues/299)): require `symfony/polyfill-php83`, `symfony/polyfill-php84` and `symfony/polyfill-php85`. Configure Rector for the minimum PHP version (`withPhpVersion(PHP_82)` instead of `PHP_85`, `withPhpSets()` read from `composer.json` instead of the `php81` leftover, which also enables the polyfill rules; `UP_TO_PHP_82` set removed): no change proposed on the current code. Document the PHP version policy in CONTRIBUTING.
* [#51](https://github.com/cleverage/flysystem-process-bundle/issues/51) Give a code to the tasks ([cleverage/process-bundle#91](https://github.com/cleverage/process-bundle/issues/91)): `flysystem_file_fetch`, `flysystem_list_content` and `flysystem_remove_file` (`task: flysystem_file_fetch`, with `cleverage/process-bundle` 5.4 or later, referencing them by `service` being deprecated there). The tasks have their class name as service id; fetching them directly from the service container is deprecated (`container.private`), they will be private in 4.0. The former ids `cleverage_flysystem_process.task.file_fetch` / `cleverage_flysystem_process.task.list_content` / `cleverage_flysystem_process.task.remove_file` are deprecated aliases, removed in 4.0. Update documentation, add tests.
* [#49](https://github.com/cleverage/flysystem-process-bundle/issues/49) CI: test the lowest versions allowed by `composer.json` (`--prefer-lowest`, PHP 8.2). Require `psr/log` `^2|^3`: `cleverage/process-bundle` 5.x uses the typed `LoggerInterface` signatures (fatal error with `psr/log` 1).
* [#47](https://github.com/cleverage/flysystem-process-bundle/issues/47) Add a `.gitattributes`: the development files (`.docker/`, `.github/`, `tests/`, quality tools and PHPUnit configurations, `Makefile`) are no longer part of the archive installed by Composer (`dist`).
* [#45](https://github.com/cleverage/flysystem-process-bundle/issues/45) Require `league/flysystem` and `psr/log`, used directly by the tasks but only installed through `league/flysystem-bundle` and `cleverage/process-bundle`; add `league/flysystem-local` (used by the tests) to the dev requirements.

v3.2
------

### Changes
* [#33](https://github.com/cleverage/flysystem-process-bundle/issues/33) Add missing tests: FileFetchTask, ListContentTask and RemoveFileTask on local storages, bundle and DI extension.

### Fixes
* [#37](https://github.com/cleverage/flysystem-process-bundle/issues/37) Fix FileFetchTask: list the source storage once per input (it was listed again before each file). Update documentation, add tests.
* [#38](https://github.com/cleverage/flysystem-process-bundle/issues/38) Fix FileFetchTask: process each input (with `file_pattern`, only the first input copied the files; a path received twice was skipped). Update documentation, add tests.
* [#39](https://github.com/cleverage/flysystem-process-bundle/issues/39) Fix FileFetchTask: apply `ignore_missing` to the files given as input (a missing file threw `UnableToReadFile`). Update documentation, add tests.
* [#40](https://github.com/cleverage/flysystem-process-bundle/issues/40) Fix FileFetchTask and RemoveFileTask: a file named `0` is no longer handled as no file. Add tests.
* [#41](https://github.com/cleverage/flysystem-process-bundle/issues/41) Fix RemoveFileTask: a missing file is logged as not found (it was logged as deleted), accept a list of paths, fix the deletion failure message. Update documentation, add tests.

v3.1
------

### Changes
* [#30](https://github.com/cleverage/flysystem-process-bundle/issues/30) Update quality stack: use Rector `withComposerBased()` sets (removed `SYMFONY_64` / `PHPUNIT_100` sets), declare used Symfony packages and PHPUnit range in composer.json, apply quality tools fixes
* [#32](https://github.com/cleverage/flysystem-process-bundle/issues/32) Add missing documentations: harmonize and complete reference pages for every Task (renamed to snake_case), add SFTP import, SFTP export and remote cleanup cookbooks. Harmonize and fix existing documentation.

### Fixes
* [#35](https://github.com/cleverage/flysystem-process-bundle/issues/35) FileFetchTask no longer ignores write failures on the destination storage: the error is thrown (the error strategy applies) and, with `remove_source`, the source file is no longer deleted

v3.0
------

### Changes
* [#22](https://github.com/cleverage/flysystem-process-bundle/issues/22) Add support for PHP 8.5 Update phpunit/phpunit to version >10.0 Bump version to cleverage/process-bundle ^5.0

### BC breaks
* [#22](https://github.com/cleverage/flysystem-process-bundle/issues/22) Remove support for PHP 8.1

v2.3
------

### Changes

* [#20](https://github.com/cleverage/flysystem-process-bundle/issues/20) Upgrade to Symfony 7.3 & PHP 8.4

v2.2
------

### Changes

* [#18](https://github.com/cleverage/flysystem-process-bundle/issues/18) Add ignore_missing option on FileFetchTask that allow throwing Exception when file(s) not found.

v2.1.1
------

### Fixes

* [#16](https://github.com/cleverage/flysystem-process-bundle/issues/16) Add missing shared: false on tasks

v2.1
------

### Changes

* [#14](https://github.com/cleverage/flysystem-process-bundle/issues/14) Update RemoveFileTask to work with file_pattern or input.

v2.0
------

## BC breaks

* [#5](https://github.com/cleverage/flysystem-process-bundle/issues/5) Replace "oneup/flysystem-bundle": ">1.0,<4.0" by "league/flysystem-bundle": "^3.0"
* [#5](https://github.com/cleverage/flysystem-process-bundle/issues/5) Update Tasks for "league/flysystem-bundle": "^3.0"
* [#6](https://github.com/cleverage/flysystem-process-bundle/issues/6) Update services according to Symfony best practices. Services should not use autowiring or autoconfiguration. Instead, all services should be defined explicitly.
  Services must be prefixed with the bundle alias instead of using fully qualified class names => `cleverage_flysystem_process`

### Changes

* [#3](https://github.com/cleverage/flysystem-process-bundle/issues/3) Add Makefile & .docker for local standalone usage
* [#3](https://github.com/cleverage/flysystem-process-bundle/issues/3) Add rector, phpstan & php-cs-fixer configurations & apply it
* [#4](https://github.com/cleverage/flysystem-process-bundle/issues/4) Remove `sidus/base-bundle` dependency

### Fixes

v1.0.1
------

### Fixes

* Fixed dependencies after removing sidus/base-bundle from the base process bundle

v1.0.0
------

* Initial release
