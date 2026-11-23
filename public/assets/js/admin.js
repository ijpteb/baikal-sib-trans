/*
 * Скрипт админки «Байкал Сиб-Транс».
 * Меню на телефоне, подтверждение удаления, предупреждение о несохранённых изменениях,
 * счётчики символов, показ пароля, загрузка картинок и фото машины,
 * порядок записей и переключатель «На сайте» без перезагрузки страницы.
 * Без скрипта админка тоже работает — просто чуть менее удобно.
 */

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

let config = {};
try {
  config = JSON.parse($('#admin-config')?.textContent || '{}');
} catch (error) {
  config = {};
}
const csrfToken = String(config.csrf || '');
const maxUpload = Number(config.maxUpload) > 0 ? Number(config.maxUpload) : 15 * 1024 * 1024;

/** Сколько картинок загружается прямо сейчас: пока идёт загрузка, форму не отправляем. */
let uploadsInProgress = 0;

/* ---------- Мелочи ---------- */

function iconSvg(name) {
  return `<svg class="icon" width="24" height="24" aria-hidden="true" focusable="false"><use href="#i-${name}"></use></svg>`;
}

function formatBytes(bytes) {
  if (bytes >= 1024 * 1024) {
    const mb = bytes / 1024 / 1024;
    return `${String(mb >= 10 ? Math.round(mb) : Math.round(mb * 10) / 10).replace('.', ',')} МБ`;
  }
  return `${Math.max(1, Math.round(bytes / 1024))} КБ`;
}

/** Сообщить об изменении форме (для предупреждения о несохранённых изменениях). */
function notifyChanged(element) {
  element.dispatchEvent(new CustomEvent('admin:changed', { bubbles: true }));
}

/* ---------- Объявления для экранного диктора ---------- */

const announcer = document.createElement('div');
announcer.className = 'visually-hidden';
announcer.setAttribute('aria-live', 'polite');
document.body.append(announcer);

function announce(message) {
  announcer.textContent = '';
  window.setTimeout(() => {
    announcer.textContent = message;
  }, 80);
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

/* ---------- Запросы к сайту ---------- */

async function postForm(url, body) {
  if (!body.has('_csrf')) body.append('_csrf', csrfToken);
  let response;
  try {
    response = await fetch(url, {
      method: 'POST',
      body,
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
    });
  } catch (error) {
    throw new Error('Нет связи с сайтом. Проверьте интернет и попробуйте ещё раз.');
  }
  let data = null;
  try {
    data = await response.json();
  } catch (error) {
    data = null;
  }
  if (response.ok && data && data.ok !== false) return data;
  if (data && typeof data.error === 'string' && data.error) throw new Error(data.error);
  if (response.status === 413) {
    throw new Error('Файл слишком большой для хостинга. Уменьшите его и попробуйте ещё раз.');
  }
  throw new Error('Не получилось. Обновите страницу и попробуйте ещё раз.');
}

function sizeProblem(file) {
  if (file.size === 0) return `Файл «${file.name}» пустой — выберите другой.`;
  if (file.size > maxUpload) {
    return `Файл «${file.name}» весит ${formatBytes(file.size)}, а можно не больше ${formatBytes(maxUpload)}. `
      + 'Уменьшите фото (например, перешлите его себе в мессенджере и сохраните оттуда) и попробуйте ещё раз.';
  }
  return '';
}

async function uploadImage(file, purpose) {
  const body = new FormData();
  body.append('file', file);
  body.append('purpose', purpose);
  uploadsInProgress += 1;
  try {
    const data = await postForm('/admin/upload', body);
    if (!data.token || !data.preview) throw new Error('Сайт не вернул картинку. Попробуйте ещё раз.');
    return data;
  } finally {
    uploadsInProgress -= 1;
  }
}

/* ---------- Меню на телефоне ---------- */

function initNav() {
  const toggle = $('[data-nav-toggle]');
  const nav = $('#admin-nav');
  if (!toggle || !nav) return;
  const backdrop = $('.admin-backdrop');
  const outside = $$('.admin-top, .admin-main');
  const desktop = window.matchMedia('(min-width: 1024px)');
  const isOpen = () => document.body.classList.contains('nav-open');

  function open() {
    if (backdrop) {
      backdrop.hidden = false;
      void backdrop.offsetWidth; // чтобы фон появился плавно
    }
    document.body.classList.add('nav-open');
    toggle.setAttribute('aria-expanded', 'true');
    outside.forEach((el) => {
      el.inert = true;
    });
    const target = $('[aria-current="page"]', nav) || $('.admin-side__close', nav);
    window.setTimeout(() => target?.focus(), reducedMotion.matches ? 0 : 80);
  }

  function close(returnFocus = true) {
    if (!isOpen()) return;
    document.body.classList.remove('nav-open');
    toggle.setAttribute('aria-expanded', 'false');
    outside.forEach((el) => {
      el.inert = false;
    });
    if (backdrop) {
      const hide = () => {
        if (!isOpen()) backdrop.hidden = true;
      };
      if (reducedMotion.matches) hide();
      else window.setTimeout(hide, 400);
    }
    if (returnFocus) toggle.focus();
  }

  toggle.addEventListener('click', () => (isOpen() ? close() : open()));
  $$('[data-nav-close]').forEach((el) => el.addEventListener('click', () => close()));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) close();
  });
  desktop.addEventListener('change', (event) => {
    if (event.matches) close(false);
  });
}

