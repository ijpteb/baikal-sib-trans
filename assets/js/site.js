/*
 * Скрипт сайта «Байкал Сиб-Транс».
 * Шапка, меню, появление блоков, карусели, фильтр автопарка, вопросы-ответы,
 * карта, заявка (собирает текст и отправляет через мессенджер), калькулятор.
 * Ничего не отправляет на сервер и ничего не запоминает.
 */

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

let config = {};
try {
  config = JSON.parse($('#site-config')?.textContent || '{}');
} catch (error) {
  config = {};
}
config.messengers = Array.isArray(config.messengers) ? config.messengers : [];
config.calculator = Array.isArray(config.calculator) ? config.calculator : [];

/* ---------- Мелочи ---------- */

function plural(n, one, few, many) {
  const a = Math.abs(n) % 100;
  const b = a % 10;
  if (a > 10 && a < 20) return many;
  if (b > 1 && b < 5) return few;
  if (b === 1) return one;
  return many;
}

const numberFormat = new Intl.NumberFormat('ru-RU');
function money(value) {
  return `${numberFormat.format(Math.round(value)).replace(/\s/g, ' ')} ₽`;
}

function iconSvg(name) {
  return `<svg class="icon" width="24" height="24" aria-hidden="true" focusable="false"><use href="#i-${name}"></use></svg>`;
}

const pad2 = (n) => String(n).padStart(2, '0');

function isoDate(d) {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}

function todayIso() {
  return isoDate(new Date());
}

/* ---------- Дата и время: дд.мм.гггг и чч:мм в любом браузере ---------- */

