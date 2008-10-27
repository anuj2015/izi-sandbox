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
Controllers module for gautomator.
"""

__version__ = '$Revision$'
__author__  = 'David JEAN LOUIS <izimobil@gmail.com>'
__all__     = ['ActionManager', 'CategoryManager', 'WorkflowManager']

# dependencies {{{

import fcntl
import os
import sys
import shutil
import zipfile
try:
    import cPickle as pickle
except:
    import pickle
try:
    import xml.etree.cElementTree as etree
except ImportError:
    import xml.etree.ElementTree as etree

from gautomator.core import helpers
from gautomator.core import settings
from gautomator.core import models

# }}}
# class ActionManager {{{

class ActionManager:
    """
    A simple class that manages actions, it can create, install, update,
    remove or get informations about actions.
    """
    __cache__ = []

    @classmethod
    def get(cls, action_id):
        """
        Return the category which name matches 'name'.
        """
        for a in cls.get_all():
            if a.info['id'] == action_id:
                return a

    @classmethod
    def get_by_category(cls, category):
        """
        Return the category which name matches 'name'.
        """
        if category.name.lower() == 'all':
            return cls.get_all()
        return [a for a in cls.get_all() if category in a.info['categories']]

    @classmethod
    def get_all(cls, force_reload=False):
        """
        Return all available actions.
        """
        if force_reload:
            cls.__cache__ = []
        if not len(cls.__cache__):
            sys_dir = settings.get_builtin_actions_dir()
            user_dir = settings.get_user_actions_dir()
            dirs = [os.path.join(sys_dir, d) for d in os.listdir(sys_dir)] + \
                   [os.path.join(user_dir, d) for d in os.listdir(user_dir)]
            sys.path.append(sys_dir)
            sys.path.append(user_dir)
            for d in dirs:
                try:
                    xml = os.path.join(d, 'action.xml')
                    assert os.path.exists(xml)
                    node = etree.parse(xml).getroot()
                    action_id = os.path.basename(d)
                    mod = __import__(action_id)
                    cls.__cache__.append(mod.UserAction.new(node))
                except (AssertionError, ImportError), exc:
                    pass
            sys.path.remove(sys_dir)
            sys.path.remove(user_dir)
        cls.__cache__.sort()
        return cls.__cache__

    @staticmethod
    def create(action_id, **kwargs):
        """
        Create a new action skeleton with the give parameters.
        """
        action_dir = action_id
        action_id  = os.path.basename(action_id)
        # create directory of the action
        try:
            assert(action_id not in [a.info['id']
                   for a in ActionManager.get_all()])
            os.mkdir(action_dir, 0744)
        except Exception, exc:
            raise Exception('Action "%s" already exists' % action_id)
        confdir = settings.get_config_dir()
        # write the xml file
        xmlfile = os.path.join(action_dir, 'action.xml')
        try:
            xml = etree.parse(os.path.join(confdir, 'action.xml.in'))
            xml.getroot().attrib['id'] = action_id
            # XXX todo fill the xml
            fh = open(xmlfile, 'w')
            fh.write('<?xml version="1.0" encoding="utf-8"?>\n')
            xml.write(fh, 'utf-8')
            fh.close()
        except Exception, exc:
            if os.path.exists(xmlfile):
                os.unlink(xmlfile)
                os.rmdir(action_dir)
            raise
        # write the __init__.py file
        pyfile = os.path.join(action_dir, '__init__.py')
        try:
            tpl_fh = open(os.path.join(confdir, '__init__.py.in'))
            fh = open(pyfile, 'w')
            fh.write(tpl_fh.read() % {'action_id': action_id})
            tpl_fh.close()
            fh.close()
        except Exception, exc:
            if os.path.exists(pyfile):
                os.unlink(pyfile)
                os.unlink(xmlfile)
                os.rmdir(action_dir)
            raise
        return action_id

    @staticmethod
    def install(action_path, system_wide=False):
        """
        Install the given action (either an  action directory or an action zip
        file) system wide if the system_wide param is set to True or in the
        user home directory otherwise.
        """
        if not os.path.exists(action_path):
            raise Exception('"%s" not found, please provide the path to the'\
                            ' action you want to install' % action_path)
        action_id = os.path.basename(action_path)
        if system_wide:
            path = settings.get_builtin_actions_dir()
        else:
            path = settings.get_user_actions_dir()
        if not os.path.exists(path):
            os.makedirs(path, 0744)
        if not helpers.is_valid_action(action_path):
            raise Exception('Invalid action "%s"' % action_path)
        destpath = os.path.join(path, action_id)
        if os.path.exists(destpath):
            raise Exception('Action "%s" already installed' % action_id)
        if zipfile.is_zipfile(action_path):
            helpers.extract_zipfile(action_path, path)
        else:
            shutil.copytree(action_path, destpath)
        return action_id

    @staticmethod
    def uninstall(action_id):
        """
        Uninstall the given action identified by action id.
        """
        action_id = os.path.basename(action_id)
        for p in [settings.get_user_actions_dir(),
                  settings.get_builtin_actions_dir()]:
            d = os.path.join(p, '%s' % action_id)
            if os.path.isfile(os.path.join(d, 'action.xml')):
                shutil.rmtree(d)
                return action_id
        raise Exception('Action "%s" is not installed' % action_id)

    @staticmethod
    def update(action_path):
        """
        Install the given action (the path to the action directory or action
        zip file). This is a shortcut to uninstall/install.
        """
        if not os.path.exists(action_path):
            raise Exception('"%s" not found, please provide the path to the'\
                            ' action you want to update' % action_path)
        ActionManager.uninstall(os.path.basename(action_path))
        return ActionManager.install(action_path)


# }}}
# class CategoryManager {{{

class CategoryManager:
    """
    A simple class that manages categories.
    """
    __cache__ = []

    @classmethod
    def get(cls, name):
        """
        Return the category matching the given name.
        """
        for c in cls.get_all():
            if c.name.lower() == name.lower():
                return c
        return None

    @classmethod
    def get_all(cls, force_reload=False):
        """
        Return all available categories.
        """
        if force_reload:
            cls.__cache__ = []
        if not len(cls.__cache__):
            tree = etree.parse(os.path.join(
                settings.get_config_dir(), 'categories.xml'
            ))
            for node in tree.getroot().findall('category'):
                cls.__cache__.append(models.Category.new(node))
        return cls.__cache__


# }}}
# class WorkFlowManager {{{

class WorkflowManager:
    """
    A simple class that manages workflows, it can save, delete, load and
    organise workflows.
    """
    pass


# }}}