/* ---------- Отправка форм: подтверждение, двойное нажатие, «Проверяем…» ---------- */

function initForms() {
  // Сначала — до всех остальных обработчиков: подтверждение и ожидание загрузки картинок.
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const submitter = event.submitter;
    const message = submitter?.dataset.confirm || form.dataset.confirm;
    if (message && !window.confirm(message)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }
    if (uploadsInProgress > 0 && form.querySelector('[data-uploader], [data-gallery]')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      toast('Подождите: картинка ещё загружается. Сохраните, когда загрузка закончится.', 'error', 6000);
    }
  }, true);

  // В конце — когда форма точно уходит на сервер.
  const busyButtons = new Map();
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
    if (form.hasAttribute('data-download-form')) return;
    if (form.dataset.submitting) {
      event.preventDefault(); // защита от двойного нажатия: иначе новая запись создастся дважды
      return;
    }
    form.dataset.submitting = '1';
    window.setTimeout(() => {
      delete form.dataset.submitting;
    }, 15000);
    const button = event.submitter;
    if (button && button.dataset.busyText) {
      busyButtons.set(button, Array.from(button.childNodes));
      const spinner = document.createElement('span');
      spinner.className = 'spinner';
      spinner.setAttribute('aria-hidden', 'true');
      button.replaceChildren(spinner, document.createTextNode(button.dataset.busyText));
      button.setAttribute('aria-disabled', 'true');
    }
  });

  // Возврат на страницу кнопкой «Назад»: всё снова можно нажимать.
  window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    $$('form[data-submitting]').forEach((form) => {
      delete form.dataset.submitting;
    });
    busyButtons.forEach((nodes, button) => {
      button.replaceChildren(...nodes);
      button.removeAttribute('aria-disabled');
    });
    busyButtons.clear();
  });

  // Скачивание архива: страница остаётся на месте, поэтому просто подсказываем.
  $$('form[data-download-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = $('button[type="submit"]', form);
      toast('Готовим архив — скачивание начнётся через несколько секунд.', 'info', 6000);
      if (button) {
        window.setTimeout(() => {
          button.disabled = true;
        }, 0);
        window.setTimeout(() => {
          button.disabled = false;
        }, 6000);
      }
    });
  });
}

/* ---------- Несохранённые изменения ---------- */

function initDirtyForms() {
  const forms = $$('form[data-dirty-form]');
  if (!forms.length) return;
  const dirty = new Set();
  let leaving = false;

  forms.forEach((form) => {
    const markDirty = (event) => {
      // Выбор файла сам по себе ничего не меняет — изменение засчитаем после загрузки.
      if (event && event.target instanceof HTMLInputElement && event.target.type === 'file') return;
      if (dirty.has(form)) return;
      dirty.add(form);
      $$('[data-dirty-hint]', form).forEach((hint) => {
        hint.hidden = false;
      });
    };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('admin:changed', () => markDirty());
    form.addEventListener('submit', (event) => {
      if (!event.defaultPrevented) leaving = true;
    });
  });

  window.addEventListener('beforeunload', (event) => {
    if (leaving || dirty.size === 0) return;
    event.preventDefault();
    event.returnValue = '';
  });
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) leaving = false;
  });
}

/* ---------- Счётчик символов ---------- */