const WEEKDAYS = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
// Начала названий месяцев — для даты словами из переписки: «25 декабря 2026», «1 мая 2027 г.»
const MONTH_STEMS = ['янв', 'фев', 'мар', 'апр', 'ма', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

/** Пока человек печатает: «25122026» → «25.12.2026», «1.2.» → «01.02.». Здесь только вид, без проверки. */
function maskDate(raw) {
  const text = String(raw || '');
  // Вставили дату, где год впереди: «2026-12-25», «2026.1.5»
  const yearFirst = /^\s*(\d{4})[-./](\d{1,2})[-./](\d{1,2})\s*$/.exec(text);
  if (yearFirst) return `${yearFirst[3].padStart(2, '0')}.${yearFirst[2].padStart(2, '0')}.${yearFirst[1]}`;
  // Вставили дату словами: «25 декабря 2026», «1 мая 2027 года»
  const words = /^\s*(\d{1,2})\s+([а-яё]{3,})\.?\s+(\d{4})(?:\s*г(?:ода?)?\.?)?\s*$/i.exec(text);
  if (words) {
    const month = MONTH_STEMS.findIndex((stem) => words[2].toLowerCase().startsWith(stem)) + 1;
    if (month) return `${words[1].padStart(2, '0')}.${pad2(month)}.${words[3]}`;
  }
  const groups = text.split(/\D+/);
  let digits = '';
  groups.forEach((group, i) => {
    // День или месяц одной цифрой, после которой поставили точку: «1.» → «01.»
    digits += i < groups.length - 1 && group.length === 1 && digits.length < 4 ? `0${group}` : group;
  });
  // День не начинается с 4–9, месяц — с 2–9: «5122026» → «05.12.2026», «254» → «25.04».
  if (/^[4-9]/.test(digits)) digits = `0${digits}`;
  if (/^\d{2}[2-9]/.test(digits)) digits = `${digits.slice(0, 2)}0${digits.slice(2)}`;
  digits = digits.slice(0, 8);
  let out = digits.slice(0, 2);
  if (digits.length > 2) out += `.${digits.slice(2, 4)}`;
  if (digits.length > 4) out += `.${digits.slice(4)}`;
  if (/\D$/.test(text) && (digits.length === 2 || digits.length === 4)) out += '.';
  return out;
}

/** «1430» → «14:30», «930» → «09:30», «9:5» → «09:5». Время — только 24 часа. */
function maskTime(raw) {
  const text = String(raw || '');
  const groups = text.split(/\D+/);
  const separated = groups.length > 1;
  let digits = groups.join('');
  // Час одной цифрой: «9:30», «930» и «9» — это 09:30 и 09, а не 93.
  // Явно написанные «24:00» или «30:00» не переделываем — их подсветит проверка.
  if ((separated && groups[0].length === 1) || (/^[3-9]/.test(digits) && !(separated && groups[0].length === 2))) {
    digits = `0${digits}`;
  }
  digits = digits.slice(0, 4);
  let out = digits.slice(0, 2);
  if (digits.length > 2) out += `:${digits.slice(2)}`;
  if (/\D$/.test(text) && digits.length === 2) out += ':';
  return out;
}

/** После ввода: «25.12.26» → «25.12.2026», «1.2.2027» → «01.02.2027». Неполную дату не трогаем — её подсветит проверка. */
function normalizeDateText(value) {
  const raw = String(value || '').trim();
  // Стёрли день или месяц и ушли с поля («.12.2026», «25..2026», «25.2026»): цифры не переставляем,
  // иначе месяц или год «съедут» на чужое место. Проверка попросит дописать дату.
  if (/^\.|\.\s*\.|^\d{1,2}\.\d{4}$/.test(raw)) return raw;
  const text = maskDate(raw);
  const short = /^(\d{2})\.(\d{2})\.(\d{2})$/.exec(text);
  return short ? `${short[1]}.${short[2]}.20${short[3]}` : text;
}

/** «9» → «09:00», «14» и «14:» → «14:00». */
function normalizeTimeText(value) {
  const raw = String(value || '').trim();
  // Стёрли часы и ушли с поля («:30») — так и оставляем, проверка попросит дописать.
  if (/^:|:\s*:/.test(raw)) return raw;
  const text = maskTime(raw);
  const hours = /^(\d{2}):?$/.exec(text);
  return hours ? `${hours[1]}:00` : text;
}

/** «25.12.2026» → { iso: '2026-12-25', day: Date }; null — если такой даты нет. */
function parseDateText(text) {
  const m = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(text || '');
  if (!m) return null;
  const [day, month, year] = [Number(m[1]), Number(m[2]), Number(m[3])];
  const d = new Date(year, month - 1, day);
  if (d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) return null;
  return { iso: `${m[3]}-${m[2]}-${m[1]}`, day: d };
}

function isoInYears(years) {
  const d = new Date();
  d.setFullYear(d.getFullYear() + years);
  return isoDate(d);
}

/** Что не так с датой заявки; пустая строка — всё в порядке (дата необязательна). */
function dateProblem(value) {
  const text = normalizeDateText(value);
  if (!text) return '';
  if (!/^\d{2}\.\d{2}\.\d{4}$/.test(text)) return 'Введите дату полностью: дд.мм.гггг';
  const parsed = parseDateText(text);
  if (!parsed) return 'Такой даты нет — проверьте день и месяц';
  if (parsed.iso < todayIso()) return 'Эта дата уже прошла — выберите сегодня или позже';
  if (parsed.iso > isoInYears(2)) return 'Проверьте год: дата больше чем через два года';
  return '';
}

function timeProblem(value) {
  const text = normalizeTimeText(value);
  if (!text) return '';
  const m = /^(\d{2}):(\d{2})$/.exec(text);
  if (!m || Number(m[1]) > 23 || Number(m[2]) > 59) return 'Укажите время как чч:мм, например 08:30';
  return '';
}

/** Для текста заявки: «25.12.2026 (пятница)». */
function describeDate(value) {
  const text = normalizeDateText(value);
  const parsed = parseDateText(text);
  return parsed ? `${text} (${WEEKDAYS[parsed.day.getDay()]})` : text;
}

/** Дописываем сокращения перед отправкой: «25.12.26» → «25.12.2026», «9» → «09:00». */
function normalizeDateTimeFields(root) {
  $$('[data-dt]', root).forEach((wrap) => {
    const text = $('[data-dt-text]', wrap);
    if (!text) return;
    const next = (wrap.dataset.dt === 'time' ? normalizeTimeText : normalizeDateText)(text.value);
    if (next !== text.value) text.value = next;
  });
}

/** Календарь не даёт выбрать прошедший день и даты дальше двух лет (сегодня — по часам посетителя). */
function limitDatePickers(root) {
  const min = todayIso();
  const max = isoInYears(2);
  $$('input[type="date"][min]', root).forEach((input) => {
    input.min = min;
    input.max = max;
  });
}

/**
 * Поля даты и времени (разметка — dt_input() в PHP). Главное — текстовое поле: его значение уходит в заявку.
 * Значок справа — прозрачное встроенное поле type="date|time": открывает календарь или выбор времени,
 * а выбранное значение переносится в текст. Встроенное поле само ничего не показывает — поэтому
 * формат «месяц/день/год» из настроек английского браузера на экран не попадает.
 */
function initDateTimeInputs() {
  const coarse = window.matchMedia('(pointer: coarse)');
  $$('[data-dt]').forEach((wrap) => {
    const isTime = wrap.dataset.dt === 'time';
    const text = $('[data-dt-text]', wrap);
    const native = $('[data-dt-native]', wrap);
    if (!text) return;
    const mask = isTime ? maskTime : maskDate;
    const normalize = isTime ? normalizeTimeText : normalizeDateText;
    const extra = isTime ? /[^\d:]/g : /[^\d.]/g;

    text.addEventListener('input', (event) => {
      // Значение из календаря или вставки уже приведено к виду — его не трогаем.
      if (!event.isTrusted) return;
      // Стирание не переделываем — иначе стёртая точка тут же вернётся.
      if (typeof event.inputType === 'string' && event.inputType.startsWith('delete')) return;
      const raw = text.value;
      const caret = text.selectionStart ?? raw.length;
      if (caret < raw.length && document.activeElement === text) {
        // Правят середину — день, месяц или час. Цифры не переставляем и нули не дописываем:
        // иначе «1» в «1:30» сразу станет «01:30», и вторая цифра уже не поместится в поле.
        // Убираем только лишние знаки; нули допишутся, когда человек уйдёт с поля.
        const clean = raw.replace(extra, '');
        if (clean === raw) return;
        const pos = raw.slice(0, caret).replace(extra, '').length;
        text.value = clean;
        text.setSelectionRange(pos, pos);
        return;
      }
      const next = mask(raw);
      if (next !== raw) text.value = next;
    });
    text.addEventListener('change', () => {
      const next = normalize(text.value);
      if (next !== text.value) text.value = next;
    });
    // Готовое значение целиком: «18:30», «25.12.2026».
    const complete = isTime ? /^\d{2}:\d{2}$/ : /^\d{2}\.\d{2}\.\d{4}$/;
    // Вставка, перетаскивание мышью, подсказка клавиатуры или диктовка. Разбираем сами: браузер обрезал бы
    // текст по длине поля раньше, чем уберутся пробелы и слова (« 25.12.2026», «в 18:30», «25 декабря 2026»).
    const insert = (fragment) => {
      const piece = String(fragment || '').trim();
      if (!piece) return;
      // Целая дата или время заменяет старое значение: если вклеить «18:30» в середину «14:30»,
      // получится чужое время. Для времени дописанные «:00» не в счёт — «18» поверх часа меняет только час.
      let next = isTime ? mask(piece) : normalize(piece);
      if (!complete.test(next)) {
        const start = text.selectionStart ?? text.value.length;
        const end = text.selectionEnd ?? start;
        const limit = text.maxLength > 0 ? text.maxLength : Infinity;
        // Поле заполнено и ничего не выделено — как и сам браузер, ничего не добавляем.
        if (start === end && text.value.length >= limit) return;
        next = normalize(`${text.value.slice(0, start)}${piece}${text.value.slice(end)}`).slice(0, limit);
      }
      if (next === text.value) return;
      text.value = next;
      text.dispatchEvent(new Event('input', { bubbles: true }));
    };
    text.addEventListener('paste', (event) => {
      const pasted = event.clipboardData?.getData('text');
      if (!pasted) return;
      event.preventDefault();
      insert(pasted);
    });
    text.addEventListener('beforeinput', (event) => {
      // Текст целиком (перетащили мышью, подсказка клавиатуры, диктовка) разбираем так же, как вставку.
      // Обычные нажатия клавиш — по одному знаку — идут своим путём, через 'input'.
      if (event.isComposing || !event.cancelable) return;
      const type = event.inputType;
      if (type !== 'insertFromDrop' && type !== 'insertText' && type !== 'insertReplacementText') return;
      const piece = event.data ?? event.dataTransfer?.getData('text/plain') ?? '';
      if (piece.length < 2) return;
      event.preventDefault();
      insert(piece);
    });

    if (!native) return;
    // Календарь открывается на уже введённой дате (или на сегодняшней, если дата не введена).
    const syncNative = () => {
      const value = normalize(text.value);
      if (isTime) native.value = value && !timeProblem(value) ? value : '';
      else native.value = parseDateText(value)?.iso || '';
    };
    const canShowPicker = typeof native.showPicker === 'function';
    native.addEventListener('pointerdown', syncNative);
    native.addEventListener('focus', syncNative);
    native.addEventListener('mousedown', (event) => {
      // На компьютере курсор ставим в текстовое поле: можно и выбрать в календаре, и допечатать.
      if (!coarse.matches && canShowPicker) event.preventDefault();
    });
    native.addEventListener('click', (event) => {
      // На телефоне встроенный выбор открывается сам — от касания (и без экранной клавиатуры).
      if (coarse.matches || !canShowPicker) return;
      try {
        syncNative();
        text.focus({ preventScroll: true });
        native.showPicker();
        event.preventDefault();
      } catch (error) {
        /* браузер откроет выбор сам */
      }
    });
    const pick = (event) => {
      event.stopPropagation();
      const value = native.value;
      const next = value ? (isTime ? value.slice(0, 5) : maskDate(value)) : '';
      if (next === text.value) return;
      text.value = next;
      // Сообщаем форме, что поле изменилось: проверка снимет ошибку, если дата теперь верная.
      text.dispatchEvent(new Event('input', { bubbles: true }));
    };
    native.addEventListener('input', pick);
    native.addEventListener('change', pick);
  });
}

/* ---------- Уведомления (тосты) ---------- */

const toastBox = $('#toasts');

/**
 * Открытое окно (заявка, выбор мессенджера) перекрывает всю страницу — даже то, что поверх неё.
 * Поэтому уведомления на это время переносим внутрь окна, а после закрытия — обратно.
 * Возвращает true, если блок уведомлений пришлось перенести.
 */
function placeToasts() {
  const open = $$('dialog[open]');
  const host = open[open.length - 1] || document.body;
  if (toastBox.parentElement === host) return false;
  host.append(toastBox);
  return true;
}

function toast(message, type = 'info', timeout = 4500) {
  if (!toastBox) return;
  const moved = placeToasts();
  const el = document.createElement('div');
  el.className = `toast toast--${type}`;
  el.innerHTML = iconSvg(type === 'error' ? 'alert-circle' : type === 'success' ? 'check-circle' : 'info');
  const text = document.createElement('span');
  text.textContent = message;
  el.append(text);
  const remove = () => el.remove();
  const show = () => {
    toastBox.append(el);
    window.setTimeout(() => {
      if (reducedMotion.matches) {
        remove();
        return;
      }
      el.classList.add('is-leaving');
      el.addEventListener('animationend', remove, { once: true });
      window.setTimeout(remove, 400);
    }, timeout);
  };
  // После переноса даём программам для незрячих заметить блок, иначе они могут не прочитать сообщение.
  if (moved) window.setTimeout(show, 80);
  else show();
}

/* ---------- Копирование в буфер обмена ---------- */

async function copyText(text, container = document.body) {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return true;
    }
  } catch (error) {
    /* попробуем запасной способ */
  }
  const area = document.createElement('textarea');
  area.value = text;
  area.setAttribute('readonly', '');
  area.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;';
  container.append(area);
  area.select();
  area.setSelectionRange(0, text.length);
  let ok = false;
  try {
    ok = document.execCommand('copy');
  } catch (error) {
    ok = false;
  }
  area.remove();
  return ok;
}

