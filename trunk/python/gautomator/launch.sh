#!/bin/sh
PYTHON=`which python`
cd `dirname $0` && exec $PYTHON "bin/gautomator" $1
