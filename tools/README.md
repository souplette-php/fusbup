
```sh
cd "$(git rev-parse --show-toplevel)"
pushd tools
wget -O incremental_dafsa.py 'https://hg.mozilla.org/mozilla-central/raw-file/tip/xpcom/ds/tools/incremental_dafsa.py'
wget -O make_dafsa.py 'https://hg.mozilla.org/mozilla-central/raw-file/tip/xpcom/ds/tools/make_dafsa.py'
popd
```