/* ---------- Окна (меню, заявка) ---------- */

function openDialog(dialog, opener) {
  if (!dialog || dialog.open) return;
  dialog.opener = opener || document.activeElement;
  dialog.classList.remove('is-closing');
  dialog.showModal();
}

function closeDialog(dialog, { instant = false, keep = false } = {}) {
  if (!dialog || !dialog.open) return;
  dialog.keepData = keep;
  const finish = () => {
    dialog.classList.remove('is-closing');
    if (dialog.open) dialog.close();
    const opener = dialog.opener;
    dialog.opener = null;
    if (!keep && opener && document.contains(opener) && typeof opener.focus === 'function') {
      opener.focus({ preventScroll: true });
    }
  };
  if (instant || reducedMotion.matches) {
    finish();
    return;
  }
  if (dialog.classList.contains('is-closing')) return;
  dialog.classList.add('is-closing');
  window.setTimeout(finish, 190);
}

function initDialogs() {
  $$('dialog').forEach((dialog) => {
    dialog.addEventListener('cancel', (event) => {
      event.preventDefault();
      closeDialog(dialog);
    });
    // Закрытие по нажатию на затемнённый фон (нажали и отпустили именно на фоне)
    let downOnBackdrop = false;
    dialog.addEventListener('pointerdown', (event) => {
      downOnBackdrop = event.target === dialog;
    });
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog && downOnBackdrop) closeDialog(dialog);
      downOnBackdrop = false;
    });
    $$('[data-dialog-close]', dialog).forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });
    // Окно закрылось — уведомления возвращаются на страницу и остаются видны.
    dialog.addEventListener('close', () => {
      if (toastBox && dialog.contains(toastBox)) document.body.append(toastBox);
    });
    initSheetDrag(dialog);
  });
}

