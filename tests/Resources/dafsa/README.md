To regenerate the files in the `chromium` directory, run:

```sh
cd "$(git rev-parse --show-toplevel)" \
  && find tests/Resources/dafsa/chromium -type f -name '*.gperf' -print0 \
  | xargs -0 -L1 -P0 ./scripts/gperf-to-psl.php
```

To regenerate the files in the `gecko` directory, run:

```sh
cd "$(git rev-parse --show-toplevel)" \
  && scripts/generate-dafsa-tests.py
```
