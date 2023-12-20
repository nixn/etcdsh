# nixn/etcdsh

A [PHP][php] [session handler][php-sh], which stores the session data in an [ETCD cluster][etcd]
and enables automatic cleaning of sessions with ETCD's [lease system][etcd-lease].

It uses the PHP session lifetime ([`session.gc_maxlifetime`][gctime]) for the data,
which enables automatic cleaning of the session data after session timeout -
with no interaction from the application side (like CRON jobs or the like).

Due to the nature of ETCD it is possible to have multiple web servers, which *all* access the *same*
session data, so possibly session stickiness could be rethought…

[php]: https://www.php.net/
[php-sh]: https://www.php.net/manual/class.sessionhandlerinterface.php
[etcd]: https://github.com/coreos/etcd/
[etcd-lease]: https://etcd.io/docs/v3.5/tutorials/how-to-create-lease/
[gctime]: https://www.php.net/manual/session.configuration.php#ini.session.gc-maxlifetime

## Setup

### Installation
etcdsh uses the [Aternos gRPC ETCD client][aternos-etcd], which needs the extension `php-grpc`.

[aternos-etcd]: https://github.com/aternosorg/php-etcd

```sh
apt install php-grpc
composer require nixn/etcdsh
```

### Usage
```php
use nix\etcdsh\EtcdSessionHandler;
use Aternos\Etcd\Client as EtcdClient;

session_name('MY_SESSION_NAME'); // recommended to set, default is PHPSESSID
session_set_save_handler(new EtcdSessionHandler(new EtcdClient('localhost')));
session_save_path('sessions/'); // used as prefix in ETCD ("<name>/<id>:<lease-id>" appended)
ini_set('session.gc_probability', 0); // GC not needed with EtcdSessionHandler
ini_set('session.gc_maxlifetime', 1440); // set lifetime to your liking
session_start();
```

NOTE: The package name is `nix\etcdsh`, not ~~`nixn\etcdsh`~~.

## License
Copyright © 2023 nix <https://keybase.io/nixn>

Distributed under the MIT license, available in the file [LICENSE](LICENSE).

## Donations
If you like etcdsh, please consider dropping some bitcoins to `1nixn9rd4ns8h5mQX3NmUtxwffNZsbDTP`.
