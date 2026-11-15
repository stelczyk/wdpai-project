<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

/**
 * Teksty interfejsu w wybranym języku. Katalogi tłumaczeń: lang/pl.php i lang/en.php
 * (płaskie tablice 'klucz' => 'tekst', parametry w tekście jako {nazwa}).
 */
final class Translator
{
    public const LOCALES = ['pl', 'en'];

    private const DEFAULT_LOCALE = 'pl';
    private const COOKIE_NAME = 'lang';
    private const COOKIE_LIFETIME = 60 * 60 * 24 * 365;

    private static string $locale = self::DEFAULT_LOCALE;
    private static array $messages = [];

    // Kolejność: ?lang= w adresie (zapamiętywany w cookie) → cookie z poprzedniej wizyty → polski.
    public static function initFromRequest(): void
    {
        $requested = $_GET['lang'] ?? null;

        if (self::isSupported($requested)) {
            setcookie(self::COOKIE_NAME, $requested, [
                'expires' => time() + self::COOKIE_LIFETIME,
                'path' => '/',
                'samesite' => 'Lax',
                'httponly' => true,
            ]);
            self::setLocale($requested);

            return;
        }

        $saved = $_COOKIE[self::COOKIE_NAME] ?? null;
        self::setLocale(self::isSupported($saved) ? $saved : self::DEFAULT_LOCALE);
    }

    public static function setLocale(string $locale): void
    {
        if (!self::isSupported($locale)) {
            throw new InvalidArgumentException('Unsupported locale: ' . $locale);
        }

        self::$locale = $locale;
        self::$messages = require dirname(__DIR__) . '/lang/' . $locale . '.php';
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    // Brakujący klucz zwracamy dosłownie – od razu widać na stronie, czego brakuje w katalogu.
    public static function get(string $key, array $params = []): string
    {
        $text = self::$messages[$key] ?? $key;

        $replacements = [];
        foreach ($params as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($text, $replacements);
    }

    // Liczby w zapisie danego języka: PL „1 330,5”, EN „1,330.5”.
    public static function number(float $value, int $decimals = 1): string
    {
        return self::$locale === 'pl'
            ? number_format($value, $decimals, ',', ' ')
            : number_format($value, $decimals, '.', ',');
    }

    private static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::LOCALES, true);
    }
}
