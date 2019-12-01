<?php
	function lineGraphTeamQuery($connection, $teamID){
		$query = "SELECT date, GoalsFor, GoalsAgainst, COUNT(IF(team = ".$teamID.", 1, NULL))+GoalsFor AS ShotsFor, "
			."COUNT(IF(team = ".$teamID.", NULL, 1))+GoalsAgainst AS ShotsAgainst, Powerplays, PenaltyKills FROM "
			."(SELECT date, idgames, COUNT(IF(team = ".$teamID.", 1, NULL)) AS GoalsFor, COUNT(IF(team = ".$teamID.", NULL, 1)) "
			."AS GoalsAgainst, IF(home = ".$teamID.", homePPOpps, awayPPOpps) AS PowerPlays, IF(home = ".$teamID.", awayPPOpps, homePPOpps) "
			."AS PenaltyKills FROM (SELECT game, team FROM NHLapiDB.goals WHERE shootout=false) AS GOALS JOIN "
			."(SELECT date(date) AS date, idgames, home, homePPOpps, awayPPOpps FROM NHLapiDB.games WHERE home = ".$teamID." OR away = ".$teamID.") "
			."AS GAMES ON game=idgames GROUP BY date) AS DATA JOIN (SELECT game, team FROM NHLapiDB.shots WHERE shootout=false) "
			."AS SHOTS ON game=idgames GROUP BY DATE;";
		//echo $query;
		return mysqli_query($connection, $query);
		
	}
	
	function radarGraphTeamAvgs($connection){
		$query = "SELECT goals/gp AS avgGoals, shots/gp AS avgShots, pps/gp AS avgPPs " 
				."FROM (select count(*) as goals from NHLapiDB.goals where shootout=false) AS GOALS, " 
				."(select count(*) as shots from NHLapiDB.shots where shootout=false) AS SHOTS, "
				."(select count(*) as gp, sum(homePPOpps)+sum(awayPPOpps) as pps from NHLapiDB.games) AS GP;";
		return mysqli_fetch_assoc(mysqli_query($connection, $query));
		
	}
?>