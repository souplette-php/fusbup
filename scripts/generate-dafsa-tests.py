#!/usr/bin/env python3

from pathlib import Path
import textwrap
import sys


ROOT_DIR = Path(__file__).parent.parent
sys.path.insert(0, str(ROOT_DIR / 'tools'))


import test_dafsa
from test_dafsa import TestDafsa


OUT_DIR = ROOT_DIR / 'tests/Resources/dafsa/gecko'


def generate_test(test: TestDafsa, name: str):
  def patched_assert(input, expected):
    with open(OUT_DIR / f'{name}.txt', 'w') as fp:
      fp.write(textwrap.dedent(input))
      fp.write('\n>>>>>>>>>>\n')
      fp.write(textwrap.dedent(expected))

  test_dafsa._assert_dafsa = patched_assert
  method = getattr(test, name)
  method()


if __name__ == '__main__':
  test = TestDafsa()

  for n in range(1, 21):
    name = f'test_{n}'
    generate_test(test, name)

