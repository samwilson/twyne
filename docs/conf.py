# Configuration file for the Sphinx documentation builder.
#
# Docs: https://www.sphinx-doc.org/en/master/usage/configuration.html
#

project = 'Twyne'
copyright = '2021, Sam Wilson'
author = 'Sam Wilson'

# Read the version numbers from the latest Git tag.
import os
import re
release = os.popen('git describe --tags --always').read().strip()
version = release

extensions = []

# Add any paths that contain templates here, relative to this directory.
templates_path = ['_templates']

# List of patterns, relative to source directory, that match files and
# directories to ignore when looking for source files.
# This pattern also affects html_static_path and html_extra_path.
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']

# -- Options for HTML output -------------------------------------------------
html_theme = 'sphinx_rtd_theme'
html_show_copyright = False
html_static_path = ['_static']
