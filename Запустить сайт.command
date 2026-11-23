#!/bin/bash
# Запуск сайта «Байкал Сиб-Транс» на этом компьютере — для проверки.
# Дважды щёлкните этот файл. Сайт откроется в браузере по адресу http://localhost:8000
# Чтобы остановить сайт, закройте окно «Терминала».

cd "$(dirname "$0")" || exit 1
PORT=8000
URL="http://localhost:$PORT"
export PATH="$HOME/.docker/bin:/usr/local/bin:/opt/homebrew/bin:$PATH"

pause_and_exit() {
  echo
  read -r -n 1 -p "Нажмите любую клавишу, чтобы закрыть окно…"
  exit 1
}

# Сайт уже запущен (например, из Claude) — просто открываем его.
if curl -s -o /dev/null --max-time 2 "$URL"; then
  echo "Сайт уже работает: $URL"
  open "$URL"
  exit 0
fi

# Вариант 1: на компьютере установлен PHP 8.1 или новее.
if command -v php >/dev/null 2>&1 && php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' 2>/dev/null; then
  echo "Сайт запущен: $URL"
  echo "Чтобы остановить сайт, закройте это окно."
  (sleep 1; open "$URL") &
  PHP_CLI_SERVER_WORKERS=4 php -d upload_max_filesize=16M -d post_max_size=64M \
    -S 127.0.0.1:$PORT -t public public/index.php
  exit 0
fi

# Вариант 2: программа Docker Desktop.
if command -v docker >/dev/null 2>&1; then
  if ! docker info >/dev/null 2>&1; then
    echo "Запускаю Docker — это займёт до минуты…"
    open -a Docker
    for _ in $(seq 1 60); do
      docker info >/dev/null 2>&1 && break
      sleep 2
    done
  fi
  if ! docker info >/dev/null 2>&1; then
    echo "Docker не запустился. Откройте программу Docker вручную и запустите этот файл ещё раз."
    pause_and_exit
  fi
  if ! docker image inspect baikal-php:8.4 >/dev/null 2>&1; then
    echo "Первый запуск: готовлю PHP 8.4 (пара минут)…"
    docker build -t baikal-php:8.4 -f .claude/php.Dockerfile .claude || pause_and_exit
  fi
  docker rm -f baikal-site >/dev/null 2>&1
  trap 'docker stop baikal-site >/dev/null 2>&1' EXIT HUP INT TERM
  echo "Сайт запущен: $URL"
  echo "Чтобы остановить сайт, закройте это окно."
  (sleep 2; open "$URL") &
  docker run --rm --init --name baikal-site -p 127.0.0.1:$PORT:8000 \
    -e PHP_CLI_SERVER_WORKERS=4 -v "$PWD":/site -w /site baikal-php:8.4 \
    php -d upload_max_filesize=16M -d post_max_size=64M -S 0.0.0.0:8000 -t public public/index.php
  exit 0
fi

echo "Не найдены ни PHP, ни Docker — сайт не на чем запустить."
echo "Установите программу Docker Desktop (docker.com) и запустите этот файл ещё раз."
pause_and_exit