/** Нижнюю шторку на телефоне можно смахнуть вниз за верхнюю часть. */
function initSheetDrag(dialog) {
  const panel = $('.sheet__panel', dialog);
  const handle = $('.sheet__header', dialog);
  if (!panel || !handle) return;
  let startY = 0;
  let delta = 0;
  let dragging = false;
  handle.addEventListener('pointerdown', (event) => {
    if (event.pointerType !== 'touch' || event.target.closest('button, a, input')) return;
    if (window.matchMedia('(min-width: 768px)').matches) return;
    dragging = true;
    startY = event.clientY;
    delta = 0;
    panel.style.transition = 'none';
    handle.setPointerCapture(event.pointerId);
  });
  handle.addEventListener('pointermove', (event) => {
    if (!dragging) return;
    delta = Math.max(0, event.clientY - startY);
    panel.style.transform = `translateY(${delta}px)`;
  });
  const end = () => {
    if (!dragging) return;
    dragging = false;
    panel.style.transition = '';
    panel.style.transform = '';
    if (delta > 110) closeDialog(dialog, { instant: true });
  };
  handle.addEventListener('pointerup', end);
  handle.addEventListener('pointercancel', end);
}

/* ---------- Шапка и мобильное меню ---------- */

function initHeader() {
  const header = $('#site-header');
  if (!header) return;
  let ticking = false;
  const update = () => {
    header.classList.toggle('is-scrolled', window.scrollY > 12);
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(update);
    }
  }, { passive: true });
  update();
}

function initMenu() {
  const menu = $('#menu');
  const burger = $('[data-menu-open]');
  if (!menu || !burger) return;
  burger.addEventListener('click', () => {
    openDialog(menu, burger);
    burger.setAttribute('aria-expanded', 'true');
  });
  menu.addEventListener('close', () => burger.setAttribute('aria-expanded', 'false'));
  $$('[data-menu-close]', menu).forEach((button) => button.addEventListener('click', () => closeDialog(menu)));
  $$('[data-menu-link]', menu).forEach((link) => {
    link.addEventListener('click', () => closeDialog(menu, { instant: true, keep: true }));
  });
  // Меню нужно только на узком экране: при повороте телефона или расширении окна закрываем его.
  window.matchMedia('(min-width: 1280px)').addEventListener('change', (event) => {
    if (event.matches) closeDialog(menu, { instant: true });
  });
}

/** Подсветка пункта меню того раздела, который сейчас на экране. */
function initScrollSpy() {
  const links = $$('.nav__link[data-nav]');
  if (!links.length || !('IntersectionObserver' in window)) return;
  const byId = new Map(links.map((link) => [link.dataset.nav, link]));
  const sections = [...byId.keys()].map((id) => document.getElementById(id)).filter(Boolean);
  if (!sections.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const link = byId.get(entry.target.id);
      if (!link) return;
      if (entry.isIntersecting) {
        links.forEach((l) => l.classList.toggle('is-active', l === link));
      } else if (link.classList.contains('is-active')) {
        link.classList.remove('is-active');
      }
    });
  }, { rootMargin: '-45% 0px -50% 0px' });
  sections.forEach((section) => observer.observe(section));
}

/* ---------- Появление блоков при прокрутке ---------- */

function initReveal() {
  const items = $$('[data-reveal]');
  if (!items.length || reducedMotion.matches || !('IntersectionObserver' in window)) return;
  // Сначала только измеряем (чтобы не было лишних перерасчётов страницы), потом меняем.
  const viewport = window.innerHeight;
  const onScreen = items.map((el) => {
    const rect = el.getBoundingClientRect();
    return rect.top < viewport && rect.bottom > 0;
  });
  const pending = [];
  items.forEach((el, i) => {
    if (onScreen[i]) el.classList.add('is-visible');
    else pending.push(el);
  });
  document.documentElement.classList.add('reveal-ready');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
  pending.forEach((el) => observer.observe(el));
}

/* ---------- Счётчики в цифрах о компании ---------- */

function initCounters() {
  const counters = $$('[data-count-to]');
  if (!counters.length || reducedMotion.matches || !('IntersectionObserver' in window)) return;
  const run = (el) => {
    const target = parseInt(el.dataset.countTo, 10);
    if (!Number.isFinite(target) || target <= 0) return;
    const duration = 1200;
    const start = performance.now();
    const tick = (now) => {
      const t = Math.min(1, (now - start) / duration);
      const eased = 1 - (1 - t) ** 3;
      el.textContent = numberFormat.format(Math.round(target * eased));
      if (t < 1) window.requestAnimationFrame(tick);
    };
    el.textContent = '0';
    window.requestAnimationFrame(tick);
  };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        observer.unobserve(entry.target);
        run(entry.target);
      }
    });
  }, { threshold: 0.6 });
  counters.forEach((el) => observer.observe(el));
}

/* ---------- Вопросы и ответы ---------- */

function initAccordions() {
  $$('[data-accordion]').forEach((accordion) => {
    $$('.accordion__trigger', accordion).forEach((trigger) => {
      trigger.addEventListener('click', () => {
        const item = trigger.closest('.accordion__item');
        const open = !item.classList.contains('is-open');
        item.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', String(open));
      });
    });
  });
}

/* ---------- Карусели фотографий ---------- */

