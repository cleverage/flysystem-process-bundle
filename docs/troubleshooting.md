# Troubleshooting

## SFTP: long-running process fails with "Got packet type" / "Connection closed prematurely"

### Symptom

A long-running process that uses an SFTP storage (e.g. `FileFetchTask`, `ListContentTask`,
`RemoveFileTask`) fails on a **later** task with one of:

```
Expected NET_SFTP_HANDLE or NET_SFTP_STATUS. Got packet type: <n>
```
```
Connection closed prematurely
```

Typically the first SFTP task succeeds (a file is fetched/listed), then the process spends a long
time doing other work (large CSV import, thousands of API calls, …), and a subsequent SFTP task —
often the archiving step, or the next iteration of an iterable task that re-lists the source — blows
up with the error above.

### Cause

Flysystem's SFTP adapter (`league/flysystem-sftp-v3`) **caches** its underlying phpseclib
connection through `SftpConnectionProvider`, and every task using that storage reuses it. While the
process is busy, the SFTP server reaches its idle/session timeout and drops the session. When the
next SFTP task runs, the provider hands back the **stale** cached connection.

By default `SftpConnectionProvider` only guards the cached connection with
`SimpleConnectivityChecker`, which calls `SFTP::isConnected()` — a **local bit flag**, it never
probes the server. So the dead connection passes the check and the next operation fails.

Depending on how the server tore the session down you get a different message (same root cause):

- the server closed the **transport** (TCP/`SSH_MSG_DISCONNECT`, e.g. OpenSSH) → phpseclib hits EOF
  → `Connection closed prematurely`;
- the server left the **SFTP channel** de-synchronized — a stray/late packet on an otherwise open
  transport (common with enterprise/appliance SFTP servers) → the next `OPEN`/`OPENDIR` reads that
  packet → `Expected NET_SFTP_HANDLE or NET_SFTP_STATUS. Got packet type`.

### Solution

Give the storage a **connectivity checker that performs a real SFTP round-trip**, so a stale or
de-synchronized connection is detected and `SftpConnectionProvider` transparently reconnects before
the operation.

> Note: enabling `SimpleConnectivityChecker` with `usePing: true` is **not enough** for the
> "Got packet type" case. `SFTP::ping()` works at the SSH **transport** level (it opens a separate
> keep-alive channel); it cannot detect a de-synchronized SFTP channel and returns `true` while the
> SFTP stream is broken. Probe at the SFTP protocol level instead.

Create a checker that does an SFTP operation (here `stat('.')`):

```php
<?php

namespace App\Flysystem;

use League\Flysystem\PhpseclibV3\ConnectivityChecker;
use phpseclib3\Net\SFTP;

final class SftpConnectivityChecker implements ConnectivityChecker
{
    public function isConnected(SFTP $connection): bool
    {
        if (!$connection->isConnected()) {
            return false;
        }

        try {
            // A real SFTP round-trip: on a stale/de-synchronized connection this
            // throws (or reads the stray packet), so we report "not connected" and
            // SftpConnectionProvider reconnects before the actual operation runs.
            $connection->stat('.');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
```

Wire it on the storage with the `connectivityChecker` option (provided by `flysystem-bundle`),
passing the service id:

```yaml
# config/packages/flysystem.yaml
flysystem:
  storages:
    remote.storage:
      adapter: 'sftp'
      options:
        host: '%env(string:SFTP_HOST)%'
        port: '%env(int:SFTP_PORT)%'
        username: '%env(string:SFTP_USERNAME)%'
        password: '%env(string:SFTP_PASSWORD)%'
        root: '%env(string:SFTP_ROOT)%'
        connectivityChecker: App\Flysystem\SftpConnectivityChecker
```

The class is auto-registered as a service by the default `App\` service definition; no extra
service configuration is required.

### Notes

- The check adds one lightweight SFTP round-trip per operation that reuses a cached connection.
- Alternatively you can avoid the shared/cached connection altogether (e.g. disconnect the storage
  between long phases), but a connectivity checker keeps the configuration declarative.

See [cleverage/flysystem-process-bundle#24](https://github.com/cleverage/flysystem-process-bundle/issues/24).
