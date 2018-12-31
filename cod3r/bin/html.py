#!/usr/bin/env python
import argparse
import fileinput
import sys

from cod3r.escapes import html

parser = argparse.ArgumentParser(
    description="cod3r html converter.\n\n"
    "Output in html standard input.\n"
    "Example: $ echo \"<b>Bold</b>\" | b html",
    formatter_class=argparse.RawTextHelpFormatter)

parser.parse_known_args()


with html():
    for line in fileinput.input():
        sys.stdout.write(line)
