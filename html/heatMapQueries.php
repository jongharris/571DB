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
	
	function heatMapTeamGSFA($connection, $teamid){
		$xpoints = array();
		$ypoints = array();
		$xpoints2 = array();
		$ypoints2 = array();
		$xpoints3 = array();
		$ypoints3 = array();
		$xpoints4 = array();
		$ypoints4 = array();


		$queryHeat = "SELECT x, y, team, goal FROM (select idgames from NHLapiDB.games where home=".$teamid." or away=".$teamid
			." ) AS GP JOIN (select game, xposition as x, yposition as y, team, true as goal from NHLapiDB.goals where shootout = false "
			."UNION select game, xposition as x, yposition as y, team, false as goal from NHLapiDB.shots where shootout = false) AS SHOTS ON idgames=game;";
		
		$runqueryHeat = mysqli_query($connection, $queryHeat);
		if(!$runqueryHeat)
			echo "error";
		else
			while ($result = mysqli_fetch_assoc($runqueryHeat)){
				if($result['team']==$teamid && $result['goal']){
					array_push($xpoints, $result['x']);
					array_push($ypoints, $result['y']);
				}else if ($result['team']==$teamid){
					array_push($xpoints2, $result['x']);
					array_push($ypoints2, $result['y']);
				}else if ($result['goal']){
					array_push($xpoints3, -1*$result['x']);
					array_push($ypoints3, -1*$result['y']);
				}else{
					array_push($xpoints4, -1*$result['x']);
					array_push($ypoints4, -1*$result['y']);
				}
			}
			
		return array($xpoints, $ypoints, $xpoints2, $ypoints2, $xpoints3, $ypoints3, $xpoints4, $ypoints4);
	}
?>