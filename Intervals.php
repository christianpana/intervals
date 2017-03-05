<?php

/**
 * Class that allows a date interval to be split into smaller chunks
 * Also allows for quick interval selection (e.g. last 7 days)
 * All passed in dates should be datetimes otherwise the result might be ambiguous
 * @author christianpana.com
 */
class Intervals
{


	/**
	 * Wrapper function to get the intervals array
	 * @param string $sType
	 * @param string $sStartDate
	 * @param string $sEndDate
	 * @return array
	 */
	public static function getIntervals($sType, $sStartDate, $sEndDate)
	{
		SWITCH($sType)
		{
			case 'hour':
			case 'hourly': 	
				return self::getHourIntervals($sStartDate, $sEndDate); 
				break;

			case 'day':
			case 'daily':	
				return self::getDayIntervals($sStartDate, $sEndDate); 
				break;

			case 'week':
			case 'weekly':	
				return self::getWeekIntervals($sStartDate, $sEndDate); 
				break;

			case 'month':
			case 'monthly':	
				return self::getMonthIntervals($sStartDate, $sEndDate); 
				break;

			case 'year':
			case 'yearly':	
				$aIntervals = self::getMonthIntervals($sStartDate, $sEndDate);

				foreach($aIntervals as $sKey => $aInterval)
				{
					$aIntervals[$sKey]['type'] = 'yearly';
					$aIntervals[$sKey]['key'] = date('Y-01-01 00:00:00', strtotime($aInterval['key']));
				}

				return $aIntervals;

			case 'total-month':
				$aIntervals = self::getMonthIntervals($sStartDate, $sEndDate, 'total');

				foreach($aIntervals as $sKey => $aInterval)
				{
					$aIntervals[$sKey]['type'] = 'total';
					$aIntervals[$sKey]['key'] = 'total';
				}

				return $aIntervals;

			default:
				return array();
		}
	}


	/**
	 * Get the interval split keys and the names to display
	 * @param array $aIntervals
	 * @return array
	 */
	public static function getIntervalKeys(array $aIntervals)
	{
		$aIntervalKeys = array();

		foreach($aIntervals as $aInterval)
		{
			$sIntervalKey 		= $aInterval['key'];
			$iIntervalKeyUnix 	= strtotime($sIntervalKey);
			$iStartDateUnix 	= $aInterval['start_date_unix'];
			$iEndDateUnix 		= $aInterval['end_date_unix'];

			/* The same interval key might be used for two intervals (if it was split) */
			if(!isset($aIntervalKeys[$sIntervalKey]))
			{
				$aIntervalKeys[$sIntervalKey]['interval'] = $aInterval;
				$aIntervalKeys[$sIntervalKey]['key'] = $sIntervalKey;
				$aIntervalKeys[$sIntervalKey]['key_unix'] = $iIntervalKeyUnix;
				$aIntervalKeys[$sIntervalKey]['start_date_unix'] = $aInterval['start_date_unix'];
				$aIntervalKeys[$sIntervalKey]['end_date_unix'] = $aInterval['end_date_unix'];
			}
			else
			{
				$iStartDateUnix = $aIntervalKeys[$sIntervalKey]['start_date_unix'];
				$aIntervalKeys[$sIntervalKey]['end_date_unix'] = $aInterval['end_date_unix'];
			}

			// set a pretty name for the interval
			SWITCH($aInterval['type'])
			{
				case 'hourly':
				case 'hour': 	
					$aIntervalKeys[$sIntervalKey]['name'] = date('H:i', $iIntervalKeyUnix); 
					break;

				case 'daily':
				case 'day': 	
					$aIntervalKeys[$sIntervalKey]['name'] = date('D d', $iIntervalKeyUnix); 
					break;

				case 'weekly':
				case 'week':
					$sWeek  = 'Week ' . date('W', $iIntervalKeyUnix) . ' ' . date('Y', $iIntervalKeyUnix) .' - ';
					$sWeek .= date('d/m', $iStartDateUnix) . ' - ' . date('d/m', $iEndDateUnix);
					$aIntervalKeys[$sIntervalKey]['name'] = $sWeek;

					$sWeek  = 'Week ' . date('W', $iIntervalKeyUnix) . ' ' . date('Y', $iIntervalKeyUnix) .' <br />';
					$sWeek .= date('d/m', $iStartDateUnix) . ' - ' . date('d/m', $iEndDateUnix);
					$aIntervalKeys[$sIntervalKey]['name_br'] = $sWeek;

					$sWeek  = 'Week ' . date('W', $iIntervalKeyUnix) . ' ' . date('Y', $iIntervalKeyUnix) . " \r\n";
					$sWeek .= date('d/m', $iStartDateUnix) . ' - ' . date('d/m', $iEndDateUnix);
					$aIntervalKeys[$sIntervalKey]['name_nobr'] = $sWeek;
					break;

				case 'monthly':
				case 'month': 	
					$aIntervalKeys[$sIntervalKey]['name']	= date('F Y', $iIntervalKeyUnix); 
					break;

				case 'yearly':
				case 'year': 	
					$aIntervalKeys[$sIntervalKey]['name'] = date('Y', $iIntervalKeyUnix); 
					break;

				default: 
					$aIntervalKeys[$sIntervalKey]['name'] = 'n/a';
			}
		}

		uasort($aIntervalKeys, function($aDateOne,$aDateTwo) {
			if($aDateOne['key_unix'] > $aDateTwo['key_unix'])
				return 1;
			elseif($aDateOne['key_unix'] < $aDateTwo['key_unix'])
				return -1;
			else
				return 0;
		});

		return $aIntervalKeys;
	}
	