function initCarousels() {
  $$('[data-carousel]').forEach((carousel) => {
    const track = $('.carousel__track', carousel);
    const slides = $$('.carousel__slide', carousel);
    if (!track || slides.length < 2) return;
    const prev = $('[data-carousel-prev]', carousel);
    const next = $('[data-carousel-next]', carousel);
    const dots = $$('[data-carousel-dot]', carousel);
    const counter = $('[data-carousel-count]', carousel);
    let index = -1;

    const go = (i) => {
      const target = Math.max(0, Math.min(slides.length - 1, i));
      track.scrollTo({ left: target * track.clientWidth, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
    };
    const update = () => {
      const i = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
      if (i === index) return;
      index = i;
      if (prev) prev.disabled = i <= 0;
      if (next) next.disabled = i >= slides.length - 1;
      dots.forEach((dot, n) => {
        if (n === i) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
      if (counter) counter.textContent = `${i + 1} / ${slides.length}`;
    };
    let frame = 0;
    track.addEventListener('scroll', () => {
      window.cancelAnimationFrame(frame);
      frame = window.requestAnimationFrame(update);
    }, { passive: true });
    prev?.addEventListener('click', () => go(index - 1));
    next?.addEventListener('click', () => go(index + 1));
    dots.forEach((dot, n) => dot.addEventListener('click', () => go(n)));
    track.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        go(index - 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        go(index + 1);
      }
    });
    update();
  });
}

/* ---------- Фильтр автопарка по количеству мест ---------- */

function initFleetFilter() {
  const filter = $('[data-fleet-filter]');
  const list = $('[data-fleet-list]');
  if (!filter || !list) return;
  const items = $$(':scope > li', list);
  const empty = $('[data-fleet-empty]');
  filter.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-filter]');
    if (!chip) return;
    const value = chip.dataset.filter;
    $$('[data-filter]', filter).forEach((c) => c.setAttribute('aria-pressed', String(c === chip)));
    let shown = 0;
    items.forEach((item) => {
      const match = value === 'all' || item.dataset.range === value;
      const wasHidden = item.classList.contains('is-hidden');
      item.classList.toggle('is-hidden', !match);
      if (match) {
        shown += 1;
        item.classList.add('is-visible');
        if (wasHidden && !reducedMotion.matches) {
          item.classList.remove('is-entering');
          void item.offsetWidth; // перезапуск анимации
          item.classList.add('is-entering');
        }
      }
    });
    if (empty) empty.hidden = shown > 0;
  });
}

/* ---------- Карта: загружается по нажатию «Показать карту» ---------- */

function initMaps() {
  let warmed = false;
  const warm = () => {
    if (warmed) return;
    warmed = true;
    const link = document.createElement('link');
    link.rel = 'preconnect';
    link.href = 'https://yandex.ru';
    document.head.append(link);
  };
  $$('[data-map]').forEach((box) => {
    const button = $('[data-map-load]', box);
    if (!button) return;
    box.addEventListener('pointerenter', warm, { once: true });
    box.addEventListener('touchstart', warm, { once: true, passive: true });
    button.addEventListener('focus', warm, { once: true });
    button.addEventListener('click', () => {
      if (box.dataset.loaded) return;
      box.dataset.loaded = '1';
      const frame = document.createElement('iframe');
      frame.className = 'map__frame';
      frame.src = box.dataset.src;
      frame.title = box.dataset.title || 'Карта';
      frame.allowFullscreen = true;
      frame.addEventListener('load', () => {
        frame.classList.add('is-loaded');
        box.classList.add('is-loaded');
      }, { once: true });
      box.classList.add('is-loading');
      box.append(frame);
    });
  });
}

/* ---------- Поля: счётчик «− 25 +» и маска телефона ---------- */

