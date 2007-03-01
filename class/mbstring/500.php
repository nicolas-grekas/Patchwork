<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


/*

 * Working only if iconv is loaded :

mb_convert_encoding
mb_decode_mimeheader
mb_encode_mimeheader


 * Not implemented :

mb_convert_kana — Convertit entre les différents "kana"
mb_convert_variables — Convertit l'encodage de variables
mb_decode_numericentity — Décode les entités HTML en caractères
mb_detect_encoding — Détecte un encodage
mb_detect_order — Lit/modifie l'ordre de détection des encodages
mb_encode_numericentity — Encode des entités HTML
mb_ereg_match — Expression rationnelle POSIX pour les chaînes multi-octets
mb_ereg_replace — Remplace des segments de chaînes, avec le support des expressions rationnelles mutli-octets
mb_ereg_search_getpos — Retourne l'offset du début du prochain segment repéré par une expression rationnelle
mb_ereg_search_getregs — Lit le dernier segment de chaîne multi-octets qui correspond au masque
mb_ereg_search_init — Configure les chaînes et les expressions rationnelles pour le support des caractères multi-octets
mb_ereg_search_pos — Retourne la position et la longueur du segment de chaîne qui vérifie le masque de l'expression rationnelle
mb_ereg_search_regs — Retourne le segment de chaîne trouvé par une expression rationnelle multi-octets
mb_ereg_search_setpos — Choisit le point de départ de la recherche par expression rationnelle
mb_ereg_search — Recherche par expression rationnelle multi-octets
mb_ereg — Recherche par expression rationnelle avec support des caractères multi-octets
mb_eregi_replace — Expression rationnelle avec support des caractères multi-octets, sans tenir compte de la casse
mb_eregi — Expression rationnelle insensible à la casse avec le support des caractères multi-octets
mb_get_info — Lit la configuration interne de l'extension mbstring
mb_http_input — Détecte le type d'encodage d'un caractère HTTP
mb_http_output — Lit/modifie l'encodage d'affichage
mb_internal_encoding — Lit/modifie l'encodage interne
mb_language — Lit/modifie le langage courant
mb_list_encodings_alias_names — Returns an array of all supported alias encodings
mb_list_mime_names — Returns an array or string of all supported mime names
mb_output_handler — Fonction de traitement des affichages
mb_preferred_mime_name — Détecte l'encodage MIME
mb_regex_encoding — Retourne le jeu de caractères courant pour les expressions rationnelles
mb_regex_set_options — Lit et modifie les options des fonctions d'expression rationnelle à support de caractères multi-octets
mb_send_mail — Envoie un mail encodé
mb_split — Scinde une chaîne en tableau avec une expression rationnelle multi-octets
mb_strcut
mb_strimwidth
mb_strwidth
mb_substitute_character

 */

class
{
	static function convert_encoding($str, $to_encoding, $from_encoding = 'UTF-8')
	{
		if (function_exists('iconv')) return iconv($from_encoding, $to_encoding, $str);
		W(__FUNCTION__ . '() not supported on this configuration');
		return $str;
	}

	static function decode_mimeheader($str)
	{
		if (function_exists('iconv_mime_decode')) return iconv_mime_decode($str);
		W(__FUNCTION__ . '() not supported on this configuration');
		return $str;
	}

	static function encode_mimeheader($str, $charset = 'UTF-8', $transfer_encoding = null, $linefeed = null, $indent = null)
	{
		if (function_exists('iconv_mime_encode')) return iconv_mime_encode('', $str, array(
			'scheme' => null === $transfer_encoding ? 'B' : $transfer_encoding,
			'input-charset' => $charset,
			'output-charset' => $charset,
			'line-length' => 76,
			'line-break-chars' => null === $linefeed ? "\r\n" : $linefeed,
		));
		W(__FUNCTION__ . '() not supported on this configuration');
		return $str;
	}


	static function convert_case($str, $mode, $encoding = null)
	{
		switch ($mode)
		{
		case MB_CASE_UPPER: return mb_strtoupper($str);
		case MB_CASE_LOWER: return mb_strtolower($str);
		case MB_CASE_TITLE: return preg_replace("/\b./eu", 'mb_strtoupper("$0",$encoding)', $str);
		}

		return $str;
	}

	static function list_encodings()
	{
		return array('UTF-8');
	}

	static function parse_str($encoded_string, &$result = null, $encoding = null)
	{
		return parse_str($encoded_string, $result);
	}

	static function strlen($str, $encoding = null)
	{
		return function_exists('iconv_strlen') ? iconv_strlen($str) : strlen(utf8_decode($str));
	}

	static function strpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		if (function_exists('iconv_strpos')) return iconv_strpos($haystack, $needle, $offset);
		if ($offset = (int) $offset) $haystack = mb_substr($haystack, $offset);
		$pos = strpos($haystack, $needle);
		return false === $pos ? false : ($offset + ($pos ? mb_strlen(substr($haystack, 0, $pos)) : 0));
	}

	static function strrpos($haystack, $needle, $offset = 0, $encoding = null)
	{
		if ($offset = (int) $offset) $haystack = mb_substr($haystack, $offset);
		$needle = mb_substr($needle, 0, 1);
		$pos = strpos(strrev($haystack), strrev($needle));
		return false === $pos ? false : ($offset + mb_strlen($pos ? substr($haystack, 0, -$pos) : $haystack));
	}

	static function strtolower($str, $encoding = null)
	{
		return preg_replace('/[A-Z]+/eu', "strtolower('$0')", $str);
	}

	static function strtoupper($str, $encoding = null)
	{
		return preg_replace('/[a-z]+/eu', "strtoupper('$0')", $str);
	}

	static function substr_count($haystack, $needle, $encoding = null)
	{
		return substr_count($haystack, $needle);
	}

	static function substr($str, $start, $length = null, $encoding = null)
	{
		if (function_exists('iconv_substr')) return iconv_substr($str, $start, $length);

		$strlen = mb_strlen($str);
		$start = (int) $start;

		if (0 > $start) $start += $strlen;
		if (0 > $start) $start = 0;
		if ($start >= $strlen) return '';

		$rx = $strlen - $start;

		if (null === $length) $length  = $rx;
		else if (0 > $length) $length += $rx;
		if (0 >= $length) return '';

		if ($length > $strlen - $start) $length = $rx;

		$rx = '/^' . (0 > $start ? '' : self::preg_offset($start)) . '(' . self::preg_offset($length) . ')/u';

		return preg_match($rx, $str, $str) ? $str[1] : '';
	}

	static function preg_offset($offset)
	{
		$rx = array();
		$offset = (int) $offset;

		while ($offset > 65535)
		{
			$rx[] = '(?:.{65535})';
			$offset -= 65535;
		}

		return implode('', $rx) . '(?:.{' . $offset . '})';
	}
}
