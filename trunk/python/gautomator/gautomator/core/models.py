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
gautomator models.
"""

__version__ = '$Revision$'
__author__  = 'David JEAN LOUIS <izimobil@gmail.com>'
__all__     = ['Action', 'Author', 'Parameter', 'Input', 'Ouput', 'Category',
               'Workflow']

# dependencies {{{

import os
import sys
import logging
import gettext
_ = gettext.gettext


# }}}
# Action class {{{

class Action:
    """
    Base class for all gautomator actions.
    """
    __cache__ = {}

    def __init__(self, *args, **kwargs):
        """
        Constructor.
        """
        self.info = {
            'id'         : kwargs.get('id_'),
            'name'       : kwargs.get('name'),
            'icon'       : kwargs.get('icon'),
            'description': kwargs.get('description', ''),
            'version'    : kwargs.get('version', ''),
            'authors'    : kwargs.get('authors', []),
            'categories' : kwargs.get('categories', []),
            'parameters' : kwargs.get('parameters', []),
            'input'      : kwargs.get('input_', []),
            'output'     : kwargs.get('output', [])
        }

    def __str__(self):
        """
        String representation of the action.
        """
        return self.info['name']

    def is_chainable_with(self, action):
        """
        Return a boolean determining if the action can be chained with the
        action provided.
        An action can be chained with another if it accepts what the other
        action provides.

        Keyword argument:
        action -- object, an instance of Action class or subclass
        """
        return True

    def run(self, *args):
        """
        Method that actually perform the action, the default is to return
        the input data untouched.
        """
        return args

    @classmethod
    def new(cls, xml):
        authors = [Author.new(n) for n in xml.findall('authors/author')]
        cats = []
        from gautomator.core.controllers import CategoryManager
        for node in xml.findall('categories/category'):
            cat = CategoryManager.get(node.text.strip())
            if cat is not None:
                cats.append(cat)
        params = [Parameter.new(n) for n in xml.findall('parameters/parameter')]
        input_ = Input.new(xml.find('input'))
        output = Output.new(xml.find('output'))
        icon   = xml.findtext('icon').strip()
        if icon is None or icon == '':
            icon = 'applications-system'
        return cls(
            id_         = xml.attrib.get('id'),
            name        = xml.findtext('name').strip(),
            icon        = icon,
            description = xml.findtext('description').strip(),
            version     = xml.findtext('version').strip(),
            authors     = authors,
            categories  = cats,
            parameters  = params,
            input_      = input_,
            output      = output
        )


# }}}
# Action class {{{

class Workflow:
    """
    Base class for all gautomator actions.
    """

    def __init__(self, *args, **kwargs):
        """
        Constructor.
        """
        self.name = kwargs.get('name')
        self.actions = []

    def __str__(self):
        """
        String representation of the action.
        """
        return self.name

    def run(self, *args):
        """
        Method that actually perform the action, the default is to return
        the input data untouched.
        """
        for action in self.actions:
            yield action.run()

# }}}
# Parameter class {{{

class Parameter:
    """
    Represent an action parameter.
    """
    TYPE_STRING          = 1
    TYPE_INT             = 2
    TYPE_FLOAT           = 3
    TYPE_BOOL            = 4
    TYPE_SINGLE_CHOICE   = 5
    TYPE_MULTIPLE_CHOICE = 6
    TYPE_TEXT            = 7
    TYPE_FILE            = 8
    TYPE_DIRECTORY       = 9
    TYPE_COLOR           = 10
    TYPE_FONT            = 11

    def __init__(self, *args, **kwargs):
        """
        Constructor.
        """
        self.name = kwargs.get('name', '')
        self.required = kwargs.get('required', False)
        self.default = kwargs.get('default', '')
        self.choices = kwargs.get('choices', [])
        try:
            self.type = getattr(self,
                'TYPE_%s' % kwargs.get('type_', 'string').upper())
        except:
            self.type = self.TYPE_STRING

    def __str__(self):
        """
        String representation of the author.
        """
        return self.name

    @classmethod
    def new(cls, xml):
        """
        Load instance from given xml node.
        """
        choices = [(c.text.strip(), c.attrib.get('id')) for c in
                   xml.findall('choice')]
        return Parameter(
            name=xml.findtext('name', '').strip(),
            default=xml.findtext('default', '').strip(),
            choices=choices,
            required=xml.attrib.get('required'),
            type_=xml.attrib.get('type')
        )


# }}}
# _IOBase class {{{

class _IOBase:
    """
    Represent an action author.
    """
    TYPE_FILESANDFOLDERS = 1
    TYPE_FOLDERS         = 2
    TYPE_DATA            = 3

    type_map = {
        TYPE_FILESANDFOLDERS: _('Files and folders'),
        TYPE_FOLDERS        : _('Folders'),
        TYPE_DATA           : _('Data'),
    }

    def __init__(self, *args, **kwargs):
        """
        Constructor.
        """
        self.mimetypes = kwargs.get('mimetypes', [])
        self.type = kwargs.get('type_', self.TYPE_FILESANDFOLDERS)

    def __str__(self):
        """
        String representation of the author.
        """
        return self.type_map[self.type]

    @classmethod
    def new(cls, xml):
        """
        Load instance from given xml node.
        """
        mimetypes = [m.text.strip() for m in xml.findall('mimetypes')]
        try:
            type_ = xml.attrib.get('type', 'FilesAndFolders').strip().upper()
            type_ = getattr(self, 'TYPE_%s' % type_)
        except:
            type_ = None
        return cls(type_=type_, mimetypes=mimetypes)

# }}}
# Input class {{{

class Input(_IOBase):
    pass

# }}}
# Output class {{{

class Output(_IOBase):
    pass

# }}}
# Author class {{{

class Author:
    """
    Represent an action author.
    """
    ROLE_LEAD        = 1
    ROLE_CONTRIBUTOR = 2

    def __init__(self, *args, **kwargs):
        """
        Constructor.
        """
        self.name = kwargs.get('name', '')
        self.email = kwargs.get('email', '')
        try:
            self.role = getattr(self,
                'ROLE_%s' % kwargs.get('role', 'lead').upper())
        except:
            self.role = self.ROLE_LEAD

    def __str__(self):
        """
        String representation of the author.
        """
        ret = self.name
        if self.email:
            ret += ' <%s>' % self.email
        return ret

    @classmethod
    def new(cls, xml):
        """
        Load instance from given xml node.
        """
        return Author(
            name=xml.findtext('name', '').strip(),
            email=xml.findtext('email', '').strip(),
            role=xml.attrib.get('role')
        )

# }}}
# Category class {{{

class Category:
    """
    Represent a category of actions.
    """
    __cache__ = {}

    def __init__(self, *args, **kwargs):
        """
        Constructor.
        """
        self.name = kwargs.get('name', '')
        self.icon = kwargs.get('icon', 'applications-system')
        self.description = kwargs.get('description', '')

    def __str__(self):
        """
        String representation of the category.
        """
        return self.name

    @classmethod
    def new(cls, xml):
        """
        Build a category instance from the given xml node.
        """
        return cls(
            name        = xml.findtext('name', '').strip(),
            icon        = xml.findtext('icon', '').strip(),
            description = xml.findtext('description', '').strip()
        )

# }}}
