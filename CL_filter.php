<?
//************************************************************************
// Leonardo XC Server, http://leonardo.thenet.gr
//
// Copyright (c) 2004-8 by Andreadakis Manolis
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
// $Id: CL_filter.php,v 1.2 2010/02/27 22:40:51 manolis Exp $                                                                 
//
//************************************************************************

/*

this class compiles - decompiles a filter URL
to a more compact (binary?) format

FILTER_YEAR_select_op=%3E%3D
&FILTER_YEAR_select=2007
&FILTER_MONTH_YEAR_select_op=%3D
&FILTER_MONTH_YEAR_select_MONTH=02
&FILTER_MONTH_YEAR_select_YEAR=2010
&FILTER_dateType=DATE_RANGE
&FILTER_from_day_text=01.02.2010
&FILTER_to_day_text=15.02.2010
&FILTER_pilots_incl=3451%2C4220%2C4026%2C5717%2C2266%2C80%2C2784%2C7501%2C8803%2C
&FILTER_nacclubs101_incl=118%2C
&FILTER_countries_incl=DE%2CGR
&FILTER_takeoffs_incl=9705%2C9334%2C9888%2C9165%2C9014%2C9758%2C
&FILTER_nationality_incl=BB%2CBR%2CHR%2CFI%2CGR%2C
&FILTER_server_incl=1%2C2%2C4%2C8%2C
&FILTER_sex=M
&FILTER_cat1=1
&FILTER_cat2=2
&FILTER_linear_distance_op=%3E%3D
&FILTER_linear_distance_select=10
&FILTER_olc_distance_op=%3C%3D
&FILTER_olc_distance_select=100
&FILTER_olc_score_op=%3E%3D
&FILTER_olc_score_select=50
&FILTER_olc_type=FREE_FLIGHT
&FILTER_duration_op=%3E%3D
&FILTER_duration_hours_select=23
&FILTER_duration_minutes_select=12


add also 
- Extend Filter with checkbox categories for Paraglider LTF Class (LTF 1, 1-2, 2, 2-3, A, B, C, D, E, Open)
- Extend Filter with checkbox category for Hanggliders with Kingposts.
- Extend Filter page with Pilot Birthdate.
- Extend Filter page for Start type: foot, winch, microlight tow or e-motor (is to be included on GUI_flight_submit.php)

Integrate RSS Feed in Filterpage, to generate Feeds according to filter settings.
Ensure Filter bookmarks function correctly (check seasons mod).
Ensure Filter settings function correctly on all scoring lists.

*/

$filterOps=array(
/*
the opID is a byte


4 left most bits for the type (0-15) 
+ 5 bits for the id ( 0 - 15 ) 

we have 
0000 -> reserved
0010 0x20 (+32) -> date related 
0100 0x40 (+64) -> multiple items 
0110 0x60 (+96) -> simple integer select
1000 0x80 (+128)-> greater then, less than , equal  items

*/

// date related filter
// greater then, less than , equal  items
/* opID + 1 byte for operator + 2 bytes per date
 the 2 leftmost bits  are the operator 
 00 ->  =  (equal)
 01 -> <=  (less than)
 10 -> >=  (greater then )
 11 -> between  (needs 2 extra bytes 
 
 year  -> 8 bits ( 1900 + value  we can get up to year 2256 .. this should be enough ?!) 
 month -> 4 bits ( 0-15 ) 
 day   -> 5 bits ( 0-31 ) 
 total 19 bits -> 3 bytes
 
*/

0x20=>array("FILTER_DATE"),
// 0x21=>array("FILTER_DATE_range"),
0x22=>array("FILTER_PilotBirthdate"),

// multiple items inclusive
/* 
  opID 
  
  nationality and countries -> country code ie GRiBGiDE -> GR BG DE
  pilots   -> pilotID +i +pilotID +i .... + s
  takeoffs -> takeoffID +i+ takeoffID +r+ takeoffID  ... + s
  server   -> takeoffID +i+ takeoffID +i+ takeoffID  ...2 bytes
  nacclubs ->...
*/
0x40=>array("FILTER_pilots_incl",		'pilot',	'userID'),
0x41=>array("FILTER_countries_incl",	'country',	$waypointsTable.'.countryCode'),
0x42=>array("FILTER_takeoffs_incl",		'takeoff',	'takeoffID'),
0x43=>array("FILTER_nationality_incl",	'nationality',$pilotsTable.'.countryCode'),
0x44=>array("FILTER_server_incl",		'server',	$flightsTable.'.serverID'),
0x45=>array("FILTER_nacclubs_incl",		'nacclub',	$flightsTable.'.NACclubID'),

// simple select , or multiple choices 
/* opID + 2 bytes (32 bits) range 
*/
0x60=>array("FILTER_sex"),
0x61=>array("FILTER_cat"), 		// category pg,hg etc... 
0x62=>array("FILTER_class"),  		// class -> open, sport tandem etc...
0x63=>array("FILTER_olc_type"),
0x64=>array("FILTER_glider_cert"),	//
0x65=>array("FILTER_start_type"),	// Start type

// greater then, less than , equal  items
/* opID + 2 bytes (30 bits) range (0-16384) the 2 leftmost bits 
 are the operator 
 00 ->  =  (equal)
 01 -> <=  (less than)
 10 -> >=  (greater then )
*/
0x80=>array("FILTER_linear_distance"),
0x81=>array("FILTER_olc_distance"),
0x82=>array("FILTER_olc_score"),
0x83=>array("FILTER_duration"), // (in minutes)

);



