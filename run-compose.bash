#!/usr/bin/env bash

init_up(){
  if [ -z "$(command -v sops)" ]; then
    echo "Please install Sops"
    echo "https://github.com/mozilla/sops"
    exit 1
  fi
  if [ -z "$(command -v docker-compose)" ]; then
    echo "Please install Docker"
    echo "https://www.docker.com"
    exit 1
  fi
}

up(){
  sops exec-env credentials.yaml 'docker-compose up -d'
}

if [ "$0" = "${BASH_SOURCE[0]}" ] ; then
  init_up
  up
fi
