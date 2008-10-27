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
gautomator helpers.
"""

__version__ = '$Revision$'
__author__  = 'David JEAN LOUIS <izimobil@gmail.com>'
__all__     = ['uniq', 'is_valid_action', 'extract_zipfile']

# uniq() {{{

def uniq(lst, sort=False):
    """
    Remove all duplicates from the given list and return it.
    If sort is True, the list is also sorted before being returned.
    """
    d = {}
    ret = [d.setdefault(e,e) for e in lst if e not in d]
    if sort:
        ret.sort()
    return ret

# }}}
# is_valid_action() {{{

def is_valid_action(action_path):
    """
    Ensure that the given path or file points to a valid action by checking its
    contents.
    """
    import os
    if os.path.isdir(action_path):
        contents = os.listdir(action_path)
    else:
        try:
            import zipfile
            zf = zipfile.ZipFile(action_path)
            contents = zf.namelist()
            zf.close()
        except:
            return False
    return 'action.xml' in contents and '__init__.py' in contents

# }}}
# extract_zipfile() {{{

def extract_zipfile(f, destdir):
    """
    Extract the zip file 'zf' to the destination dir 'destdir'.
    """
    import os
    destdir = os.path.join(destdir, os.path.basename(f).rsplit('.', 1)[0])
    if os.path.exists(destdir):
        raise Exception('Directory "%s" already exists' % destdir)
    try:
        os.makedirs(destdir)
        import zipfile
        zf = zipfile.ZipFile(f)
        for name in zf.namelist():
            destpath = os.path.join(destdir, os.path.normpath(name))
            if name.endswith('/'):
                os.makedirs(destpath)
            else:
                parent = os.path.dirname(destpath)
                if '/' in name and not os.path.exists(parent):
                    os.makedirs(parent)
                fh = open(destpath, 'w')
                fh.write(zf.read(name))
                fh.close()
        zf.close()
    except:
        if os.path.exists(destdir):
            import shutil
            shutil.rmtree(destdir)
        raise

# }}}