function initCounters() {
  $$('[data-counter]').forEach((field) => {
    const max = Number(field.getAttribute('maxlength')) || 0;
    if (!max) return;
    const counter = document.createElement('span');
    counter.className = 'counter';
    counter.setAttribute('aria-hidden', 'true');
    field.after(counter);
    const update = () => {
      const length = field.value.length;
      counter.textContent = `${length} / ${max}`;
      counter.classList.toggle('is-over', length >= max);
    };
    field.addEventListener('input', update);
    update();
  });
}

/* ---------- Показать / скрыть пароль ---------- */

function initPasswords() {
  $$('input[data-password]').forEach((input) => {
    const hadFocus = document.activeElement === input;
    const wrap = document.createElement('div');
    wrap.className = 'password-wrap';
    input.before(wrap);
    wrap.append(input);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.setAttribute('aria-label', 'Показать пароль');
    button.setAttribute('aria-pressed', 'false');
    button.innerHTML = iconSvg('eye');
    wrap.append(button);
    if (hadFocus) input.focus();

    const setVisible = (visible) => {
      input.type = visible ? 'text' : 'password';
      button.setAttribute('aria-pressed', visible ? 'true' : 'false');
      button.innerHTML = iconSvg(visible ? 'eye-off' : 'eye');
    };
    button.addEventListener('click', () => {
      setVisible(input.type === 'password');
      input.focus({ preventScroll: true });
    });
    // Перед отправкой прячем пароль обратно — так менеджер паролей его увидит и предложит сохранить.
    input.form?.addEventListener('submit', () => setVisible(false));
  });
}

/* ---------- Одна картинка: логотип, значок, фото первого экрана ---------- */

function initUploaders() {
  $$('[data-uploader]').forEach((box) => {
    const input = $('[data-uploader-input]', box);
    const value = $('[data-uploader-value]', box);
    if (!input || !value) return;
    const img = $('[data-uploader-img]', box);
    const empty = $('[data-uploader-empty]', box);
    const busy = $('[data-uploader-busy]', box);
    const remove = $('[data-uploader-remove]', box);
    const pickLabel = $('[data-uploader-pick-label]', box);
    const errorBox = $('[data-uploader-error]', box);
    const purpose = box.dataset.purpose || 'logo';
    errorBox?.setAttribute('role', 'alert');

    const setError = (message) => {
      box.classList.toggle('is-invalid', Boolean(message));
      if (!errorBox) return;
      const span = $('span', errorBox);
      if (span) span.textContent = message || '';
      errorBox.hidden = !message;
    };

    const show = (src) => {
      const has = Boolean(src);
      if (img) {
        if (has) img.src = src;
        else img.removeAttribute('src');
        img.hidden = !has;
      }
      if (empty) empty.hidden = has;
      if (remove) remove.hidden = !has;
      if (pickLabel) pickLabel.textContent = has ? 'Заменить' : 'Загрузить';
    };

    input.addEventListener('change', async () => {
      const file = input.files && input.files[0];
      input.value = ''; // чтобы тот же файл можно было выбрать ещё раз
      if (!file) return;
      const problem = sizeProblem(file);
      if (problem) {
        setError(problem);
        return;
      }
      setError('');
      if (busy) busy.hidden = false;
      box.setAttribute('aria-busy', 'true');
      try {
        const data = await uploadImage(file, purpose);
        value.value = data.token;
        show(data.preview);
        notifyChanged(box);
        toast('Картинка загружена. Чтобы она появилась на сайте, нажмите «Сохранить».', 'success', 6000);
      } catch (error) {
        setError(error.message);
      } finally {
        if (busy) busy.hidden = true;
        box.removeAttribute('aria-busy');
      }
    });

    remove?.addEventListener('click', () => {
      value.value = '';
      show('');
      setError('');
      notifyChanged(box);
      input.focus();
      announce('Картинка убрана. Нажмите «Сохранить», чтобы убрать её с сайта.');
    });
  });
}

/* ---------- Фотографии машины ---------- */

