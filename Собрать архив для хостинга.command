#!/bin/bash
# Собирает сайт «Байкал Сиб-Транс» в один архив для загрузки на хостинг.
# Дважды щёлкните этот файл. Архив baikal-site.zip появится рядом с папкой проекта,
# и Finder сразу его покажет. Что делать с архивом дальше — в README-RU.md, раздел «Загрузка на хостинг».
#
# В архиве три папки:
#   app          — программа сайта;
#   data         — база данных: тексты, автопарк, цены, логин администратора
#                  (если вы уже заполняли сайт на этом компьютере);
#   public_html  — то, что открывается в интернете, вместе с загруженными фото.

cd "$(dirname "$0")" || exit 1
PROJECT="$PWD"
# Архив кладём рядом с папкой проекта, а не внутрь: так он не попадёт на хостинг вместе с сайтом.
OUT_DIR="${BAIKAL_OUT_DIR:-$(dirname "$PROJECT")}"
OUT="$OUT_DIR/baikal-site.zip"

finish() {
  echo
  read -r -n 1 -p "Нажмите любую клавишу, чтобы закрыть окно…"
  echo
  exit "${1:-0}"
}

if ! command -v zip >/dev/null 2>&1; then
  echo "Не найдена программа zip — архив собрать не получится."
  finish 1
fi
if [ ! -f public/index.php ] || [ ! -f app/bootstrap.php ]; then
  echo "Этот файл должен лежать в папке проекта — рядом с папками app, data и public."
  finish 1
fi

WORK=$(mktemp -d "${TMPDIR:-/tmp}/baikal-site.XXXXXX") || finish 1
trap 'rm -rf "$WORK"' EXIT

echo "Собираю архив сайта…"

# 1. Программа сайта и сайт для интернета (на хостинге папка public называется public_html).
cp -R app "$WORK/app" || finish 1
cp -R public "$WORK/public_html" || finish 1

# 2. База данных — без служебных файлов этого компьютера (сессии, кэш, журналы, коды входа).
mkdir -p "$WORK/data"
cp data/.htaccess data/README.txt "$WORK/data/" 2>/dev/null
HAS_DB=""
if [ -f data/site.sqlite ]; then
  # Безопасная копия базы: правильная, даже если сайт сейчас запущен.
  if ! { command -v sqlite3 >/dev/null 2>&1 && sqlite3 data/site.sqlite ".backup '$WORK/data/site.sqlite'" 2>/dev/null; }; then
    cp data/site.sqlite "$WORK/data/site.sqlite" || finish 1
  fi
  HAS_DB=1
fi

# 3. Убираем мусор macOS и выставляем права, которые просят хостинги: папки 755, файлы 644.
find "$WORK" -name '.DS_Store' -type f -delete
chmod -R u=rwX,go=rX "$WORK"

# 4. Архив.
if ! (cd "$WORK" && zip -r -q -X "$WORK/baikal-site.zip" app data public_html); then
  echo "Не получилось создать архив."
  finish 1
fi
if ! mv -f "$WORK/baikal-site.zip" "$OUT"; then
  echo "Не получилось сохранить архив в папку $OUT_DIR."
  finish 1
fi

SIZE=$(du -h "$OUT" | cut -f1 | tr -d ' ')
PHOTOS=$(find "$WORK/public_html/uploads" -type f ! -name '.*' 2>/dev/null | wc -l | tr -d ' ')
echo
echo "Готово! Архив: $OUT ($SIZE)"
if [ -n "$HAS_DB" ]; then
  echo "• В архиве всё, что вы заполнили на этом компьютере: тексты, автопарк, цены,"
  echo "  логин и пароль админки. На хостинге входите в админку с ними же."
else
  echo "• Базы данных в архиве нет: сайт создаст её сам при первом открытии,"
  echo "  а админка попросит код настройки."
fi
echo "• Фото и картинок в архиве: $PHOTOS."
echo
echo "Что дальше — в файле README-RU.md, раздел «Загрузка на хостинг»."
open -R "$OUT" 2>/dev/null
finish 0
