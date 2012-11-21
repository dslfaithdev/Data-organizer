#!/bin/sh

(php $(dirname ${0})/parseDir.php $1 2>&1 1>&3 | tee /tmp/errors.log) 3>&1 1>&2 | tee /tmp/output.log