	/**
	 * Split interals into 1 hour chunks
	 *
	 * @param string $sStartDate
	 * @param string $sEndDate
	 * @return array
	 */
	public static function getHourIntervals($sStartDate, $sEndDate)
	{
		$i = 1;

		/* Interval limits as a unix timestamps */
		$iOriginalStartDateUnix = strtotime($sStartDate);
		$iOriginalEndDateUnix	= strtotime($sEndDate);

		/* Variable that will be incremented - defaults to initial start date */
		$iDateUnix = $iOriginalStartDateUnix;

		/* Holds the result */
		$aIntervals = array();

		/* As long as this is true, the while will loop */
		$bLoop = true;

		while($bLoop && $iDateUnix <= $iOriginalEndDateUnix)
		{

			$iStartDateUnix = $iDateUnix;
			$iEndDateUnix 	= strtotime(date('Y-m-d H:59:59', $iStartDateUnix));

			/* Next period */
			$iDateUnix = $iEndDateUnix + 1;

			/* check for the end of interval */
			if($iEndDateUnix > $iOriginalEndDateUnix)
			{
				$bLoop = false;
				$iEndDateUnix = $iOriginalEndDateUnix;
			}

			$aIntervals[$i]['type']				= 'hourly';
			$aIntervals[$i]['start_date'] 		= date('Y-m-d H:i:s', $iStartDateUnix);
			$aIntervals[$i]['end_date'] 		= date('Y-m-d H:i:s', $iEndDateUnix);
			$aIntervals[$i]['start_date_unix'] 	= $iStartDateUnix;
			$aIntervals[$i]['end_date_unix'] 	= $iEndDateUnix;
			$aIntervals[$i]['month'] 			= date('_ym', $iStartDateUnix);
			$aIntervals[$i]['ym'] 				= date('ym', $iStartDateUnix);
			$aIntervals[$i]['Y'] 				= date('Y', $iStartDateUnix);
			$aIntervals[$i]['y'] 				= date('y', $iStartDateUnix);
			$aIntervals[$i]['month_start'] 		= date('Y-m-01 00:00:00', $iStartDateUnix);
			$aIntervals[$i]['month_end'] 		= date('Y-m-t 23:59:59', $iStartDateUnix);
			$aIntervals[$i]['ym01']				= date('Y-m-01', $iStartDateUnix);
			$aIntervals[$i]['day'] 				= date('j', $iStartDateUnix);
			$aIntervals[$i]['days'] 			= array(date('j', $iStartDateUnix));
			$aIntervals[$i]['key']              = date('Y-m-d H:00:00', $iStartDateUnix);

			$i++;
		}

		return $aIntervals;
	}


