# DevTools setup

We use the following tools to generate test cases and/or compare our implementation
against well-established ones (gecko, chromium and libpsl).

## Install PHP devtools

```sh
cd "$(git rev-parse --show-toplevel)"
phive install
```

If you just want to run the test suite and benchmarks, you can stop here.
Otherwise you should read the following chapters.

## Install the `psl` binary

```sh
sudo apt install psl
```

## Download Mozilla's `make_dafsa.py` utility

```sh
cd "$(git rev-parse --show-toplevel)"
pushd tools
wget -O incremental_dafsa.py 'https://hg.mozilla.org/mozilla-central/raw-file/tip/xpcom/ds/tools/incremental_dafsa.py'
wget -O make_dafsa.py 'https://hg.mozilla.org/mozilla-central/raw-file/tip/xpcom/ds/tools/make_dafsa.py'
wget -O test_dafsa.py 'https://hg.mozilla.org/mozilla-central/raw-file/tip/xpcom/ds/test/test_dafsa.py'
popd
```
