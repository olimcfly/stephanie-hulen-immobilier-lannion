<?php
/**
 * ============================================================
 * helpers/utils.php
 * Fonctions utilitaires partagées par tous les renderers
 * ============================================================
 */

if (!function_exists('_ss')) {
    /**
     * Récupère un paramètre site via SiteSettings ou retourne le défaut
     */
    function _ss(string $key, string $default = ''): string {
        if (class_exists('SiteSettings')) {
            return SiteSettings::get($key, $default);
        }
        return $default;
    }
}

if (!function_exists('formatDateFr')) {
    /**
     * Formate une date en français : "1er janvier 2025"
     */
    function formatDateFr(string $date): string {
        if (empty($date)) return '';
        $months = [
            'janvier','février','mars','avril','mai','juin',
            'juillet','août','septembre','octobre','novembre','décembre'
        ];
        $ts = strtotime($date);
        if (!$ts) return $date;
        $d = intval(date('d', $ts));
        $m = $months[intval(date('n', $ts)) - 1];
        $y = date('Y', $ts);
        return ($d === 1 ? '1er' : $d) . ' ' . $m . ' ' . $y;
    }
}

if (!function_exists('jsonDecode')) {
    /**
     * Décode un champ JSON de la DB — retourne toujours un tableau
     */
    function jsonDecode(?string $val): array {
        if (empty($val)) return [];
        $d = json_decode($val, true);
        return is_array($d) ? $d : [];
    }
}

if (!function_exists('safeSlug')) {
    /**
     * Extrait le slug propre depuis REQUEST_URI
     */
    function safeSlug(): string {
        $raw = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        return empty($raw) ? 'accueil' : $raw;
    }
}

if (!function_exists('siteUrl')) {
    /**
     * Retourne l'URL de base du site sans slash final
     */
    function siteUrl(): string {
        return rtrim(_ss('site_url', 'https://eduardo-desul-immobilier.fr'), '/');
    }
}

if (!function_exists('siteName')) {
    /**
     * Retourne le nom du site
     */
    function siteName(): string {
        return _ss('site_name', 'Eduardo De Sul Immobilier');
    }
}

if (!function_exists('readingTime')) {
    /**
     * Calcule le temps de lecture estimé en minutes
     */
    function readingTime(string $content, int $wpm = 200): int {
        return max(1, (int) ceil(str_word_count(strip_tags($content)) / $wpm));
    }
}

if (!function_exists('truncate')) {
    /**
     * Tronque un texte à N caractères en coupant sur un mot
     */
    function truncate(string $text, int $max = 160, string $suffix = '...'): string {
        $text = strip_tags($text);
        if (mb_strlen($text) <= $max) return $text;
        return rtrim(mb_substr($text, 0, $max)) . $suffix;
    }
}