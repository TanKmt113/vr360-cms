<?php
/**
 * Polyfill tối thiểu cho các hàm mb_* khi PHP không bật extension mbstring.
 * Chỉ định nghĩa khi hàm gốc chưa tồn tại — nếu có mbstring sẽ dùng bản gốc.
 * Hỗ trợ tiếng Việt cho mb_strtolower (bảng chuyển hoa→thường).
 */
declare(strict_types=1);

if (!function_exists('mb_strlen')) {
    function mb_strlen($s, $enc = null): int
    {
        return preg_match_all('/./us', (string)$s);
    }
}

if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0, $enc = null)
    {
        // Substring UTF-8 an toàn theo byte cho phép so khớp chính xác
        $pos = strpos((string)$haystack, (string)$needle, (int)$offset);
        return $pos === false ? false : $pos;
    }
}

if (!function_exists('mb_strtolower')) {
    // Bảng hoa→thường: ASCII A-Z + nguyên âm tiếng Việt có dấu
    function _vn_lower_map(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }
        $upper = 'AÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴĐ';
        $lower = 'aàáảãạăằắẳẵặâầấẩẫậèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ';
        // Tách theo ký tự UTF-8
        $us = preg_split('//u', $upper, -1, PREG_SPLIT_NO_EMPTY);
        $ls = preg_split('//u', $lower, -1, PREG_SPLIT_NO_EMPTY);
        $map = array_combine($us, $ls) ?: [];
        return $map;
    }

    function mb_strtolower($s, $enc = null): string
    {
        // strtolower xử lý A-Z (ASCII, an toàn với byte UTF-8); strtr xử lý nguyên âm tiếng Việt
        return strtolower(strtr((string)$s, _vn_lower_map()));
    }
}

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth($s, $start, $width, $trimmarker = '', $enc = null): string
    {
        $chars = preg_split('//u', (string)$s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($chars) <= $width) {
            return (string)$s;
        }
        return implode('', array_slice($chars, (int)$start, (int)$width)) . $trimmarker;
    }
}

if (!function_exists('mb_convert_case')) {
    function mb_convert_case($s, $mode, $enc = null): string
    {
        return mb_strtolower($s);
    }
}
