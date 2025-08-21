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