	/**
	 * Split interval into 1 day chunks
	 * @param string $sStartDate
	 * @param string $sEndDate
	 */
	public static function getDayIntervals($sStartDate, $sEndDate)
	{
		$i = 1;

		/* Interval limits as a unix timestamp */
		$iOriginalStartDateUnix = strtotime($sStartDate);
		$iOriginalEndDateUnix	= strtotime($sEndDate);

		/* Variable that will be incremented - defaults to initial start date */
		$iDateUnix = $iOriginalStartDateUnix;

		/* Holds the result */
		$aIntervals = array();

		/* As long as this is true, the while will loop */
		$bLoop = true;

		while($bLoop && $iDateUnix <= $iOriginalEndDateUnix)
		{
			$iStartDateUnix = $iDateUnix;
			$iEndDateUnix 	= strtotime(date('Y-m-d 23:59:59', $iStartDateUnix));

			$iDateUnix = $iEndDateUnix + 1;

			/* check for the end of interval */
			if($iEndDateUnix > $iOriginalEndDateUnix)
			{
				$bLoop = false;
				$iEndDateUnix = $iOriginalEndDateUnix;
			}

			$aIntervals[$i]['type']				= 'daily';
			$aIntervals[$i]['start_date'] 		= date('Y-m-d H:i:s', $iStartDateUnix);
			$aIntervals[$i]['end_date'] 		= date('Y-m-d H:i:s', $iEndDateUnix);
			$aIntervals[$i]['start_date_unix'] 	= $iStartDateUnix;
			$aIntervals[$i]['end_date_unix'] 	= $iEndDateUnix;
			$aIntervals[$i]['month'] 			= date('_ym', $iStartDateUnix);
			$aIntervals[$i]['ym'] 				= date('ym', $iStartDateUnix);
			$aIntervals[$i]['Y'] 				= date('Y', $iStartDateUnix);
			$aIntervals[$i]['y'] 				= date('y', $iStartDateUnix);
			$aIntervals[$i]['month_start'] 		= date('Y-m-01 00:00:00', $iStartDateUnix);
			$aIntervals[$i]['month_end'] 		= date('Y-m-t 23:59:59', $iStartDateUnix);
			$aIntervals[$i]['ym01']				= date('Y-m-01', $iStartDateUnix);
			$aIntervals[$i]['day']				= date('j', $iStartDateUnix);
			$aIntervals[$i]['days']				= array(date('j', $iStartDateUnix));
			$aIntervals[$i]['weekday']			= date('N', $iStartDateUnix);
			$aIntervals[$i]['key']              = date('Y-m-d 00:00:00', $iStartDateUnix);

			/* Check if it's a full day interval */
			$sFullStartDate = date('Y-m-d 00:00:00', $iStartDateUnix);
			$sFullEndDate	= date('Y-m-d 23:59:59', $iEndDateUnix);

			if($aIntervals[$i]['start_date'] == $sFullStartDate && $aIntervals[$i]['end_date'] == $sFullEndDate)
				$aIntervals[$i]['full_day'] = true;
			else
				$aIntervals[$i]['full_day'] = false;

			$i++;
		}

		return $aIntervals;
	}


