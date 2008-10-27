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
gautomator data types.
"""

__version__ = '$Revision$'
__author__  = 'David JEAN LOUIS <izimobil@gmail.com>'
__all__     = ['Type', 'TypeNull', 'TypeFilesAndFolders', 'TypeData']

# dependencies {{{

import mimetypes
import gettext
from gautomator.core.helpers import uniq

_ = gettext.gettext
mimetypes.init()

#}}}
# Type class {{{

class Type(object):
    """
    Base Abstract class for gautomator types.
    """
    mimetypes = []

    def is_compatible_with(self, other):
        return other == self

    def supported_mimetypes(cls):
        return []

# }}}
# TypeNull class {{{

class TypeNull(Type):
    """
    Null type.
    """
    def __str__(self):
        return _('Null')

# }}}
# TypeFilesAndFolders class {{{

class TypeFilesAndFolders(Type):
    """
    Handle files and folders recursively.
    """
    mimetypes = uniq(['inode/directory'] + \
        mimetypes.types_map.values() + mimetypes.common_types.values(), True)

    def __str__(self):
        return _('Files and folders')

# }}}
# TypeData class {{{

class TypeData(Type):
    """
    Handle data.
    """
    def __str__(self):
        return _('Data')

# }}}
