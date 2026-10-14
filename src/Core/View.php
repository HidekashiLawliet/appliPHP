<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renders PHP templates from src/Views inside a layout.
 *
 * Templates only display data: they receive ready-made variables from the
 * controller and use the helpers of this class through `$this`.
 */
final class View
{
    public function __construct(private readonly string $templateDir)
    {
    }

    /**
     * Renders a template, then wraps it in the layout.
     *
     * @param string               $template Path below src/Views, without ".php".
     * @param array<string, mixed> $data     Variables made available to the template.
     */
    public function render(string $template, array $data = []): string
    {
        $content = $this->renderFile($template, $data);

        return $this->renderFile('layout/main', ['content' => $content] + $data);
    }

    /**
     * Escapes a value for HTML output.
     */
    public function e(mixed $value): string
    {
        return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Formats a SQL datetime ("2026-10-12 08:30:00") for display or for an input.
     *
     * @param string $format "d/m/Y H:i" for display, "Y-m-d\TH:i" for datetime-local.
     */
    public function date(mixed $sqlDate, string $format = 'd/m/Y H:i'): string
    {
        if (!is_string($sqlDate) || $sqlDate === '') {
            return '';
        }
        $date = date_create_immutable($sqlDate);

        return $date === false ? $sqlDate : $date->format($format);
    }

    /**
     * Returns the hidden CSRF field to put in every form.
     */
    public function csrfField(): string
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', Csrf::FIELD, $this->e(Csrf::token()));
    }

    /**
     * Includes a template file with the given variables and captures its output.
     *
     * @param array<string, mixed> $data
     * @throws \InvalidArgumentException When the template name is invalid or missing.
     */
    private function renderFile(string $template, array $data): string
    {
        if (preg_match('#^[a-z0-9_]+(/[a-z0-9_]+)*$#', $template) !== 1) {
            throw new \InvalidArgumentException(sprintf('Nom de vue invalide : "%s".', $template));
        }

        $path = $this->templateDir . '/' . $template . '.php';
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Vue introuvable : "%s".', $template));
        }

        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $path;
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
