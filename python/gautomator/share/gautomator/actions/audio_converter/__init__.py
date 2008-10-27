# -*- coding: utf-8 -*-
#
# This file contains the audio_converter gautomator action.

from gautomator.core.models import Action

# your action class
class UserAction(Action):
    def run(self, *args):
        # implementation here
        # the default is to return arguments untouched
        return args
