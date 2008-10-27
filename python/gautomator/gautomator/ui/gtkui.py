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
gautomator gtk ui module.
"""

__version__   = '$Revision$'
__author__    = 'David JEAN LOUIS <izimobil@gmail.com>'
__copyright__ = '2007 David JEAN LOUIS <izimobil@gmail.com>'
__license__   = '''
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
'''

# dependencies {{{
import os
import sys
import gettext
import gobject
import pygtk; pygtk.require("2.0")
import gtk
import gtk.glade
import logging
try:
    import cPickle as pickle
except ImportError:
    import pickle

from gautomator.core import controllers
from gautomator.core import models
from gautomator.core import settings

# }}}
# Constants {{{

APP_NAME  = 'gautomator'

# }}}
# setup gettext {{{

_ = gettext.gettext
gettext.textdomain(APP_NAME)
gettext.bindtextdomain(APP_NAME)
gtk.glade.textdomain(APP_NAME)
gtk.glade.bindtextdomain(APP_NAME)

# }}}
# MainWindow class {{{

class MainWindow(object):
    """
    Main gui class.
    """
    # MainWindow::__init_() {{{

    def __init__(self):
        """
        Constructor.
        """
        self.id= 0
        self.current_action = None
        # init glade interface
        logging.debug('initializing glade interface')
        self.glade = gtk.glade.XML(
            os.path.join(
                settings.get_resource_dir(), 'glade', '%s.glade' % APP_NAME
            )
        )
        # connect signals
        logging.debug('connecting signals')
        sigs = {
            'on_button_search_clicked': self.on_search_action,
            'on_file_new_workflow_activate': self.on_new_workflow,
            'on_file_open_workflow_activate': self.on_open_workflow,
            'on_file_save_workflow_activate': self.on_save_workflow,
            'on_button_play_workflow_clicked': self.on_play_workflow,
            'on_button_stop_workflow_clicked': self.on_stop_workflow,
            'on_help_about_activate': self.on_open_about,
            'on_file_quit_activate': self.on_quit,
            'on_window_resize': self.on_window_resize,
            'on_window_destroy': self.on_quit
        }
        self.glade.signal_autoconnect(sigs)
        # widgets
        logging.debug('loading widgets')
        self.window = self.glade.get_widget('window_main')
        self.layout_actions = self.glade.get_widget('layout_actions')
        self.label_action_name = self.glade.get_widget('label_action_name')
        self.label_action_desc = self.glade.get_widget('label_action_desc')
        self.image_action_icon = self.glade.get_widget('image_action_icon')
        self.button_search = self.glade.get_widget('button_search')
        self.hpaned = self.glade.get_widget('hpaned1')
        self.tv_categories = self.init_categories_treeview()
        self.tv_actions = self.init_actions_treeview()
        self.tv_workflow = self.init_workflow_treeview()
        # set up drag and drop
        self.dnd_src = [('MY_TREE_MODEL_ROW', gtk.TARGET_SAME_APP, 0)]
        self.tv_actions.enable_model_drag_source(
            gtk.gdk.BUTTON1_MASK, self.dnd_src, gtk.gdk.ACTION_COPY)
        self.tv_workflow.enable_model_drag_source(
            gtk.gdk.BUTTON1_MASK, self.dnd_src, gtk.gdk.ACTION_MOVE)
        self.tv_actions.connect("drag_begin", self.on_drag_begin)
        self.tv_workflow.connect("drag_begin", self.on_drag_begin)
        self.tv_workflow.enable_model_drag_dest(
            self.dnd_src+[('text/plain', 0, 1)],
            gtk.gdk.ACTION_COPY)
        self.tv_workflow.connect(
            "drag_data_received",
            self.on_drag_data_received
        )
        self.tv_workflow.connect('drag-motion', self.on_drag_motion)

    # }}}
    # MainWindow::init() {{{

    @staticmethod
    def init():
        """
        Initialize the gtk loop.
        """
        logging.debug('loading main window')
        mainwindow = MainWindow()
        mainwindow.window.maximize()
        mainwindow.window.show()
        gtk.main()

    # }}}
    # MainWindow::init_categories_treeview() {{{

    def init_categories_treeview(self):
        """
        Initialize the categories treeview and return the treeview widget.
        """
        logging.debug('entering method init_categories_treeview()')
        tv = self.glade.get_widget("treeview_categories")
        pixbuf_renderer = gtk.CellRendererPixbuf()
        text_renderer   = gtk.CellRendererText()
        col = gtk.TreeViewColumn(_('Categories'))
        col.pack_start(pixbuf_renderer, False)
        col.pack_end(text_renderer, True)
        col.set_attributes(pixbuf_renderer, pixbuf=1)
        col.set_attributes(text_renderer, markup=2)
        tv.append_column(col)
        tv.set_model(gtk.ListStore(gobject.TYPE_PYOBJECT, gtk.gdk.Pixbuf, str))
        for i, cat in enumerate(controllers.CategoryManager.get_all()):
            try:
                theme = gtk.icon_theme_get_default()
                pb = theme.load_icon(cat.icon, 24, gtk.ICON_LOOKUP_USE_BUILTIN)
            except:
                # do not fail if icon is not found, just create a 1px pixbuf
                pb = gtk.gdk.Pixbuf(gtk.gdk.COLORSPACE_RGB, True, 8, 1, 1)
            tv.get_model().append([cat, pb, cat.name])
        tv.get_selection().connect('changed', self.on_category_selected)
        return tv

    # }}}
    # MainWindow::init_actions_treeview() {{{

    def init_actions_treeview(self):
        """
        Initialize the actions treeview and return the treeview widget.
        """
        logging.debug('entering method init_actions_treeview()')
        tv = self.glade.get_widget("treeview_actions")
        pixbuf_renderer = gtk.CellRendererPixbuf()
        text_renderer   = gtk.CellRendererText()
        col = gtk.TreeViewColumn(_('Actions'))
        col.pack_start(pixbuf_renderer, False)
        col.pack_end(text_renderer, True)
        col.set_attributes(pixbuf_renderer, pixbuf=1)
        col.set_attributes(text_renderer, markup=2)
        tv.append_column(col)
        tv.set_model(gtk.ListStore(gobject.TYPE_PYOBJECT, gtk.gdk.Pixbuf, str))
        tv.get_selection().connect('changed', self.on_action_selected)
        return tv

    # }}}
    # MainWindow::init_workflow_treeview() {{{

    def init_workflow_treeview(self):
        """
        Initialize the workflow treeview and return the treeview widget.
        """
        logging.debug('entering method init_workflow_treeview()')
        tv = self.glade.get_widget("treeview_workflow")
        pixbuf_renderer = gtk.CellRendererPixbuf()
        text_renderer   = gtk.CellRendererText()
        col = gtk.TreeViewColumn(_('Actions'))
        col.pack_start(pixbuf_renderer, False)
        col.pack_end(text_renderer, True)
        col.set_attributes(pixbuf_renderer, pixbuf=1)
        col.set_attributes(text_renderer, markup=2)
        tv.append_column(col)
        tv.set_model(gtk.ListStore(gobject.TYPE_PYOBJECT, gtk.gdk.Pixbuf, str))
        tv.connect('row-activated', self.on_workflow_action_activated)
        return tv

    # }}}
    # MainWindow::on_drag_begin() {{{

    def on_drag_begin(self, w, ctx):
        logging.debug('entering method MainWindow::on_drag_begin()')
        model, it = w.get_selection().get_selected()
        self.current_action = model.get_value(it, 0)

    # }}}
    # MainWindow::on_drag_data_received() {{{

    def on_drag_data_received(self, w, ctx, x, y, data, i, t):
        """
        Callback called when an element has been dropped on the window
        """
        logging.debug('entering method MainWindow::on_drag_data_received()')
        model  = w.get_model()
        drop_info = w.get_dest_row_at_pos(x, y)
        try:
            theme = gtk.icon_theme_get_default()
            pb = theme.load_icon(self.current_action.info['icon'],
                48, gtk.ICON_LOOKUP_USE_BUILTIN)
        except:
            # do not fail if icon is not found, just create a 1px pixbuf
            pb = gtk.gdk.Pixbuf(gtk.gdk.COLORSPACE_RGB, True, 8, 1, 1)
        label = "<b>%s</b>\n%s" % \
            (self.current_action.info['name'],
             self.current_action.info['description'])
        if drop_info:
            path, pos = drop_info
            iter = model.get_iter(path)
            if pos == gtk.TREE_VIEW_DROP_BEFORE or \
               pos == gtk.TREE_VIEW_DROP_INTO_OR_BEFORE:
                method = 'insert_before'
            else:
                method = 'insert_after'
            getattr(model, method)(iter, [self.current_action, pb, label])
        else:
            model.append([self.current_action, pb, label])
        if ctx.get_source_widget() == w:
            ctx.finish(True, True, t)

    # }}}
    # MainWindow::on_drag_motion() {{{

    def on_drag_motion(self, w, ctx, x, y, t):
        def get_neightbour_actions(w, x, y):
            model  = w.get_model()
            drop_info = w.get_dest_row_at_pos(x, y)
            if drop_info:
                path, pos = drop_info
                try:
                    if pos == gtk.TREE_VIEW_DROP_BEFORE or \
                       pos == gtk.TREE_VIEW_DROP_INTO_OR_BEFORE:
                        if path[0] == 0:
                            prev = None
                        else:
                            prev = model[path[0]-1][0]
                        next = model[path][0]
                    else:
                        prev = model[path][0]
                        if len(model) > (path[0]+1):
                            next = model[path[0]+1][0]
                        else:
                            next = None
                except:
                    prev, next = None, None
            else:
                if len(model) > 0:
                    prev = model[len(model)-1][0]
                else:
                    prev = None
                next = None
            return (prev, next)
        prev_action, next_action = get_neightbour_actions(w, x, y)
        if (prev_action is None or self.current_action.is_chainable_with(prev_action))\
            and \
           (next_action is None or next_action.is_chainable_with(self.current_action)):
            w.enable_model_drag_dest(
                self.dnd_src+[('text/plain', 0, 1)],
                gtk.gdk.ACTION_COPY
            )
        else:
            w.enable_model_drag_dest([], gtk.gdk.ACTION_COPY)

    # }}}
    # MainWindow::on_category_selected() {{{

    def on_category_selected(self, sel):
        """
        Callback called when the user has selected a category in the treeview.
        """
        logging.debug('entering method MainWindow::on_category_selected()')
        try:
            treemodel, it = sel.get_selected()
            cat = treemodel.get(it, 0)[0]
        except:
            return
        theme = gtk.icon_theme_get_default()
        self.tv_actions.get_model().clear()
        for i, act in enumerate(controllers.ActionManager.get_by_category(cat)):
            try:
                pb = theme.load_icon(act.info['icon'], 16,
                    gtk.ICON_LOOKUP_USE_BUILTIN)
            except:
                # do not fail if icon is not found, just create a 1px pixbuf
                pb = gtk.gdk.Pixbuf(gtk.gdk.COLORSPACE_RGB, True, 8, 1, 1)
            self.tv_actions.get_model().append([act, pb, act.info['name']])
        self.label_action_name.set_markup('<big><b>%s</b></big>' % cat.name)
        self.label_action_desc.set_text(cat.description)
        try:
            caticon = theme.load_icon(cat.icon, 32, gtk.ICON_LOOKUP_USE_BUILTIN)
        except:
            # do not fail if icon is not found, just create a 1px pixbuf
            caticon = gtk.gdk.Pixbuf(gtk.gdk.COLORSPACE_RGB, True, 8, 1, 1)
        self.image_action_icon.set_from_pixbuf(caticon)
        self._fix_description_label_wrapping()

    # }}}
    # MainWindow::on_action_selected() {{{

    def on_action_selected(self, sel):
        """
        Callback called when the user has selected an action in the treeview.
        """
        logging.debug('entering method MainWindow::on_action_selected()')
        try:
            treemodel, it = sel.get_selected()
            act = treemodel.get(it, 0)[0]
        except:
            return
        self.label_action_name.set_markup('<big><b>%s</b></big>' % \
            act.info['name'])
        self.label_action_desc.set_text(act.info['description'])
        try:
            theme = gtk.icon_theme_get_default()
            acticon = theme.load_icon(act.info['icon'], 32,
                gtk.ICON_LOOKUP_USE_BUILTIN)
        except:
            # do not fail if icon is not found, just create a 1px pixbuf
            acticon = gtk.gdk.Pixbuf(gtk.gdk.COLORSPACE_RGB, True, 8, 1, 1)
        self.image_action_icon.set_from_pixbuf(acticon)
        self._fix_description_label_wrapping()

    # }}}
    # MainWindow::on_workflow_action_activated() {{{

    def on_workflow_action_activated(self, tv, path, tv_col):
        """
        Callback called when the user has selected an action in the treeview.
        """
        logging.debug('entering method MainWindow::on_workflow_action_activated()')
        xml = gtk.glade.XML(os.path.join(
            settings.get_resource_dir(), 'glade', 'action.glade'
        ))
        dialog = xml.get_widget('dialog_action')
        dialog.show()

    # }}}
    # MainWindow::on_search_action() {{{

    def on_search_action(self, *args, **kwargs):
        """
        Callback called when the user has clicked the search button or pressed
        enter to launch the search.
        """
        logging.debug('entering method MainWindow::on_search_action()')

    # }}}
    # MainWindow::on_new_workflow() {{{

    def on_new_workflow(self, *args, **kwargs):
        """
        Callback called when the user has requested the creation of a new
        workflow.
        """
        logging.debug('entering method MainWindow::on_new_workflow()')

    # }}}
    # MainWindow::on_open_workflow() {{{

    def on_open_workflow(self, *args, **kwargs):
        """
        Callback called when the user has requested the opening of an existing
        workflow.
        """
        logging.debug('entering method MainWindow::on_open_workflow()')

    # }}}
    # MainWindow::on_save_workflow() {{{

    def on_save_workflow(self, *args, **kwargs):
        """
        Callback called when the user has requested to save the current
        workflow.
        """
        logging.debug('entering method MainWindow::on_save_workflow()')

    # }}}
    # MainWindow::on_play_workflow() {{{

    def on_play_workflow(self, *args, **kwargs):
        """
        Callback called when the user has clicked the play button to play the
        current workflow.
        """
        logging.debug('entering method MainWindow::on_play_workflow()')

    # }}}
    # MainWindow::on_stop_workflow() {{{

    def on_stop_workflow(self, *args, **kwargs):
        """
        Callback called when the user has clicked the stop button to stop the
        current workflow.
        """
        logging.debug('entering method MainWindow::on_stop_workflow()')

    # }}}
    # MainWindow::on_open_about() {{{

    def on_open_about(self, *args, **kwargs):
        """
        Callback called when the user has requested the about dialog.
        """
        logging.debug('entering method MainWindow::on_open_about()')
        dialog = gtk.AboutDialog()
        dialog.set_transient_for(self.window)
        dialog.set_name(APP_NAME)
        dialog.set_authors([__author__])
        dialog.set_copyright(__copyright__)
        dialog.set_license(__license__)
        dialog.set_version(__version__)
        dialog.run()
        dialog.destroy()

    # }}}
    # MainWindow::on_window_resize() {{{

    def on_window_resize(self, *args):
        """
        Callback called when the window emit a size-request signal.
        """
        logging.debug('entering method MainWindow::on_window_resize()')
        self._fix_description_label_wrapping()

    # }}}
    # MainWindow::on_quit() {{{

    def on_quit(self, *args, **kwargs):
        """
        Callback called when the user close the window with the close button or
        via the window close [x] icon or via the file/quit menu.
        """
        logging.debug('entering method MainWindow::on_quit()')
        gtk.main_quit()

    # }}}
    # MainWindow::_fix_description_label_wrapping() {{{

    def _fix_description_label_wrapping(self):
        # XXX hack for proper text wrapping, it works well :)
        self.label_action_desc.set_size_request(
            self.hpaned.get_position()-10, -1)

    # }}}

# }}}
# ActionDialog class {{{

class ActionDialog(object):
    """
    Base gui class for action dialogs.
    """
    def __init__(self, action, glade_file):
        """
        Constructor.
        """
        self.action = action
        self.glade  = gtk.glade.XML(glade_file)
        self.dialog = self.glade.get_widget('action_dialog')

    def show(self):
        """Show the dialog and invoke relevant action methods"""
        self.action.gui_opened()
        response = self.dialog.run()
        if response == gtk.RESPONSE_OK:
            self.action.gui_closed()
        self.dialog.destroy()

# }}}