if (0) {
	//echo leonardoFilter::getShort("10004",0);
	$filter=new LeonardoFilter();
	$filterString=$_GET['f'];
	if (!$filterString) $filterString="80000260000820C100A64100A741gr_de_us_fr.402466_4672_17282_13461.".
	"45101_234_235_271.";
	$filter->parseFilterString($filterString);
	echo "<PRE>";
	print_r($filter->filterArray);
	echo "</PRE>";
	
	echo $filter->makeClause();
	
	echo "<HR>".$filterString. "<HR>".$filter->makeFilterString();
}

class leonardoFilter {

	var $filterArray=array();

	function leonardoFilter() {

	}
	
	
	function getByte($filterStr,$start) {
		if ( strlen($filterStr) < ($start +2) ) return -1;

		$valHex=substr($filterStr,$start,2);
		if( preg_match("/[^0-9ABCDEF]+/i", $valHex) ) return -1;

		$val=hexdec($valHex);
		return $val;
	}
	
	function getShort($filterStr,$start){
		if ( strlen($filterStr) < ($start +4) ) return -1;
		
		$valHex=substr($filterStr,$start,4);
		if( preg_match("/[^0-9ABCDEF]+/i", $valHex) ) return -1;

		$val=hexdec($valHex);
		return $val;
	}
	
	function parseOp($filterStr,$start){
		global $filterOps;
		
		$op=$this->getByte($filterStr,$start);
		if ($op<0 ) return 0; // something is wrtong with the data, stop here
		
		// echo "#".dechex($op)."#";  
		
		if ( ($op & 0xf0) ==  0x40) {
		// multiple items inclusive
/* 
  opID 
  
  nationality and countries -> country code ie GR_BG_DE. -> GR BG DE
  pilots   -> pilotID _ pilotID . .... + .
  takeoffs -> takeoffID _ takeoffID _ takeoffID  + .
  server   -> takeoffID _ takeoffID _ takeoffID  
  nacclubs ->...
*/
			$i=2;
			$end=false;
			$values=array();
			$tmpStr='';			
			
			while (!$end) {
				$chr=$filterStr[$start+$i];
				$i++;
				
				
				if ($chr=='_' || $chr=='.') {
				
					if( preg_match("/[^0-9A-Z]+/i", $tmpStr) ) {
						return -1;
					}

					if ($chr=='.') $end=true;

					$values[]=$tmpStr;
					$tmpStr='';
					continue;
				}
			
				$tmpStr.=$chr;				
			}
			
			// echo "i=$i ***<BR>";
			if ( $op == 0x45 ) { 
				$nacid=	array_shift($values);
				$this->filterArray[]=array($op,$filterOps[$op][0],$values,$nacid);
			} else {
				$this->filterArray[]=array($op,$filterOps[$op][0],$values);
			}	
			return $i;
			

		} else if ( ($op & 0xf0) ==  0x80) {
		 //opID + 2 bytes (30 bits) range (0-16384) the 2 leftmost bits  are the operator 
		 /* 00 ->  =  (equal)
		    01 -> <=  (less than)
		    10 -> >=  (greater then ) */

			$val=$this->getShort($filterStr,$start+2);
			if ($val<0) return -1;
			
		 	$value=  $val&0x3fff;
			$operand=($val&0xC000 )>>14;
			//echo $operand."**";
			if ( $operand==0) $operand="=";
	 		else  if ( $operand==1) $operand="<=";			 
			else  if ( $operand==2) $operand=">=";
			else return 0; // error
			
			$this->filterArray[]=array($op,$filterOps[$op][0],$operand,$value);
			return 6;
		}
	  
	  	if ( ($op & 0xf0) ==  0x60) {
			// simple select , or multiple choices 
			/* opID + 2 bytes (32 bits) range 
			*/
			$val=$this->getShort($filterStr,$start+2);
			if ($val<0) return -1;
			
		 	$value=  $val& 0x3fff;			
			$this->filterArray[]=array($op,$filterOps[$op][0],$val);
			return 6;
		}
		
		// date related filter
// greater then, less than , equal  items
/* opID + 1 byte for operator + 3 bytes per date
 the 2 leftmost bits  are the operator 
 00 ->  =  (equal)
 01 -> <=  (less than)
 10 -> >=  (greater then )
 11 -> between  (needs 2 extra bytes 
 
 year  -> 8 bits ( 1900 + value  we can get up to year 2256 .. this should be enough ?!) 
 month -> 4 bits ( 0-15 ) 
 day   -> 5 bits ( 0-31 ) 
 total 19 bits -> 3 bytes
 
 12345678   1234   12345
 10000000   0101   00110   -> 1 0000 0000   1010 0110 ->  1 00 A6  + less then (01) -> 4100A6
                              0100  0000  0000 0000   0000 0000
							  1 0000 000  0 1010 0110
							  0100 0001 0000 0000 1010 0110
 128->2028  5       6 
 */
		 if ( ($op & 0xf0) ==  0x20) {

			$val1=$this->getShort($filterStr,$start+2);
			$val2=$this->getByte($filterStr,$start+6);
			if ($val1<0 || $val2<0) return -1;
			
			
			$val=($val1<<8 )+$val2;
			
		 	$value=$val & 0x3fffff;


			$year=1900+ ($value >>9) ;
			$month=($value >>5) & 0x00000f;
			$day = $value & 0x1F;


			$resLen=8;
			$operand=($val&0x00C00000) >> 22;
			
			if ( $operand==0) $operand="=";
	 		else  if ( $operand==1) $operand="<=";			 
			else  if ( $operand==2) $operand=">=";			
			else {
				$operand="between";
				$val1=$this->getShort($filterStr,$start+8);
				$val2=$this->getByte($filterStr,$start+12);
				if ($val1<0 || $val2<0) return -1;
			
				$val=($val1<<8 )+$val2;
				
				$value=$val & 0x3fffff;
	
				$year2=$value >>9 ;
				$month2=$value >>5 & 0x0f;
				$day2 = $value & 0x1F;	
				
				$year2=1900+ ($value >>9) ;
				$month2=($value >>5) & 0x00000f;
				$day2 = $value & 0x1F;
					
				$resLen=14;
			}
			
			$this->filterArray[]=array($op,$filterOps[$op][0],$operand,$year,$month,$day,$year2,$month2,$day2);
			return $resLen;
		}
		
		// all else failed
		return 0;
	}
	