function initSteppers() {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-step]');
    if (!button) return;
    const stepper = button.closest('[data-stepper]');
    const input = stepper && $('input', stepper);
    if (!input) return;
    const step = Number(button.dataset.step) || 1;
    const min = input.min !== '' ? Number(input.min) : 1;
    const max = input.max !== '' ? Number(input.max) : 9999;
    const current = input.value === '' ? (step > 0 ? min - 1 : min) : Number(input.value);
    const value = Math.max(min, Math.min(max, (Number.isFinite(current) ? current : min) + step));
    input.value = String(value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

function phoneDigits(value) {
  let digits = String(value || '').replace(/\D/g, '');
  if (digits === '') return '';
  if (digits[0] === '8') digits = `7${digits.slice(1)}`;
  else if (digits[0] !== '7') digits = `7${digits}`;
  return digits.slice(0, 11);
}

function formatPhone(digits) {
  if (digits === '') return '';
  const rest = digits.slice(1);
  let out = '+7';
  if (rest.length > 0) out += ` (${rest.slice(0, 3)}`;
  if (rest.length >= 3) out += ')';
  if (rest.length > 3) out += ` ${rest.slice(3, 6)}`;
  if (rest.length > 6) out += `-${rest.slice(6, 8)}`;
  if (rest.length > 8) out += `-${rest.slice(8, 10)}`;
  return out;
}

function initPhoneMasks() {
  $$('[data-phone-mask]').forEach((input) => {
    let previous = input.value;
    input.addEventListener('focus', () => {
      if (input.value === '') {
        input.value = '+7 (';
        previous = input.value;
      }
    });
    input.addEventListener('blur', () => {
      if (phoneDigits(input.value).length <= 1) {
        input.value = '';
        previous = '';
      }
    });
    input.addEventListener('input', (event) => {
      let digits = phoneDigits(input.value);
      const deleting = typeof event.inputType === 'string' && event.inputType.startsWith('delete');
      // Стёрли скобку или дефис — стираем и цифру перед ней, иначе знак сразу вернётся.
      if (deleting && digits === phoneDigits(previous) && digits.length > 1) {
        digits = digits.slice(0, -1);
      }
      input.value = deleting && digits.length <= 1 ? (digits ? '+7 (' : '') : formatPhone(digits);
      previous = input.value;
    });
  });
}

/* ---------- Проверка полей формы ---------- */

function setFieldError(form, key, message) {
  const field = $(`[data-field="${key}"]`, form);
  if (!field) return;
  field.classList.toggle('is-invalid', Boolean(message));
  const control = $('input:not([type="radio"]), select, textarea', field);
  if (control) {
    if (message) control.setAttribute('aria-invalid', 'true');
    else control.removeAttribute('aria-invalid');
  }
  const text = $('.field__error span', field);
  if (text) text.textContent = message || '';
}

function validateContactForm(form, only = null) {
  const errors = {};
  const el = form.elements;
  if (el.name) {
    const name = el.name.value.trim();
    if (name.length < 2) errors.name = 'Напишите, как к вам обращаться';
  }
  if (el.phone) {
    const digits = phoneDigits(el.phone.value);
    if (digits.length <= 1) errors.phone = 'Укажите телефон — диспетчер перезвонит или ответит в мессенджере';
    else if (digits.length !== 11) errors.phone = 'Номер неполный: нужно +7 и ещё 10 цифр';
    else if (!/^7[3-9]/.test(digits)) errors.phone = 'Проверьте номер: после +7 обычно идёт 9 или код города';
  }
  if (el.date) {
    const problem = dateProblem(el.date.value);
    if (problem) errors.date = problem;
  }
  if (el.time) {
    const problem = timeProblem(el.time.value);
    if (problem) errors.time = problem;
  }
  if (el.passengers && el.passengers.value !== '') {
    const n = Number(el.passengers.value);
    if (!Number.isInteger(n) || n < 1 || n > 999) errors.passengers = 'Количество пассажиров — от 1 до 999';
  }
  const keys = only ? [only] : ['name', 'phone', 'date', 'time', 'passengers'];
  keys.forEach((key) => setFieldError(form, key, errors[key]));
  return only ? (errors[only] ? { [only]: errors[only] } : {}) : errors;
}

function focusFirstError(form, errors) {
  const order = ['date', 'time', 'passengers', 'name', 'phone'];
  const first = order.find((key) => errors[key]);
  const field = first && $(`[data-field="${first}"]`, form);
  const control = field && $('input, select, textarea', field);
  if (control) {
    control.focus({ preventScroll: true });
    field.scrollIntoView({ block: 'center', behavior: reducedMotion.matches ? 'auto' : 'smooth' });
  }
}

/** Ошибки показываем после первой попытки отправки, а исправленные поля сразу снимаем с ошибки. */
function attachLiveValidation(form) {
  form.addEventListener('input', (event) => {
    const field = event.target.closest('[data-field]');
    if (field && field.classList.contains('is-invalid')) validateContactForm(form, field.dataset.field);
  });
  form.addEventListener('focusout', (event) => {
    const field = event.target.closest('[data-field]');
    if (!field || !form.dataset.touched) return;
    validateContactForm(form, field.dataset.field);
  });
}

function resetForm(form) {
  if (!form) return;
  form.reset();
  // reset() не трогает скрытые поля (например, расчёт из калькулятора) — очищаем их сами.
  $$('input[type="hidden"]', form).forEach((input) => { input.value = ''; });
  delete form.dataset.touched;
  $$('[data-field]', form).forEach((field) => setFieldError(form, field.dataset.field, ''));
  limitDatePickers(form);
}

/* ---------- Заявка: сбор текста и отправка через мессенджер ---------- */

const requestDialog = $('#request-dialog');
const requestForm = $('#request-form');
const inspectionDialog = $('#inspection-dialog');
const inspectionForm = $('#inspection-form');
const sendDialog = $('#send-dialog');
let lastMessage = '';
let lastSource = null;
let currentEstimate = null;

function composeRequestMessage(form) {
  const el = form.elements;
  const value = (name) => (el[name] ? String(el[name].value || '').trim() : '');
  const lines = ['Здравствуйте! Хочу заказать транспорт.'];
  const trip = form.querySelector('input[name="trip"]:checked');
  if (trip) lines.push(`Поездка: ${trip.value}`);
  const when = [describeDate(value('date')), normalizeTimeText(value('time'))].filter(Boolean).join(', ');
  if (when) lines.push(`Дата: ${when}`);
  if (value('from')) lines.push(`Откуда: ${value('from')}`);
  if (value('to')) lines.push(`Куда: ${value('to')}`);
  if (value('passengers')) lines.push(`Пассажиров: ${value('passengers')}`);
  if (el.vehicle && el.vehicle.value) lines.push(`Транспорт: ${el.vehicle.selectedOptions[0].textContent.trim()}`);
  if (value('estimate')) lines.push(`Расчёт на сайте: ${value('estimate')}`);
  if (value('comment')) lines.push(`Комментарий: ${value('comment')}`);
  lines.push(`Имя: ${value('name')}`);
  lines.push(`Телефон: ${value('phone')}`);
  return lines.join('\n');
}

function composeInspectionMessage(form) {
  const el = form.elements;
  const value = (name) => (el[name] ? String(el[name].value || '').trim() : '');
  const lines = ['Здравствуйте! Хочу записаться на техосмотр.'];
  if (value('category')) lines.push(`Категория: ${value('category')}`);
  const when = [describeDate(value('date')), normalizeTimeText(value('time'))].filter(Boolean).join(', ');
  if (when) lines.push(`Дата: ${when}`);
  if (value('car')) lines.push(`Автомобиль: ${value('car')}`);
  if (value('comment')) lines.push(`Комментарий: ${value('comment')}`);
  lines.push(`Имя: ${value('name')}`);
  lines.push(`Телефон: ${value('phone')}`);
  return lines.join('\n');
}

function openRequest(prefill = {}) {
  if (!requestDialog || !requestForm) return;
  resetForm(requestForm);
  const el = requestForm.elements;
  if (prefill.trip) {
    const radio = $$('input[name="trip"]', requestForm).find((r) => r.value === prefill.trip);
    if (radio) radio.checked = true;
  }
  if (prefill.vehicle && el.vehicle) {
    el.vehicle.value = String(prefill.vehicle);
    if (el.vehicle.value !== String(prefill.vehicle)) el.vehicle.value = '';
  }
  ['date', 'from', 'to', 'passengers'].forEach((name) => {
    if (prefill[name] && el[name]) el[name].value = prefill[name];
  });
  // Дату с первого экрана сразу проверяем: неполную или прошедшую человек увидит до отправки.
  if (prefill.date) validateContactForm(requestForm, 'date');
  if (el.estimate) el.estimate.value = prefill.estimate || '';
  openDialog(requestDialog, prefill.opener);
}

const MAX_PREFILL_URL = 6000;

/** Ссылка на чат с готовым текстом (пробелы — %20, а не «+»: так текст правильно откроется на iPhone). */
function messengerUrl(messenger, message) {
  if (!canPrefill(messenger, message)) return messenger.url;
  const glue = messenger.url.includes('?') ? '&' : '?';
  return `${messenger.url}${glue}${messenger.param}=${encodeURIComponent(message)}`;
}

/** Очень длинный текст в ссылку не помещается — тогда, как и для MAX, копируем его в буфер обмена. */
function canPrefill(messenger, message) {
  return Boolean(messenger.prefill && messenger.param)
    && messenger.url.length + encodeURIComponent(message).length < MAX_PREFILL_URL;
}

function openSend(message, source, opener) {
  if (!sendDialog) return;
  lastMessage = message;
  lastSource = source;
  const preview = $('#send-preview', sendDialog);
  if (preview) preview.textContent = message;
  const details = $('.message-details', sendDialog);
  if (details) details.open = false;
  $$('[data-send]', sendDialog).forEach((link) => {
    const key = link.dataset.send;
    const messenger = config.messengers.find((m) => m.key === key);
    if (messenger) link.href = messengerUrl(messenger, message);
    if (key === 'email' && config.email) {
      const subject = source === 'inspection' ? 'Запись на техосмотр' : 'Заявка на транспорт';
      link.href = `mailto:${config.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`;
    }
  });
  openDialog(sendDialog, opener);
}

function reachGoal(name) {
  if (typeof window.ym === 'function' && config.metrika) {
    try {
      window.ym(Number(config.metrika), 'reachGoal', name);
    } catch (error) {
      /* статистика не должна мешать заявке */
    }
  }
}

function handleSubmit(form, dialog, source, compose) {
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    form.dataset.touched = '1';
    normalizeDateTimeFields(form);
    const errors = validateContactForm(form);
    if (Object.keys(errors).length) {
      focusFirstError(form, errors);
      return;
    }
    const message = compose(form);
    closeDialog(dialog, { instant: true, keep: true });
    openSend(message, source, dialog.opener);
    reachGoal(source === 'inspection' ? 'inspection_form' : 'request_form');
  });
  attachLiveValidation(form);
  // Форма ничего не запоминает: закрыли окно — данные стёрлись.
  dialog.addEventListener('close', () => {
    if (!dialog.keepData) resetForm(form);
    dialog.keepData = false;
  });
}

