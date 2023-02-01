To regenerate the files in this directory, run:

```sh
cd "$(git rev-parse --show-toplevel)" \
  && find tests/Resources/dafsa -type f -name '*.gperf' -print0 \
  | xargs -0 -L1 -P0 ./scripts/gperf-to-psl.php
```