	function parseFilterString($filterStr) {		
		if (!$filterStr) return;
		$filterStr=strtoupper($filterStr);
		
		$start=0;
		$filterStrLen=strlen($filterStr);
		
		do {
			$opLen=$this->parseOp($filterStr,$start);
			// echo "opLen= $opLen <BR>";
			$start+=$opLen;
			if ($opLen<0) {
				$start=0;
				$this->filterArray=array();
				break;
			}
		} while ($start <$filterStrLen && $opLen>0) ;
	
	}
	
	

function makeFilterString() {	
	global $filterOps;
	global $pilotsTable,$flightsTable;
	
	$filterStr='';
	
	foreach ($this->filterArray as $i=>$item) {
		$op=$item[0];
		$opName=$item[1];		
		

		if ( ($op & 0xf0) ==  0x40  ) {// multi values
			//print_r($item);
			$filterStr.=sprintf("%02X",$op);	
			
			if ($op==0x45) $filterStr.=$item[3].'_';
			
			foreach($item[2] as $item1) {
				 $filterStr.=$item1.'_';
			}
			$filterStr=substr($filterStr,0,-1).'.';
			continue;
		}

		if ( ($op & 0xf0) ==  0x60) {				
			$filterStr.=sprintf("%02X%04X",$op,$item[2]);	
			continue;
		}
	
		if ( ($op & 0xf0) ==  0x80) {
			$operand=$item[2];
			if ( $operand=='=') $operand=0;
			else  if ( $operand=='<=') $operand=1;			 
			else  if ( $operand=='>=') $operand=2;
			
			$operand=($operand<<14 )&0xC000;
			$value=  $item[3]&0x3fff;				
			$filterStr.=sprintf("%02X%04X",$op,$operand|$value);
			continue;
		}
	
		if ( ($op & 0xf0) ==  0x20) {
		/*
		            [0] => 32
            [1] => FILTER_DATE
            [2] => between
            [3] => 2028
            [4] => 5
            [5] => 6
            [6] => 2028
            [7] => 5
            [8] => 7

		*/
			$operand=$item[2];
			if ( $operand=='=') $operand=0;
			else  if ( $operand=='<=') $operand=1;			 
			else  if ( $operand=='>=') $operand=2;
			else  if ( $operand=='between') $operand=3;
			
			$operand=($operand<<22 )&0x00C00000;

			$year = ( ( $item[3]-1900) << 9 ) | 0x00000000 ;
			$month= ($item[4] <<  5 ) | 0x00000000  ;
			$day  = $item[5]  | 0x00000000 ;
			
			$filterStr.=sprintf("%02X%06X",$op,$operand|$year|$month|$day);
			
			if ( $item[2]=='between') {
				$year = ( ( $item[6]-1900) << 9 ) | 0x00000000 ;
				$month= ($item[7] <<  5 ) | 0x00000000  ;
				$day  = $item[8]  | 0x00000000 ;
			
				$filterStr.=sprintf("%06X",$year|$month|$day);			
			}
			continue;
		}
		
		} // foreach

		return	$filterStr;
	}

