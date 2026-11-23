<?php

declare(strict_types=1);

/*
 * Простое оформление текстов из админки — без HTML-кода.
 *   ## Заголовок         → заголовок раздела
 *   - пункт              → список
 *   1. пункт             → нумерованный список
 *   **жирный**           → выделение
 *   пустая строка        → новый абзац
 * Весь текст сначала экранируется, поэтому вставить вредный код через админку нельзя.
 */

/** Абзацы и переносы строк для коротких текстов (описания, ответы FAQ). */
function text_to_html(string $text): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
    if ($text === '') {
        return '';
    }
    $paragraphs = preg_split("/\n{2,}/", $text) ?: [];
    $out = '';
    foreach ($paragraphs as $p) {
        $out .= '<p>' . nl2br(inline_markup(e(trim($p))), false) . '</p>';
    }
    return $out;
}

/**
 * Длинный текст с заголовками и списками (политика конфиденциальности).
 * $vars — подстановки: ['{email}' => '<a …>…</a>'] (значения уже должны быть безопасным HTML).
 */
function rich_text_to_html(string $text, array $vars = []): string
{
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
    $html = '';
    $paragraph = [];
    $list = null; // 'ul' | 'ol'
    $items = [];

    $flushParagraph = static function () use (&$paragraph, &$html): void {
        if ($paragraph) {
            $html .= '<p>' . implode('<br>', $paragraph) . '</p>';
            $paragraph = [];
        }
    };
    $flushList = static function () use (&$list, &$items, &$html): void {
        if ($list !== null && $items) {
            $html .= '<' . $list . '>';
            foreach ($items as $item) {
                $html .= '<li>' . $item . '</li>';
            }
            $html .= '</' . $list . '>';
        }
        $list = null;
        $items = [];
    };
    $format = static fn (string $s): string => strtr(inline_markup(e($s)), $vars);

    foreach ($lines as $raw) {
        $line = trim($raw);
        if ($line === '') {
            $flushParagraph();
            $flushList();
            continue;
        }
        if (preg_match('/^(#{2,3})\s+(.+)$/u', $line, $m)) {
            $flushParagraph();
            $flushList();
            $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $html .= '<' . $tag . '>' . $format($m[2]) . '</' . $tag . '>';
            continue;
        }
        if (preg_match('/^[-•*]\s+(.+)$/u', $line, $m)) {
            $flushParagraph();
            if ($list !== 'ul') {
                $flushList();
                $list = 'ul';
            }
            $items[] = $format($m[1]);
            continue;
        }
        if (preg_match('/^\d{1,3}[.)]\s+(.+)$/u', $line, $m)) {
            $flushParagraph();
            if ($list !== 'ol') {
                $flushList();
                $list = 'ol';
            }
            $items[] = $format($m[1]);
            continue;
        }
        $flushList();
        $paragraph[] = $format($line);
    }
    $flushParagraph();
    $flushList();
    return $html;
}

/** **жирный** → <strong>. Получает уже экранированный текст. */
function inline_markup(string $escaped): string
{
    return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
}

/** Список строк из многострочного поля (по одной на строку). */
function lines_list(string $text): array
{
    return array_values(array_filter(
        array_map(static fn (string $l): string => trim(ltrim(trim($l), '-•*')), explode("\n", str_replace("\r", '', $text))),
        static fn (string $l): bool => $l !== ''
    ));
}
