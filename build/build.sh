#!/bin/bash

TASKWARRIOR=$1

wget http://taskwarrior.org/download/task-$TASKWARRIOR.tar.gz
gunzip task-$TASKWARRIOR.tar.gz
tar xf task-$TASKWARRIOR.tar
cd task-$TASKWARRIOR
sudo apt-get install cmake build-essential uuid-dev libgnutls-dev libreadline6-dev --force-yes
sudo cmake -DCMAKE_BUILD_TYPE=release .
sudo make
cd ..
cp task-$TASKWARRIOR/src/task ./taskwarrior-$TASKWARRIOR
sudo rm -Rf task-$TASKWARRIOR*
