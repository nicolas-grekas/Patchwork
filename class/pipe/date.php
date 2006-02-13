<?php

class pipe_date
{
	static function php($time, $format = false)
	{
		if ($format === false)
		{
			$format = CIA::string($time);
			$time = null;
		}
		else
		{
			$time = CIA::string($time);
			$format = CIA::string($format);
		}

		return date($format, $time);
	}

	static function js()
	{
		?>/*<script>*/

root.P$<?php echo substr(__CLASS__, 5)?> = function($time, $format)
{
	if (t($format))
	{
		$format = str($format);
		$time = t($time) ? $time : null;
	}
	else
	{
		$format = str($time);
		$time = null;
	}

	$time = new Date($time);

	var $result = '',
		$a = /(\d\d).+(\d{4}) (\d\d):(\d\d):(\d\d)/,
		$GMT = $time.toGMTString().match($a),
		$local = $time.toLocaleString().match($a),
		$zone = (''+$time).match(/([A-Z]{3})?([-\+]\d{2})(\d{2})/),

		$month = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		$day = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

		$i = 0,
		$len = $format.length;

	while ($i < $len)
	{
		switch($format.charAt($i))
		{
			case 'a':// Lowercase Ante meridiem and Post meridiem am or pm
				$result += $local[3]<12 ? 'am' : 'pm';
				break;

			case 'A':// Uppercase Ante meridiem and Post meridiem AM or PM
				$result += $local[3]<12 ? 'AM' : 'PM';
				break;

			case 'B':// Internet Swatch	Time 000 to 999
				$result += (''+parseInt(((60*(60*($GMT[3]/1+1) + $GMT[4]/1) + $GMT[5]/1)%86400)/86.4+1000)).substr(1);
				break;

			case 'c':// ISO 8601 date (Ex. 2004-02-12T15:19:21+00:00)
				$result += _pipe_date('Y-m-d\\TH:i:s') + $zone[2] + ':' + $zone[3];
				break;

			case 'd':// Day of the month, 2 digits with leading zeros 01 to 31
				$result += $local[1];
				break;

			case 'D':// A textual representation of a day, three letters Mon through Sun
				$result += $day[$time.getDay()].substr(0, 3);
				break;

			case 'F':// A full textual representation of a month, such as January or March January through December
				$result += $month[$time.getMonth()];
				break;

			case 'g':// 12-hour format of an hour without leading zeros 1 through 12
				$result += $local[3]%12;
				break;

			case 'G':// 24-hour format of an hour without leading zeros 0 through 23
				$result += $local[3]/1;
				break;

			case 'h':// 12-hour format of an hour with leading zeros 01 through 12
				$result += (''+($local[3]%12 + 100)).substr(1);
				break;

			case 'H':// 24-hour format of an hour with leading zeros 00 through 23
				$result += $local[3];
				break;

			case 'i':// Minutes with leading zeros 00 to 59
				$result += $local[4];
				break;

			case 'I':// Whether or not the date is in daylights savings time 1 if Daylight Savings Time, 0 otherwise.
				$result += $time.getTimezoneOffset() == -60 * $zone[2] + $zone[3]/1 ? 0 : 1;
				break;

			case 'j':// Day of the month without leading zeros 1 to 31
				$result += $local[1]/1;
				break;

			case 'l':// A full textual representation of the day of the week Sunday through Saturday
				$result += $day[$time.getDay()];
				break;

			case 'L':// Whether it's a leap year 1 if it is a leap year, 0 otherwise.
				$result += $local[2]%4 ? 0 : 1;
				break;

			case 'm':// Numeric representation of a month, with leading zeros 01 through 12
				$result += (''+($time.getMonth() + 101)).substr(1);
				break;

			case 'M':// A short textual representation of a month, three letters Jan through Dec
				$result += $month[$time.getMonth()].substr(0, 3);
				break;

			case 'n':// Numeric representation of a month, without leading zeros 1 through 12
				$result += $time.getMonth() + 1;
				break;

			case 'O':// Difference to Greenwich time (GMT) in hours Example: +0200
				$result += $zone[2] + $zone[3];
				break;

			case 'r':// » RFC 2822 formatted date Example: Thu, 21 Dec 2000 16:01:07 +0200
				$result += _pipe_date('D, d M Y H:i:s O', $time);
				break;

			case 's':// Seconds, with leading zeros 00 through 59
				$result += $local[5];
				break;

			case 'S':// English ordinal suffix for the day of the month, 2 characters st, nd, rd or th. Works well with j
				$a = $local[1];
				$result += 4<=$a && $a<=20 ? 'th' : (['st', 'nd', 'rd'][$a%10-1] || 'th');
				break;

			case 't':// Number of days in the given month 28 through 31
				$result += new Date($local[2], $time.getMonth()+1, 0).getDate();
				break;

			case 'T':// Timezone setting of this machine Examples: EST, MDT ...
				$result += $zone[0];
				break;

			case 'U':// Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
				$result += parseInt($time/1000);
				break;

			case 'w':// Numeric representation of the day of the week 0 (for Sunday) through 6 (for Saturday)
				$result += $time.getDay();
				break;

			case 'W':// ISO-8601 week number of year, weeks starting on Monday Example: 42 (the 42nd week in the year)
				$a = new Date($local[2], 0, 1, 0, 0, 0, 0).getDay() - 1;
				if ($a < 0) $a = 6;
				$result += (Date.UTC($local[2], $time.getMonth(), $local[1]) - Date.UTC($local[2], 0, 8 - $a)) / (1000 * 60 * 60 * 24 * 7) + 1;
				break;

			case 'Y':// A full numeric representation of a year, 4 digits Examples: 1999 or 2003
				$result += $local[2];
				break;

			case 'y':// A two digit representation of a year Examples: 99 or 03
				$result += $local[2].substr(2);
				break;

			case 'z':// The day of the year (starting from 0) 0 through 365
				$result += (Date.UTC($local[2], $time.getMonth(), $local[1]) - Date.UTC($local[2], 0, 0)) / (1000 * 60 * 60 * 24);
				break;

			case 'Z':// Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive. -43200 through 43200
				$result += 60 * ( 60*$zone[2] + $zone[3]/1 );
				break;

			default:
				$result += $format.charAt($i)=='\\' && $i+1 < $len ? $format.charAt(++$i) : $format.charAt($i);
		}

		++$i;
	}

	return $result;
}

<?php	}
}