function initGalleries() {
  $$('[data-gallery]').forEach((gallery) => {
    const list = $('[data-gallery-list]', gallery);
    const input = $('[data-gallery-input]', gallery);
    const template = $('template[data-gallery-template]', gallery);
    if (!list || !input || !template) return;
    const pick = input.closest('label');
    const errorBox = $('[data-gallery-error]', gallery);
    const progress = $('[data-gallery-progress]', gallery);
    const progressText = $('[data-gallery-progress-text]', gallery);
    const max = Number(gallery.dataset.max) || 12;
    const purpose = gallery.dataset.purpose || 'vehicle';
    errorBox?.setAttribute('role', 'alert');

    const items = () => $$('[data-gallery-item]', list);

    const setError = (message) => {
      if (!errorBox) return;
      const span = $('span', errorBox);
      if (span) span.textContent = message || '';
      errorBox.hidden = !message;
    };

    const refresh = () => {
      const full = items().length >= max;
      input.disabled = full;
      pick?.classList.toggle('is-disabled', full);
      pick?.setAttribute('title', full ? `Уже ${max} фото — больше добавить нельзя` : '');
    };

    const addItem = (data) => {
      const fragment = template.content.cloneNode(true);
      const item = $('[data-gallery-item]', fragment);
      if (!item) return;
      const img = $('[data-gallery-img]', item);
      const token = $('[data-gallery-token]', item);
      if (img) img.src = data.preview;
      if (token) token.value = data.token;
      list.append(item);
    };

    // Кнопка на краю (у первого фото нет «левее» и «сделать главным») скрыта — переводим фокус на соседнюю.
    const focusInItem = (item, preferred) => {
      const candidates = [preferred, '[data-gallery-move="1"]', '[data-gallery-move="-1"]', '[data-gallery-remove]'];
      for (const selector of candidates) {
        const button = $(selector, item);
        if (button && getComputedStyle(button).visibility !== 'hidden') {
          button.focus();
          return;
        }
      }
    };

    list.addEventListener('click', (event) => {
      const button = event.target.closest('button');
      if (!button || !list.contains(button)) return;
      const item = button.closest('[data-gallery-item]');
      if (!item) return;

      if (button.hasAttribute('data-gallery-remove')) {
        const neighbour = item.nextElementSibling || item.previousElementSibling;
        item.remove();
        if (neighbour) focusInItem(neighbour, '[data-gallery-remove]');
        else input.focus();
        announce('Фото убрано. Сохраните машину, чтобы изменения появились на сайте.');
      } else if (button.hasAttribute('data-gallery-cover')) {
        list.prepend(item);
        focusInItem(item, '[data-gallery-move="1"]');
        announce('Это фото теперь главное.');
      } else if (button.dataset.galleryMove) {
        const step = Number(button.dataset.galleryMove);
        if (step < 0 && item.previousElementSibling) item.previousElementSibling.before(item);
        if (step > 0 && item.nextElementSibling) item.nextElementSibling.after(item);
        focusInItem(item, `[data-gallery-move="${step}"]`);
        const position = items().indexOf(item) + 1;
        announce(position === 1 ? 'Фото стало первым — теперь оно главное.' : `Фото на месте ${position} из ${items().length}.`);
      } else {
        return;
      }
      setError('');
      refresh();
      notifyChanged(gallery);
    });

    input.addEventListener('change', async () => {
      const files = Array.from(input.files || []);
      input.value = '';
      if (!files.length) return;
      setError('');
      const room = max - items().length;
      if (room <= 0) {
        setError(`Можно добавить не больше ${max} фото. Уберите лишние, чтобы добавить новые.`);
        return;
      }
      const queue = files.slice(0, room);
      const problems = [];
      if (files.length > room) {
        problems.push(`Добавляем только ${room} из ${files.length}: всего можно ${max} фото.`);
      }
      if (progress) progress.hidden = false;
      input.disabled = true;
      gallery.setAttribute('aria-busy', 'true');
      let added = 0;
      for (const [index, file] of queue.entries()) {
        if (progressText) {
          progressText.textContent = queue.length > 1 ? `Загружаем ${index + 1} из ${queue.length}…` : 'Загружаем…';
        }
        const problem = sizeProblem(file);
        if (problem) {
          problems.push(problem);
          continue;
        }
        try {
          addItem(await uploadImage(file, purpose));
          added += 1;
        } catch (error) {
          problems.push(`«${file.name}»: ${error.message}`);
        }
      }
      if (progress) progress.hidden = true;
      gallery.removeAttribute('aria-busy');
      input.disabled = false;
      refresh();
      if (added > 0) {
        notifyChanged(gallery);
        toast(
          added === 1
            ? 'Фото добавлено. Не забудьте нажать «Сохранить машину».'
            : `Добавлено фото: ${added}. Не забудьте нажать «Сохранить машину».`,
          'success',
          6000,
        );
      }
      if (problems.length) setError(problems.join('\n'));
    });

    refresh();
  });
}

