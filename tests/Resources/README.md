The JSON files in this directory are generated from the upstream
[tests.txt](https://github.com/publicsuffix/list/blob/master/tests/tests.txt)
file.

To regenerate them, run:
```sh
composer run update-psl-tests
```

The `psl` binary must be installed on your system.
On a Debian based OS, you can run:
```sh
apt install psl
```
