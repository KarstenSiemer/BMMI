#!/usr/bin/env bash

# Set Globals
CONTAINER="bmmi-db"
DUMP="./db-bmmi.dump"
MD5_PRE=""
MD5_POST=""

init_update(){
  if [ -z "$(command -v md5sum)" ]; then
    echo "Please install md5sum"
    exit 1
  fi
}

display_help(){
  echo "Update dump file of bmmi project"
  echo "-c|--container name of container to dump"
  echo "  default: bmmi-db"
  echo "-f|--dump-file set name of dump file"
  echo "  default: ./db-bmmi.dump"
}

parse(){
  local POSITIONAL=""
  while [[ $# -gt 0 ]]; do
  key="$1"

  case $key in
      -c|--container)
      CONTAINER="${2}"
      shift 2
      ;;
      -f|--dump-file)
      DUMP="${2}"
      shift 2
      ;;
      -h|--help)
      display_help
      exit 0
      ;;
      *)    # unknown option
      POSITIONAL+=("${key}")
      shift
      ;;
  esac
  done
  set -- "${POSITIONAL[@]}" # restore positional parameters
  if [ $# -gt 1 ];then
    echo "Unknow params: " "$@"
    display_help
    exit 1
  fi
}

check_file(){
  if test -f "${DUMP}"; then
    return 0
  fi
  return 1
}

get_md5(){
  local HASH=""
  if check_file; then
    # shellcheck disable=SC2207,SC2002
    HASH=($(cat "${DUMP}" | md5sum))
  else
    HASH=()
  fi
  echo "${HASH[0]}"
}

init_update
parse "${@}"
MD5_PRE="$(get_md5)"
./run-dump.bash --container "${CONTAINER}" --dump-file "${DUMP}"
MD5_POST="$(get_md5)"

if [ "${MD5_PRE}" != "${MD5_POST}" ]; then
  exit 1
fi
