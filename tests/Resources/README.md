
The `tests.txt` file was retrieved from
https://github.com/publicsuffix/list/blob/master/tests/tests.txt.

`unregisterable.txt` was generated with:
```sh
grep -v -e '//' -e '^$' tests.txt \
  | cut -d' ' -f1 \
  | xargs -L1 psl --print-unreg-domain \
  | sed -e 's/: / /' \
  > unregisterable.txt
```

`registerable.txt` was generated with:
```sh
grep -v -e '//' -e '^$' tests.txt \
  | cut -d' ' -f1 \
  | xargs -L1 psl --print-reg-domain \
  | sed -e 's/: / /' -e 's/(null)/null/' \
  > registerable.txt
```