/* ---------- Списки: порядок и переключатель «На сайте» ---------- */

function initRows() {
  $$('[data-rows]').forEach((list) => {
    const url = list.dataset.reorderUrl;
    let inFlight = false;
    let queued = false;

    const refresh = () => {
      const rows = $$('[data-row]', list);
      rows.forEach((row, index) => {
        const up = $('[data-move="up"]', row);
        const down = $('[data-move="down"]', row);
        if (up) up.disabled = index === 0;
        if (down) down.disabled = index === rows.length - 1;
        const number = $('.row__icon--num', row);
        if (number) number.textContent = String(index + 1);
      });
    };

    // Запросы идут по одному; если двигали быстро — в конце отправится итоговый порядок.
    const saveOrder = async () => {
      if (inFlight) {
        queued = true;
        return;
      }
      inFlight = true;
      list.setAttribute('aria-busy', 'true');
      try {
        do {
          queued = false;
          const body = new FormData();
          $$('[data-row]', list).forEach((row) => body.append('ids[]', row.dataset.id || ''));
          await postForm(url, body);
        } while (queued);
      } catch (error) {
        toast(`Порядок не сохранился. ${error.message}`, 'error', 9000);
      } finally {
        inFlight = false;
        list.removeAttribute('aria-busy');
      }
    };

    window.addEventListener('beforeunload', (event) => {
      if (!inFlight) return;
      event.preventDefault();
      event.returnValue = '';
    });

    const moveRow = (form, submitter) => {
      const direction = submitter?.dataset.move;
      const row = form.closest('[data-row]');
      if (!row || (direction !== 'up' && direction !== 'down')) return;
      const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
      if (!sibling) return;
      if (direction === 'up') sibling.before(row);
      else sibling.after(row);
      refresh();
      const same = $(`[data-move="${direction}"]`, row);
      const other = $(`[data-move="${direction === 'up' ? 'down' : 'up'}"]`, row);
      (same && !same.disabled ? same : other)?.focus();
      row.classList.add('is-moved');
      window.setTimeout(() => row.classList.remove('is-moved'), 700);
      const rows = $$('[data-row]', list);
      announce(`Перемещено на место ${rows.indexOf(row) + 1} из ${rows.length}.`);
      saveOrder();
    };

    const toggleRow = async (form) => {
      if (form.dataset.busy) return;
      const button = $('.switch', form);
      const field = $('input[name="visible"]', form);
      if (!button || !field) return;
      const row = form.closest('[data-row]');
      const apply = (visible) => {
        button.setAttribute('aria-checked', visible ? 'true' : 'false');
        field.value = visible ? '0' : '1';
        const label = $('.switch__label', button);
        if (label) label.textContent = visible ? label.dataset.on : label.dataset.off;
        row?.classList.toggle('row--hidden', !visible);
      };
      const wanted = field.value === '1';
      const body = new FormData(form); // до apply(): в форме ещё нужное значение
      apply(wanted);
      form.dataset.busy = '1';
      try {
        const data = await postForm(form.action, body);
        const visible = typeof data.visible === 'boolean' ? data.visible : wanted;
        apply(visible);
        announce(visible ? 'Показано на сайте.' : 'Скрыто с сайта.');
      } catch (error) {
        apply(!wanted);
        toast(`Не переключилось. ${error.message}`, 'error', 9000);
      } finally {
        delete form.dataset.busy;
      }
    };

    list.addEventListener('submit', (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (form.matches('[data-move-form]')) {
        event.preventDefault();
        moveRow(form, event.submitter);
      } else if (form.matches('[data-toggle-form]')) {
        event.preventDefault();
        toggleRow(form);
      }
    });

    refresh();
  });
}

/* ---------- Поле с ошибкой после сохранения ---------- */

function focusFirstError() {
  const field = $('.admin-main [aria-invalid="true"]');
  if (!field) return;
  field.scrollIntoView({ block: 'center', behavior: 'auto' });
  field.focus({ preventScroll: true });
}

initNav();
initForms();
initDirtyForms();
initCounters();
initPasswords();
initUploaders();
initGalleries();
initRows();
focusFirstError();