	/**
	 * Split an interval by weeks
	 * By default, the interval is also split by month
	 * @param string $sStartDate
	 * @param string $sEndDate
	 * @return array
	 */
	public static function getWeekIntervals($sStartDate, $sEndDate)
	{
		$i = 1;

		/* Interval limits as a unix timestamp */
		$iOriginalStartDateUnix = strtotime($sStartDate);
		$iOriginalEndDateUnix	= strtotime($sEndDate);

		/* Variable that will be incremented - defaults to initial start date */
		$iDateUnix = $iOriginalStartDateUnix;

		/* Holds the result */
		$aIntervals = array();

		/* As long as this is true, the while will loop */
		$bLoop = true;

		while($bLoop && $iDateUnix <= $iOriginalEndDateUnix)
		{
			$iStartDateUnix = $iDateUnix;

			if(date('N', $iStartDateUnix) == 7)
				$iEndDateUnix = strtotime(date('Y-m-d 23:59:59', $iStartDateUnix));
			else
				$iEndDateUnix 	= strtotime(date('Y-m-d 23:59:59', strtotime('next sunday', $iStartDateUnix)));

			/* Split month */
			if(date('m', $iStartDateUnix) != date('m', $iEndDateUnix))
			{
				$iEndDateUnix = strtotime(date('Y-m-t 23:59:59', $iStartDateUnix));
				$iDateUnix = $iEndDateUnix + 1;
			}
			else
			{
				$iDateUnix = strtotime('next monday', $iEndDateUnix);
			}

			/* check for the end of interval */
			if($iEndDateUnix > $iOriginalEndDateUnix)
			{
				$bLoop = false;
				$iEndDateUnix = $iOriginalEndDateUnix;
			}

			$aIntervals[$i]['type']				= 'weekly';
			$aIntervals[$i]['start_date'] 		= date('Y-m-d H:i:s', $iStartDateUnix);
			$aIntervals[$i]['end_date'] 		= date('Y-m-d H:i:s', $iEndDateUnix);
			$aIntervals[$i]['start_date_unix'] 	= $iStartDateUnix;
			$aIntervals[$i]['end_date_unix'] 	= $iEndDateUnix;
			$aIntervals[$i]['month'] 			= date('_ym', $iStartDateUnix);
			$aIntervals[$i]['ym'] 				= date('ym', $iStartDateUnix);
			$aIntervals[$i]['Y'] 				= date('Y', $iStartDateUnix);
			$aIntervals[$i]['y'] 				= date('y', $iStartDateUnix);
			$aIntervals[$i]['month_start'] 		= date('Y-m-01 H:i:s', $iStartDateUnix);
			$aIntervals[$i]['month_end'] 		= date('Y-m-t H:i:s', $iEndDateUnix);
			$aIntervals[$i]['ym01']				= date('Y-m-01', $iStartDateUnix);
			$aIntervals[$i]['days'] 			= range(date('j', $iStartDateUnix), date('j', $iEndDateUnix));

			/* Set key - already monday */
			if(date('N', $iStartDateUnix) == 1)
				$aIntervals[$i]['key'] = date('Y-m-d 00:00:00', $iStartDateUnix);
			else
				$aIntervals[$i]['key'] = date('Y-m-d 00:00:00', strtotime('last monday', $iStartDateUnix));

			$i++;
		}

		return $aIntervals;
	}


	/**
	 * Split an interval in to monthly chunks
	 * @param string $sStartDate
	 * @param string $sEndDate
	 * @return array
	 */
	public static function getMonthIntervals($sStartDate, $sEndDate)
	{
		$i = 1;

		/* Interval limits as a unix timestamp */
		$iOriginalStartDateUnix = strtotime($sStartDate);
		$iOriginalEndDateUnix	= strtotime($sEndDate);

		/* Variable that will be incremented - defaults to initial start date */
		$iDateUnix = $iOriginalStartDateUnix;

		/* Holds the result */
		$aIntervals = array();

		/* As long as this is true, the while will loop */
		$bLoop = true;

		while($bLoop && $iDateUnix <= $iOriginalEndDateUnix)
		{
			$iStartDateUnix = $iDateUnix;
			$iEndDateUnix 	= strtotime(date('Y-m-t 23:59:59', $iStartDateUnix));

			$iDateUnix = $iEndDateUnix + 1;

			/* check for the end of interval */
			if($iEndDateUnix > $iOriginalEndDateUnix)
			{
				$bLoop = false;
				$iEndDateUnix = $iOriginalEndDateUnix;
			}

			$aIntervals[$i]['type']				= 'monthly';
			$aIntervals[$i]['start_date'] 		= date('Y-m-d H:i:s', $iStartDateUnix);
			$aIntervals[$i]['end_date'] 		= date('Y-m-d H:i:s', $iEndDateUnix);
			$aIntervals[$i]['start_date_unix'] 	= $iStartDateUnix;
			$aIntervals[$i]['end_date_unix'] 	= $iEndDateUnix;
			$aIntervals[$i]['month'] 			= date('_ym', $iStartDateUnix);
			$aIntervals[$i]['ym'] 				= date('ym', $iStartDateUnix);
			$aIntervals[$i]['Y'] 				= date('Y', $iStartDateUnix);
			$aIntervals[$i]['y'] 				= date('y', $iStartDateUnix);
			$aIntervals[$i]['month_start'] 		= date('Y-m-01 H:i:s', $iStartDateUnix);
			$aIntervals[$i]['month_end'] 		= date('Y-m-t H:i:s', $iEndDateUnix);
			$aIntervals[$i]['ym01']				= date('Y-m-01', $iStartDateUnix);
			$aIntervals[$i]['days'] 			= range(date('j', $iStartDateUnix), date('j', $iEndDateUnix));
			$aIntervals[$i]['key']              = date('Y-m-01 00:00:00', $iStartDateUnix);

			/* Check if it's a full month interval */
			$sFullStartDate = date('Y-m-01 00:00:00', $iStartDateUnix);
			$sFullEndDate	= date('Y-m-t 23:59:59', $iEndDateUnix);

			if($aIntervals[$i]['start_date'] == $sFullStartDate && $aIntervals[$i]['end_date'] == $sFullEndDate)
				$aIntervals[$i]['full_month'] = true;
			else
				$aIntervals[$i]['full_month'] = false;

			$i++;
		}

		return $aIntervals;
	}


