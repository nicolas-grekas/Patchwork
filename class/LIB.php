<?php

class LIB
{
	protected static $sort_keys;
	protected static $sort_length;

	/**
	 * Sorts an array of array/objects according to a list of keys/properties.
	 */
	static function sort(&$array, $keys, $associative = false)
	{
		self::$sort_keys = preg_split("'\s*,\s*'u", $keys);
		self::$sort_length = count(self::$sort_keys);

		$associative
			? uasort($array, array('self', 'sort_compare'))
			: usort($array, array('self', 'sort_compare'));
	}

	protected static function sort_compare($a, $b)
	{
		$a = (array) $a;
		$b = (array) $b;

		for ($i = 0; $i < self::$sort_length; ++$i)
		{
			$key = self::$sort_keys[$i];
			if (' desc' == strtolower(substr($key, -5)))
			{
				$key = substr($key, 0, -5);
				$cmp = -1;
			}
			else $cmp = 1;
			
			if ($a[$key] < $b[$key]) return -$cmp;
			else if ($a[$key] > $b[$key]) return $cmp;
		}

		return 0;
	}

	protected static $ACCENT = array(
		'AÀÁÂÃÄÅĀĂĄǺ','aàáâãäåāăąǻ','CĆĈÇĊČ','cćĉçċč','DĐĎ','dđď',
		'EÈÉÊËĒĔĘĖĚ','eèéêëēĕęėě','GĜĢĞĠ','gĝģğġ','HĤĦ','hĥħ',
		'IÌÍÎĨÏĪĬĮİ','iìíîĩïīĭįı','JĴ','jĵ','KĶ','kķ','LĹĻŁĿĽ','lĺļłŀľ',
		'NŃÑŅŇ','nńñņň','OÒÓŐÔÕÖØŌŎǾ','oòóőôõöøōŏǿ','RŔŖŘ','rŕŗř',
		'SŚŜŞŠ','sśŝşš','TŢŦŤ','tţŧť','UÙÚŰÛŨÜŮŪŬŲ','uùúűûũüůūŭų',
		'WẀẂŴẄ','wẁẃŵẅ','YỲÝŶŸ','yỳýŷÿ','ZŹŻŽ','zźżž'
	);
	protected static $ACCENT_FROM = array();
	protected static $ACCENT_TO = array();
	protected static $ACCENT_LENGTH;

	protected static function initAccents()
	{
		self::$ACCENT_FROM = array(
			"/[\xcc\xa8\xcc\xb1]/u", '/[ÆǼǢ]/u', '/[æǽǣ]/u', '/ß/u', '/Œ/u', '/œ/u', '/ʤʣʥ/u', '/ﬀ/u',
			'/ﬃ/u', '/ﬄ/u', '/ﬁ/u', '/ﬂ/u', '/ƕ/u', '/Ƣ/u', '/ƣ/u', '/ﬆﬅ/u', '/ʨ/u', '/ʦ/u', '/ƻ/u'
		);
		self::$ACCENT_TO   = array(
			''                     , 'AE'      , 'ae'      , 'ss'  , 'OE'  , 'oe'  , 'dz'    , 'ff'  ,
			'ffi' , 'ffl' , 'fi'  , 'fl'  , 'hv'  , 'OI'  , 'oi'  , 'st'   , 'tc'  , 'ts'  , '2'
		);

		$len = self::$ACCENT_LENGTH = count(self::$ACCENT);
		for ($i = 0; $i < $len; ++$i)
		{
			$v = self::$ACCENT[$i];
			self::$ACCENT_FROM[] = '/[' . substr($v, 1) . ']/u';
			self::$ACCENT_TO[]   = $v{0};
		}
	}

	/**
	 * Removes all accents from an UTF-8 string, and optionnaly change it's case.
	 */
	static function stripAccents($str, $case = 0)
	{
		if (!self::$ACCENT_FROM) self::initAccents();

		$str = preg_replace(self::$ACCENT_FROM, self::$ACCENT_TO, $str);

		return $case>0 ? mb_strtoupper($str) : $case<0 ? mb_strtolower($str) : $str;
	}

	/**
	 * Transform a string to a RegExp that is not sentive to accents
	 */
	static function getRxQuoteInsensitive($str, $delimiter = '')
	{
		if (!self::$ACCENT_FROM) self::initAccents();

		$str = '' === $delimiter ? preg_quote($str) : preg_quote($str, $delimiter);

		$str = preg_replace('/["«»“”″]/u', '["«»“”″]', $str);
		$str = preg_replace("/['‘’′ʿ]/u" , "['‘’′ʿ]" , $str);
		$str = preg_replace("/[- _]/u"   , '[- _]'   , $str);
		$str = preg_replace(self::$ACCENT_FROM, self::$ACCENT_TO, $str);

		$len = self::$ACCENT_LENGTH;
		for ($i = 0; $i < $len; ++$i)
		{
			$v = self::$ACCENT[$i];
			$str = str_replace($v{0}, "[{$v}]", $str);
		}

		return $str;
	}

	/**
	 * Return an alphabetic RexExp class, with accents support
	 */
	static function getAlphaRx()
	{
		return "a-zA-Z\xcc\xa8\xcc\xb1ÆǼǢæǽǣßŒœʤʣʥﬀﬃﬄﬁﬂƕƢƣﬆﬅʨʦƻ" . implode('', self::$ACCENT);
	}

	/**
	 * Clean an string to make it suitable for a search
	 */
	static function getKeywords($kw)
	{
		$a = "[ʿ’[:punct:][:cntrl:][:space:]]";

		$kw = ' ' . $kw . ' ';

		// Initials (Sigle)
		$kw = preg_replace("'{$a}([A-Z](?:\.[A-Z])+){$a}'ue", "str_replace('.','',' $1 ')", $kw);

		// Ponctuation
		$kw = preg_replace("'{$a}+'u", ' ', $kw);

		// Accents and case
		$kw = self::stripAccents($kw, -1);

		return trim($kw);
	}
}
