#!/usr/bin/env bash

# Include up function
source ./run-compose.bash

# Set Globals
DONT_UP=false
RUNNING=false
CONTAINER="bmmi-db"
DUMP="./db-bmmi.dump"
FILE_EXISTS=false

init_dump(){
  if [ -z "$(command -v docker)" ]; then
    echo "Please install Docker"
    echo "https://www.docker.com"
    exit 1
  fi
}

display_help(){
  echo "Runs restore on db of bmmi project"
  echo "-d|--dont-up to exit with error, if db is down"
  echo "  default: false"
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
      -d|--dont-up)
      DONT_UP="true"
      shift
      ;;
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

check_running(){
  if grep -q db <<< "$(docker-compose ps --services --filter 'status=running')"; then
    RUNNING=true
  fi
}

check_file(){
  if test -f "${DUMP}"; then
    FILE_EXISTS=true
  fi
}

restore(){
  docker exec -i "${CONTAINER}" sh -c 'exec mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < "${DUMP}"
}

init_dump
parse "${@}"
check_file
check_running
if ! $RUNNING && ! $DONT_UP; then
  init_up
  up
  check_running
fi
if ! $RUNNING; then
  echo "Database container needs to be running to create a dump."
  display_help
  exit 1
fi
if ! $FILE_EXISTS; then
  echo "Can't locate dump file."
  display_help
  exit 1
fi
restore