function initRequest() {
  // Кнопки «Заказать», «Выбрать», «Написать» и т. п. открывают заявку.
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-open-request]');
    if (trigger && requestDialog) {
      event.preventDefault();
      const prefill = { opener: trigger };
      if (trigger.dataset.trip) prefill.trip = trigger.dataset.trip;
      if (trigger.dataset.vehicle) prefill.vehicle = trigger.dataset.vehicle;
      if (trigger.hasAttribute('data-calc-order') && currentEstimate) {
        prefill.vehicle = currentEstimate.vehicleId;
        prefill.estimate = currentEstimate.text;
      }
      openRequest(prefill);
      return;
    }
    const inspection = event.target.closest('[data-open-inspection]');
    if (inspection && inspectionDialog) {
      event.preventDefault();
      resetForm(inspectionForm);
      openDialog(inspectionDialog, inspection);
    }
  });

  // Быстрая форма на первом экране открывает полную заявку с уже введёнными данными.
  const quick = $('#quick-form');
  quick?.addEventListener('submit', (event) => {
    event.preventDefault();
    const el = quick.elements;
    normalizeDateTimeFields(quick);
    openRequest({
      date: el.date.value.trim(),
      from: el.from.value.trim(),
      to: el.to.value.trim(),
      passengers: el.passengers.value,
      opener: $('[type="submit"]', quick),
    });
  });

  if (requestForm && requestDialog) handleSubmit(requestForm, requestDialog, 'request', composeRequestMessage);
  if (inspectionForm && inspectionDialog) handleSubmit(inspectionForm, inspectionDialog, 'inspection', composeInspectionMessage);

  if (!sendDialog) return;
  sendDialog.addEventListener('click', (event) => {
    const link = event.target.closest('[data-send]');
    if (!link) return;
    const key = link.dataset.send;
    const messenger = config.messengers.find((m) => m.key === key);
    if (messenger && !canPrefill(messenger, lastMessage)) {
      // Этот мессенджер не умеет принимать готовый текст в ссылке — копируем текст заранее.
      copyText(lastMessage, sendDialog).then((ok) => {
        toast(ok ? 'Текст заявки скопирован — вставьте его в чат' : 'Не удалось скопировать текст. Нажмите «Показать текст заявки» и скопируйте его вручную.', ok ? 'success' : 'error', 7000);
      });
    }
    reachGoal(`send_${key}`);
  });

  $('[data-copy-message]', sendDialog)?.addEventListener('click', () => {
    copyText(lastMessage, sendDialog).then((ok) => {
      toast(ok ? 'Текст заявки скопирован' : 'Не получилось скопировать — выделите текст вручную', ok ? 'success' : 'error');
      if (!ok) {
        const details = $('.message-details', sendDialog);
        if (details) details.open = true;
      }
    });
  });

  $('[data-send-back]', sendDialog)?.addEventListener('click', () => {
    const back = lastSource === 'inspection' ? inspectionDialog : requestDialog;
    const opener = sendDialog.opener;
    closeDialog(sendDialog, { instant: true, keep: true });
    if (back) openDialog(back, opener);
  });

  // Окно отправки закрыли — заявка больше не нужна, стираем все данные.
  sendDialog.addEventListener('close', () => {
    if (sendDialog.keepData) {
      sendDialog.keepData = false;
      return;
    }
    lastMessage = '';
    const preview = $('#send-preview', sendDialog);
    if (preview) preview.textContent = '';
    resetForm(requestForm);
    resetForm(inspectionForm);
  });
}

