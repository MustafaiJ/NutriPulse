#!/usr/bin/env bash
# Restart the dev MySQL instance + Laravel dev server for HealthApp.
# Usage: ./dev/restart.sh
set -euo pipefail

DATADIR="/tmp/mj-mysql"
SOCKET="/tmp/mj-mysql.sock"
PORT=3307

if ! mysqladmin --socket="$SOCKET" -uroot ping &>/dev/null; then
  echo "Starting MySQL (data in $DATADIR)..."
  if [ ! -d "$DATADIR" ]; then
    mysqld --initialize-insecure --datadir="$DATADIR" --user="$(whoami)" --log-error=/tmp/mj-mysql-init.log
  fi
  nohup mysqld --no-defaults --datadir="$DATADIR" \
    --socket="$SOCKET" --port="$PORT" --user="$(whoami)" \
    --pid-file=/tmp/mj-mysql.pid --log-error=/tmp/mj-mysql.err \
    >/dev/null 2>&1 &
  for i in {1..30}; do
    mysqladmin --socket="$SOCKET" -uroot ping &>/dev/null && break
    sleep 1
  done
  echo "MySQL is up on port $PORT."
else
  echo "MySQL already running."
fi

if ! ss -tln | grep -q ':8000'; then
  echo "Starting Laravel dev server on :8000..."
  nohup php artisan serve --host=127.0.0.1 --port=8000 >/tmp/opencode/laravel-serve.log 2>&1 &
  sleep 2
  echo "Laravel is up at http://127.0.0.1:8000"
else
  echo "Laravel already running at http://127.0.0.1:8000"
fi