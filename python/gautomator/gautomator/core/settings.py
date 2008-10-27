# -*- coding: utf-8 -*-
#
# Copyright (c) 2007 David JL <izimobil@gmail.com>
#
# Permission is hereby granted, free of charge, to any person obtaining a
# copy of this software and associated documentation files (the "Software"),
# to deal in the Software without restriction, including without limitation
# the rights to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Software, and to permit persons to whom the
# Software is furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
# THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
# FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
# DEALINGS IN THE SOFTWARE.
#
# $Id$

"""
Settings module for gautomator.
"""

__version__ = '$Revision$'
__author__  = 'David JEAN LOUIS <izimobil@gmail.com>'

# dependencies {{{

import os
import sys
import tempfile

# }}}
# get_resource_dir() {{{

def get_resource_dir():
    """
    Return the directory containing the ressource files.
    """
    res_dir = 'share/gautomator'
    if __file__.startswith(sys.prefix):
        return os.path.join(sys.prefix, res_dir)
    return os.path.join(os.path.dirname(__file__), '..', '..', res_dir)

# }}}
# get_config_dir() {{{

def get_config_dir():
    """
    Return the directory containing the configuration files.
    """
    return os.path.join(get_resource_dir(), 'config')

# }}}
# get_builtin_actions_dir() {{{

def get_builtin_actions_dir():
    """
    Return the directory containing the builtin actions files.
    """
    return os.path.join(get_resource_dir(), 'actions')

# }}}
# get_user_actions_dir() {{{

def get_user_actions_dir():
    """
    Return the directory containing the user actions files.
    """
    return os.path.join(os.path.expanduser('~'), '.gautomator', 'actions')

# }}}
# get_builtin_workflows_dir() {{{

def get_builtin_workflows_dir():
    """
    Return the directory containing the builtin workflows files.
    """
    return os.path.join(get_resource_dir(), 'workflows')

# }}}
# get_user_workflows_dir() {{{

def get_user_workflows_dir():
    """
    Return the directory containing the user workflows files.
    """
    return os.path.join(os.path.expanduser('~'), '.gautomator', 'workflows')

# }}}
# get_named_pipe_filepath() {{{

def get_named_pipe_filepath():
    """
    Return the path to the named pipe used for IPC.
    """
    return os.path.join(tempfile.gettempdir(), 'gautomator.pipe')

# }}}