	function makeClause() {
		global $filterOps;
		global $pilotsTable,$flightsTable;
		
		$filter_clause="";
		
		$nacclub_clauses=array();
		$AND_clauses=array();
					
		foreach ($this->filterArray as $i=>$item) {
			$op=$item[0];
			$opName=$item[1];			
			
			
			if ($opName=='FILTER_DATE') {
				$opType=$item[2];
				if ($opType=="between"){ // RANGE
					$date1=sprintf("%04d%02d%02d",$item[3],$item[4],$item[5]);
					$date2=sprintf("%04d%02d%02d",$item[6],$item[7],$item[8]);
					$filter_clause.=" AND ( DATE_FORMAT(DATE,'%Y%m%d') >=  $date1 AND DATE_FORMAT(DATE,'%Y%m%d') <= $date2 ) ";
				} else if ( $item[3] && !$item[4] && !$item[5] ) {
					$filter_clause.=" AND DATE_FORMAT(DATE,'%Y') $opType ".$item[3]." ";
				} else 	if ( $item[3] && $item[4] ) {
					$filter_clause.=" AND ( DATE_FORMAT(DATE,'%Y%m') $opType ".sprintf("%04d%02d",$item[3].$item[4])." ) ";
				} 
				continue;
			}
			
			
			if ( ($op & 0xf0) ==  0x40  ) {// multi values
				//print_r($item);
				$clause='';
				$in_string='';

				foreach($item[2] as $item1) {
					$in_string.="'$item1',";
				}
				$in_string=substr($in_string,0,-1);
				
				$clause= $filterOps[$op][2].' IN ('.$in_string.') ';
				if ($filterOps[$op][1]=='nacclub') {
					$clause=" ($flightsTable.NACid=".$item[3]." AND $clause)";
					$nacclub_clauses[]='('.$clause.')';
				} else {
					$AND_clauses[]=$clause;
				}
				continue;
			}
	
			if ($opName=='FILTER_sex') {
				$filter_clause.=" AND $pilotsTable.Sex='".$item[2]."' ";
				continue;
			}
			if ($opName=='FILTER_olc_type') {
				$filter_clause.=" AND BEST_FLIGHT_TYPE='".$item[2]."' ";
				continue;
			}	
			if ($opName=='FILTER_cat') {
					$filter_clause.=" AND $flightsTable.cat & ".$item[2]." ";
					continue;
			}			
			if ($opName=='FILTER_class') {
				$filter_clause.=" AND $flightsTable.category & ".$item[2]." ";
				continue;
			}			
			if ($opName=='FILTER_glider_cert') {
				$filter_clause.=" AND $flightsTable.gliderCertCategory & ".$item[2]." ";
				continue;
			}		
			if ($opName=='FILTER_start_type') {
				$filter_clause.=" AND $flightsTable.startType & ".$item[2]." ";
				continue;
			}	

				
			if ($opName=='FILTER_linear_distance') 
				$filter_clause.=" AND LINEAR_DISTANCE ".$item[2]." ".($item[3]*1000)." ";
	
			if ($opName=='FILTER_olc_distance') 
				$filter_clause.=" AND FLIGHT_KM ".$item[2]." ".($item[3]*1000)." ";
	
			if ($opName=='FILTER_olc_score') 			
				$filter_clause.=" AND FLIGHT_POINTS ".$item[2]." ".$item[3]." ";
		
			if ($opName=='FILTER_olc_score') 			
				$filter_clause.=" AND FLIGHT_POINTS ".$item[2]." ".$item[3]." ";
			
			if ($opName=='FILTER_duration') 			
				$filter_clause.=" AND DURATION ".$item[2]." ".$item[3]." ";
			
		} // foreach

		//print_r($AND_clauses);		
		//print_r($nacclub_clauses);
		
		if (count($nacclub_clauses)>0) {
			$AND_clauses[]='('.implode(' OR ', $nacclub_clauses).')';
		}
		if (count($AND_clauses)>0) {
			$filter_clause.=' AND '.implode(' AND ', $AND_clauses);
		}
		
		return $filter_clause;		
		
	} // function
	
	
	

}

?>