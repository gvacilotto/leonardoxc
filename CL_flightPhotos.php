<?
/************************************************************************/
/* Leonardo: Gliding XC Server					                        */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004-5 by Andreadakis Manolis                          */
/* http://sourceforge.net/projects/leonardoserver                       */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/


class flightPhotos {
	var $flightID;
	var $photosNum;
	var $photos;

	var $valuesArray;
	var $gotValues;

	function flightPhotos($flightID="") {
		if ($flightID!="") {
			$this->flightID=$flightID;
		}

	    //$this->valuesArray=array("flightID","path","name","description");

		$this->gotValues=0;
		$this->photosNum=0;
		$this->photos=array();
	}

	function getPhotoRelPath($id) {
		global $flightsWebPath;	
		if ($id>=$this->photosNum) return '';		
		return $flightsWebPath."/".$this->photos[$id]['path'].'/'.$this->photos[$id]['name'];		
	}

	function getPhotoAbsPath($id) {
		global $flightsAbsPath;	
		if ($id>=$this->photosNum) return '';		
		return $flightsAbsPath."/".$this->photos[$id]['path'].'/'.$this->photos[$id]['name'];		
	}

	function deletePhoto($photoNum,$updateFlightsTable=1) {
		if (!$this->gotValues) $this->getFromDB();
		
	
		$imgNormal=$this->getPhotoAbsPath($photoNum);
		$imgIcon=$imgNormal.'.icon.jpg';
		@unlink($imgNormal); 
		@unlink($imgNormal); 			
	
		
		global $db,$photosTable,$flightsTable;
		
		if ($updateFlightsTable) {
			$res= $db->sql_query("UPDATE $flightsTable SET hasPhotos=hasPhotos-1 WHERE flightID=".$this->flightID );
			if($res <= 0){   
				 echo "Error updating hasPhotos for flight".$this->flightID."<BR>";
			}
		}		
		
		$res= $db->sql_query("DELETE FROM  $photosTable WHERE ID=".$this->photos[$photoNum]['ID'] );
  		if($res <= 0){   
			 echo "Error deleting photo $photoNum for flight".$this->flightID."<BR>";
	    }


	}

	function deleteAllPhotos($updateFlightsTable=1) {
		if (!$this->gotValues) $this->getFromDB();
		
		foreach ( $this->photos as $photoNum=>$photoInfo) {
			$imgNormal=$this->getPhotoAbsPath($photoNum);
			$imgIcon=$imgNormal.'.icon.jpg';
			@unlink($imgNormal); 
			@unlink($imgNormal); 			
		}
		
		global $db,$photosTable,$flightsTable;
		
		if ($updateFlightsTable) {
			$res= $db->sql_query("UPDATE $flightsTable SET hasPhotos=0 WHERE flightID=".$this->flightID );
			if($res <= 0){   
				 echo "Error updating hasPhotos for flight".$this->flightID."<BR>";
			}
		}		
		
		$res= $db->sql_query("DELETE FROM  $photosTable WHERE flightID=".$this->flightID );
  		if($res <= 0){   
			 echo "Error deleting photos for flight".$this->flightID."<BR>";
	    }


	}
	
	function getFromDB() {
		global $db,$photosTable;
		$res= $db->sql_query("SELECT * FROM $photosTable WHERE flightID=".$this->flightID );
  		if($res <= 0){   
			 echo "Error getting photos from DB for flight".$this->flightID."<BR>";
		     return 0;
	    }

		$this->photosNum=0;
	    while ($row = $db->sql_fetchrow($res) ) {
			$this->photos[$this->photosNum]['ID']=$row['ID'];
			$this->photos[$this->photosNum]['path']=$row['path'];
			$this->photos[$this->photosNum]['name']=$row['name'];
			$this->photos[$this->photosNum]['description']=$row['description'];
			$this->photosNum++;			
		}

		$this->gotValues=1;
		return 1;
    }

	function putToDB($updateFlightsTable=1) {
		global $db,$photosTable,$flightsTable;

		// if (!$this->gotValues) $this->getFromDB();
		

		
		$res= $db->sql_query("DELETE FROM  $photosTable WHERE flightID=".$this->flightID );
  		if($res <= 0){   
			 echo "Error deleting photos for flight".$this->flightID."<BR>";
	    }
		
		foreach ( $this->photos as $photoNum=>$photoInfo) {
			$query="INSERT INTO $photosTable  ('flightID','path','name','description') VALUES (".
				$this->flightID.",'".prep_for_DB($photoInfo['path'])."','".
									 prep_for_DB($photoInfo['name'])."','".
									 prep_for_DB($photoInfo['description'])."' ) ";
		
			// echo $query;
			$res= $db->sql_query($query);
			if($res <= 0){
			  echo "Error putting photo for flight ".$this->flightID." to DB<BR>";
			  return 0;
			}		
		}
		
		if ($updateFlightsTable) {
			$res= $db->sql_query("UPDATE $flightsTable SET hasPhotos=".$this->photosNum." WHERE flightID=".$this->flightID );
			if($res <= 0){   
				 echo "Error updating hasPhotos for flight".$this->flightID."<BR>";
			}
		}	
		
		$this->gotValues=1;			
		return 1;
    }

}

?>