	public static function getIntervalDatesByRangeType($sRangeType)
	{
		SWITCH($sRangeType)
		{
			case 'today':
				$sStartDate		= date('Y-m-d');
				$sEndDate		= $sStartDate;
				break;

			case 'yesterday':
			case 'previous_day':
				$sStartDate		= date('Y-m-d', strtotime(date('Y-m-d 00:00:00')) - 1);
				$sEndDate		= $sStartDate;
				break;

			case 'this_week':
			case 'current_week':
				if(date('w') == 1)
					$sStartDate = date('Y-m-d');
				else
					$sStartDate = date('Y-m-d', strtotime('last monday'));

				$sEndDate = date('Y-m-d');
				break;

			case 'last_week':
			case 'previous_week':
				$sEndDate		= date('Y-m-d', strtotime('last sunday'));
				$sStartDate		= date('Y-m-d', strtotime('last monday', strtotime($sEndDate)));
				break;

			case 'this_month':
			case 'current_month':
				$sStartDate	= date('Y-m-01');
				$sEndDate   = date('Y-m-d');
				break;

			case 'last_month':
			case 'previous_month':
				$sEndDate		= date('Y-m-t', strtotime(date('Y-m-01 00:00:00')) - 1);
				$sStartDate		= date('Y-m-01', strtotime($sEndDate));
				break;

			case 'last_7_days':
			case 'last_seven_days':
			case 'previous_7_days':
			case 'previous_7_days':
				$sEndDate		= date('Y-m-d', strtotime(date('Y-m-d 00:00:00')) - 1);
				$sStartDate		= date('Y-m-d', strtotime('-7 days', strtotime(date('Y-m-d 00:00:00'))));
				break;

			case 'last_30_days':
			case 'previous_30_days':
				$sEndDate		= date('Y-m-d', strtotime(date('Y-m-d 00:00:00')) - 1);
				$sStartDate		= date('Y-m-d', strtotime('-30 days', strtotime(date('Y-m-d 00:00:00'))));
				break;

			case 'last_two_months':
			case 'last_2_months':
			case 'previous_two_months':
			case 'previous_2_months':
				$sEndDate		= date('Y-m-t', strtotime(date('Y-m-01 00:00:00')) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sEndDate));
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDate		= date('Y-m-01', strtotime($sStartDateTmp));
				break;

			case 'last_three_months':
			case 'last_3_months':
			case 'previous_three_months':
			case 'previous_3_months':
				$sEndDate		= date('Y-m-t', strtotime(date('Y-m-01 00:00:00')) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sEndDate));
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDate		= date('Y-m-01', strtotime($sStartDateTmp));
				break;

			case 'last_six_months':
			case 'last_6_months':
			case 'previous_six_months':
			case 'previous_6_months':
				$sEndDate		= date('Y-m-t', strtotime(date('Y-m-01 00:00:00')) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sEndDate));
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDateTmp	= date('Y-m-01 00:00:00', strtotime($sStartDateTmp) - 1);
				$sStartDate		= date('Y-m-01', strtotime($sStartDateTmp));
				break;

			case 'last_12_months':
			case 'last_twelve_months':
			case 'previous_twelve_months':
			case 'previous_12_months':
				$sEndDate	    = date('Y-m-t', strtotime(date('Y-m-01 00:00:00')) - 1);
				$sStartDate     = date('Y-m-01', strtotime($sEndDate) - 60*60*24*364);
				break;

			case 'this_year':
				$sStartDate = date('Y-01-01');
				$sEndDate = date('Y-m-d');
				break;

			case 'last_year':
			case 'previous_year':
				$iPreviousYear = date('Y') - 1;
				$sStartDate = $iPreviousYear . '-01-01';
				$sEndDate = $iPreviousYear . '-12-31';
				break;

			case 'custom':
				$sStartDate = '';
				$sEndDate = '';
				break;

			default:
				$sStartDate = '';
				$sEndDate = '';
		}

		return array(
			'start_date' => $sStartDate,
			'end_date' => $sEndDate,
		);
	}

}