# DevTools setup

## Install PHP devtools

```sh
cd "$(git rev-parse --show-toplevel)"
phive install
```

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
