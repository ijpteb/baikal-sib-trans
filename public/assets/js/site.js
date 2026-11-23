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

function todayIso() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function formatDate(iso) {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
  return m ? `${m[3]}.${m[2]}.${m[1]}` : '';
}

/* ---------- Уведомления (тосты) ---------- */

const toastBox = $('#toasts');

function toast(message, type = 'info', timeout = 4500) {
  if (!toastBox) return;
  const el = document.createElement('div');
  el.className = `toast toast--${type}`;
  el.innerHTML = iconSvg(type === 'error' ? 'alert-circle' : type === 'success' ? 'check-circle' : 'info');
  const text = document.createElement('span');
  text.textContent = message;
  el.append(text);
  toastBox.append(el);
  const remove = () => el.remove();
  window.setTimeout(() => {
    if (reducedMotion.matches) {
      remove();
      return;
    }
    el.classList.add('is-leaving');
    el.addEventListener('animationend', remove, { once: true });
    window.setTimeout(remove, 400);
  }, timeout);
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
  window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
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
  if (el.date && el.date.value && el.date.value < todayIso()) {
    errors.date = 'Эта дата уже прошла — выберите сегодня или позже';
  }
  if (el.passengers && el.passengers.value !== '') {
    const n = Number(el.passengers.value);
    if (!Number.isInteger(n) || n < 1 || n > 999) errors.passengers = 'Количество пассажиров — от 1 до 999';
  }
  const keys = only ? [only] : ['name', 'phone', 'date', 'passengers'];
  keys.forEach((key) => setFieldError(form, key, errors[key]));
  return only ? (errors[only] ? { [only]: errors[only] } : {}) : errors;
}

function focusFirstError(form, errors) {
  const order = ['date', 'passengers', 'name', 'phone'];
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
  delete form.dataset.touched;
  $$('[data-field]', form).forEach((field) => setFieldError(form, field.dataset.field, ''));
  const today = todayIso();
  $$('input[type="date"]', form).forEach((input) => { input.min = today; });
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
  const date = formatDate(value('date'));
  const when = [date, value('time')].filter(Boolean).join(', ');
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
  const when = [formatDate(value('date')), value('time')].filter(Boolean).join(', ');
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
    openRequest({
      date: el.date.value,
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
  const today = todayIso();
  $$('input[type="date"]').forEach((input) => { input.min = today; });
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
