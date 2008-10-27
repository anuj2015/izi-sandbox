==========
gautomator
==========

:Author: David JEAN LOUIS <izimobil@gmail>
:Title: gautomator
:Version: 0.1
:License: MIT License
:Date: 5 oct. 2007

.. |appname| replace:: **gautomator**
.. |automator| replace:: Apple Mac OS X Automator
.. |actions| replace:: **actions**
.. |action| replace:: **action**
.. |workflows| replace:: **workflows**

.. contents::
   :depth: 3

.. _Rationale:

About
-----

|appname| is an application designed for gnome and other gtk window managers
highly inspired from |automator|. The main goal is to provide a simple
interface to allow the user to automate repetitive everyday manual tasks
quickly, efficiently, and effortlessly without programming.

|appname| main building block are |actions|. Each |action| is designed to
perform a single (and preferably simple) task and do it well. The role of
|appname| is to manage and chain these |actions| together in order to create
custom |workflows| that can suit any needs. This idea is very similar to the
UNIX philosophy and especially the UNIX pipes system.

As a summary, |appname| combines the philosophy and power of UNIX pipes with
a simple and intuitive User Interface.

.. _Use cases:

Use cases
---------

For lambda users
~~~~~~~~~~~~~~~~

1. Mary wants to automate emailing a set of photos to her friend:

- she opens automator to create a new workflow called "zip photos and
  send by email"
- she adds the action "ask for files"
- she adds the action "rename files" to have a consistent naming
- she adds the action "create archive"
- she adds the action "send files by email"
- she save her workflow
- now every time she wants to share a set of photos she just select the files,
  right click and select |appname|->workflows->zip photos and send by email.

2. Jimmy wants to convert his dowloaded mp3 to ogg format

- he opens automator to create a new workflow called "mp3 to ogg"
- he adds the action "ask for files"
- he adds the action "mp3 to ogg"
- he save his workflow
- now every time he download a set of mp3 et just select the files, right click
  and select |appname|->workflows->mp3 to ogg.

For action developers
~~~~~~~~~~~~~~~~~~~~~

.. _Implementation:

Implementation
--------------

Class diagram
~~~~~~~~~~~~~

Core
~~~~

Actions
~~~~~~~

User interface
~~~~~~~~~~~~~~


.. _Integration:

Integration
-----------

Nautilus
~~~~~~~~

dbus
~~~~

XPCOM
~~~~~

Other bridges
~~~~~~~~~~~~~
- python-uno: for openoffice suite


.. _References:

1. Apple Mac OS X automator

- Features: http://www.apple.com/macosx/features/automator/
- Programming guide: http://developer.apple.com/documentation/AppleApplications/Conceptual/AutomatorConcepts/index.html

2. Unix pipes

- Pipeline on wikipedia: http://en.wikipedia.org/wiki/Pipeline_(Unix)

.. _Work done so far:

Work done so far
----------------

You can download a basic but working copy of |appname| here:
http://www.izimobil.org/gautomator/