/* ---------- Калькулятор ---------- */

function calcModes(v) {
  const modes = [];
  if (v.hour > 0) modes.push('hour');
  if (v.km > 0) modes.push('km');
  if (v.transfer > 0) modes.push('transfer');
  return modes;
}

function calcEstimate(v, mode, amount) {
  const lines = [];
  let total = 0;
  if (mode === 'hour' && v.hour > 0) {
    const hours = Math.max(Math.max(1, v.minHours || 1), amount);
    total = hours * v.hour;
    lines.push([`${hours} ${plural(hours, 'час', 'часа', 'часов')} × ${money(v.hour)}`, money(total)]);
  } else if (mode === 'km' && v.km > 0) {
    const km = Math.max(1, amount);
    total = km * v.km;
    lines.push([`${km} км × ${money(v.km)}`, money(total)]);
  } else {
    total = v.transfer || 0;
    lines.push(['Трансфер', money(total)]);
  }
  if (v.delivery > 0) {
    total += v.delivery;
    lines.push(['Подача транспорта', money(v.delivery)]);
  }
  return { total, lines };
}

function initCalculator() {
  const form = $('#calc');
  if (!form || !config.calculator.length) return;
  const el = form.elements;
  const modeInputs = $$('input[name="mode"]', form);
  const totalEl = $('[data-calc-total]', form);
  const breakdown = $('[data-calc-breakdown]', form);
  const minHint = $('[data-calc-min-hours]', form);

  const render = () => {
    const vehicle = config.calculator.find((v) => String(v.id) === el.vehicle.value) || config.calculator[0];
    const modes = calcModes(vehicle);
    modeInputs.forEach((input) => { input.disabled = !modes.includes(input.value); });
    let mode = modeInputs.find((input) => input.checked)?.value;
    if (!modes.includes(mode)) {
      mode = modes[0] || 'transfer';
      modeInputs.forEach((input) => { input.checked = input.value === mode; });
    }
    $$('[data-calc-show]', form).forEach((block) => { block.hidden = block.dataset.calcShow !== mode; });

    const minHours = Math.max(1, vehicle.minHours || 1);
    el.hours.min = String(minHours);
    if (el.hours.value !== '' && Number(el.hours.value) < minHours) el.hours.value = String(minHours);
    if (minHint) minHint.textContent = minHours > 1 ? `Минимальный заказ — ${minHours} ${plural(minHours, 'час', 'часа', 'часов')}.` : '';

    const amount = mode === 'km' ? Math.max(1, Math.round(Number(el.km.value) || 1)) : Math.max(1, Math.round(Number(el.hours.value) || minHours));
    const result = calcEstimate(vehicle, mode, amount);
    totalEl.textContent = `от ${money(result.total)}`;
    breakdown.replaceChildren(...result.lines.map(([label, sum]) => {
      const li = document.createElement('li');
      const a = document.createElement('span');
      const b = document.createElement('span');
      a.textContent = label;
      b.textContent = sum;
      li.append(a, b);
      return li;
    }));
    currentEstimate = {
      vehicleId: vehicle.id,
      text: `${result.lines[0][0]}${vehicle.delivery > 0 ? ' + подача' : ''} — от ${money(result.total)}`,
    };
  };

  form.addEventListener('input', render);
  form.addEventListener('change', render);
  form.addEventListener('submit', (event) => event.preventDefault());
  render();
}

/* ---------- Яндекс Метрика (только с согласия посетителя) ---------- */

function loadMetrika(id) {
  if (window.ym) return;
  window.ym = function ym() {
    (window.ym.a = window.ym.a || []).push(arguments);
  };
  window.ym.l = Date.now();
  const script = document.createElement('script');
  script.async = true;
  script.src = `https://mc.yandex.ru/metrika/tag.js?id=${encodeURIComponent(id)}`;
  document.head.append(script);
  window.ym(Number(id), 'init', {
    ssr: true,
    clickmap: true,
    trackLinks: true,
    accurateTrackBounce: true,
    webvisor: false,
  });
}

function initMetrika() {
  if (!config.metrika) return;
  const key = 'bst-cookie-consent';
  let choice = null;
  try {
    choice = window.localStorage.getItem(key);
  } catch (error) {
    choice = null;
  }
  const note = $('#cookie-note');
  if (!config.cookieNotice || choice === 'yes') {
    loadMetrika(config.metrika);
    return;
  }
  if (choice === 'no' || !note) return;
  note.hidden = false;
  note.addEventListener('click', (event) => {
    const button = event.target.closest('[data-cookie-choice]');
    if (!button) return;
    const value = button.dataset.cookieChoice === 'yes' ? 'yes' : 'no';
    try {
      window.localStorage.setItem(key, value);
    } catch (error) {
      /* в приватном режиме выбор просто не запомнится */
    }
    note.hidden = true;
    if (value === 'yes') loadMetrika(config.metrika);
  });
}

/* ---------- Образец оформления ---------- */

function initDemo() {
  $('[data-demo-toast]')?.addEventListener('click', () => toast('Текст заявки скопирован — вставьте его в чат', 'success'));
}

/* ---------- Запуск ---------- */

function init() {
  limitDatePickers(document);
  initDateTimeInputs();
  initDialogs();
  initHeader();
  initMenu();
  initReveal();
  initScrollSpy();
  initCounters();
  initAccordions();
  initCarousels();
  initFleetFilter();
  initMaps();
  initSteppers();
  initPhoneMasks();
  initRequest();
  initCalculator();
  initMetrika();
  initDemo();
}

init();
