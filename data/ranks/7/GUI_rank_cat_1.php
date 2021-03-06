<?
/************************************************************************/
/* Leonardo: Gliding XC Server					                                */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004-5 by Andreadakis Manolis                          */
/* http://sourceforge.net/projects/leonardoserver                       */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/

//-----------------------------------------------------------------------
//-----------------------  custom league --------------------------------
//-----------------------------------------------------------------------
	// 	sport class ,   category=1";
	// Some config
	$cat=1; // pg

	$rankNum=1;
	$customFormatFunction="sec2Time24h";
	$customRankHeader=_TOTAL_DURATION;
	
	$countHowMany= 0; // special case , count all flights
	
	$ranksList[7]['seasons']['seasons']['2009']=array('start'=>'2009-07-01','end'=>'2009-09-15');
	
	require_once dirname(__FILE__)."/common_pre.php";

	$query = "SELECT $flightsTable.ID, userID, takeoffID , userServerID,
  				 gliderBrandID, $flightsTable.glider as glider,cat,
  				 FLIGHT_POINTS  , FLIGHT_KM, BEST_FLIGHT_TYPE, DURATION 
  	FROM $flightsTable,$pilotsTable
  	WHERE (userID!=0 AND  private=0)
  		AND $flightsTable.userID=$pilotsTable.pilotID 
  		AND $flightsTable.userServerID=$pilotsTable.serverID
  		$where_clause ";

	//echo $query;
require_once dirname(__FILE__)."/common.php";

?>