<?php
//***Heat Map Queries***//
	function heatMapPlayerQueries($connection, $resultPlayer){
		$xpoints = array();
		$ypoints = array();
		$xpoints2 = array();
		$ypoints2 = array();
		//Faceoffs
		//$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.faceoffs;";
		//Goals
		$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.goals WHERE scorer = "
			.$resultPlayer['idplayers']." AND shootout=false;";
			
		//Shots
		$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.goals WHERE scorer = "
			.$resultPlayer['idplayers']." AND shootout=false;";
		$queryHeat2 = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.shots WHERE shooter = "
			.$resultPlayer['idplayers']." AND shootout=false;";
		$runqueryHeat2 = mysqli_query($connection, $queryHeat2);
		if(!$runqueryHeat2)
			echo "error";
		else
			while ($result = mysqli_fetch_assoc($runqueryHeat2)){
				array_push($xpoints2, $result['x']);
				array_push($ypoints2, $result['y']);
			}
		
		//Shot Attempts

		//$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.goals WHERE scorer = "
		//	.$resultPlayer['idplayers']." AND shootout=false;";
		
		$runqueryHeat = mysqli_query($connection, $queryHeat);
		if(!$runqueryHeat)
			echo "error";
		else
			while ($result = mysqli_fetch_assoc($runqueryHeat)){
				array_push($xpoints, $result['x']);
				array_push($ypoints, $result['y']);
			}
			
		
		return array($xpoints, $ypoints, $xpoints2, $ypoints2);
	}
?>