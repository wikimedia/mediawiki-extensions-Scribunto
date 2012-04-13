#!/bin/bash
ulimit -St $1
ulimit -Ht $2
ulimit -v $3
eval "$4"

