#!/usr/bin/env bash

# Include up function
source ./run-compose.bash

# Set Globals
DONT_UP=false
RUNNING=false
CONTAINER="bmmi-db"

init_dump(){
  if [ -z "$(command -v docker)" ]; then
    echo "Please install Docker"
    echo "https://www.docker.com"
    exit 1
  fi
}

display_help(){
  echo "Show db contents of db of bmmi project"
  echo "-d|--dont-up to exit with error, if db is down"
  echo "  default: false"
  echo "-c|--container name of container to dump"
  echo "  default: bmmi-db"
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

show(){
  docker exec -it "${CONTAINER}" sh -c 'exec mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" --execute="select id, title, filename, filetype, filesize, duration, actors, LEFT(content, 30) as content from videos;"'
}

init_dump
parse "${@}"
check_running
if ! $RUNNING && ! $DONT_UP; then
  init_up
  up
  check_running
fi
if ! $RUNNING; then
  echo "Database container needs to be running to show database contents."
  display_help
  exit 1
fi
show
