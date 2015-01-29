<?php

/**
 * Creates next period end from current one.
 * @param  string $start
 * @return string
 */
function get_period_end($start)
{
	$monthGap = 1;
	
	$start = Carbon\Carbon::createFromFormat('Y-m-d', $start);
	
	$end = $start->copy()->addMonth();

	if($end->month - $start->month > $monthGap)
	{
		$end = $end->subMonths($end->month - $start->month - $monthGap)->endOfMonth();
	}

	return $end->subDay();
}