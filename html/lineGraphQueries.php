<?php
	function lineGraphTeamQueries($connection, $teamID){
		//Goals for and against
		$query = "SELECT date, COUNT(IF(team = ".$teamID.", 1, NULL)) AS GoalsFor, COUNT(IF(team = ".$teamID.", NULL, 1)) AS GoalsAgainst " 
					."FROM (SELECT game, team FROM NHLapiDB.goals WHERE shootout=false) AS GOALS JOIN "
					."(SELECT date(date) AS date, idgames FROM NHLapiDB.games WHERE home = ".$teamID." OR away = ".$teamID.") "
					."AS GAMES ON game=idgames GROUP BY date;";
		$return mysqli_query($connection, $query);
		
	}
?>