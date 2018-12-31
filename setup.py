#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
cod3r - A sleek web based terminal emulator
"""
import os

from setuptools import setup

about = {}
with open(os.path.join(
        os.path.dirname(__file__), "cod3r", "__about__.py")) as f:
    exec(f.read(), about)

setup(
    name=about['__title__'],
    version=about['__version__'],
    description=about['__summary__'],
    url=about['__uri__'],
    author=about['__author__'],
    author_email=about['__email__'],
    license=about['__license__'],
    platforms="Any",
    scripts=['cod3r.server.py', 'scripts/cod3r', 'scripts/b'],
    packages=['cod3r'],
    install_requires=["tornado>=3.2"],
    extras_require={
        'systemd': ['tornado_systemd']
    },
    package_data={
        'cod3r': [
            'sass/*.sass',
            'themes/*.*',
            'themes/*/*.*',
            'themes/*/*/*.*',
            'static/fonts/*',
            'static/images/favicon.png',
            'static/main.css',
            'static/html-sanitizer.js',
            'static/*.min.js',
            'templates/index.html',
            'bin/*',
            'templates/motd',
            'cod3r.conf.default'
        ]
    },
    classifiers=[
        "Development Status :: 5 - Production/Stable",
        "Intended Audience :: Developers",
        "License :: OSI Approved :: GNU General Public License v3 (GPLv3)",
        "Operating System :: POSIX :: Linux",
        "Programming Language :: Python :: 2",
        "Programming Language :: Python :: 3",
        "Topic :: Terminals"])
