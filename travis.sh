#!/usr/bin/env bash

this=`readlink -fe "$0"`
this_dir=`dirname "$this"`
cd "$this_dir"

set -x
set -e

SQL_HOST=no ./deploy.sh --defaults --test
./gitci.sh
