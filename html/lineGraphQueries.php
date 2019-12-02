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
		
		$runGraph = mysqli_query($connection, $query);
		
		$date = array();
		$goalsFor = array();
		$goalsAgainst = array();
		$shotsFor = array();
		$shotsAgainst = array();
		$ppFor = array();
		$ppAgainst = array();
		$gTotal = 0;
		$gaTotal = 0;
		$sTotal = 0;
		$saTotal = 0;
		$ppTotal = 0;
		$ppaTotal = 0;
		$gpTotal = 0;
		
       while ($lineData = mysqli_fetch_assoc($runGraph)) {
            array_push($date, $lineData['date']);
			array_push($goalsFor, $lineData['GoalsFor']);
			$gTotal = $gTotal + (int)$lineData['GoalsFor'];
            array_push($goalsAgainst, $lineData['GoalsAgainst']);
			$gaTotal = $gaTotal + (int)$lineData['GoalsAgainst'];
            array_push($shotsFor, $lineData['ShotsFor']);
			$sTotal = $sTotal + (int)$lineData['ShotsFor'];
            array_push($shotsAgainst, $lineData['ShotsAgainst']);
			$saTotal = $saTotal + (int)$lineData['ShotsAgainst'];
            array_push($ppFor, $lineData['Powerplays']);
			$ppTotal = $ppTotal + (int)$lineData['Powerplays'];
            array_push($ppAgainst, $lineData['PenaltyKills']);
			$ppaTotal = $ppaTotal + (int)$lineData['PenaltyKills'];
			$gpTotal ++;
		}
		
		return array($date,	$goalsFor, $goalsAgainst, $shotsFor, $shotsAgainst, $ppFor, $ppAgainst,
			$gTotal, $gaTotal, $sTotal, $saTotal, $ppTotal, $ppaTotal, $gpTotal);
		
	}
	
	function radarGraphTeamAvgs($connection){
		$query = "SELECT goals/gp AS avgGoals, shots/gp AS avgShots, pps/gp AS avgPPs " 
				."FROM (select count(*) as goals from NHLapiDB.goals where shootout=false) AS GOALS, " 
				."(select count(*) as shots from NHLapiDB.shots where shootout=false) AS SHOTS, "
				."(select count(*) as gp, sum(homePPOpps)+sum(awayPPOpps) as pps from NHLapiDB.games) AS GP;";
		return mysqli_fetch_assoc(mysqli_query($connection, $query));
		
	}

	function lineGraphPlayerQuery($connection, $playerID) {
		$query = "SELECT date, count(IF(scorer=".$playerID.", 1, NULL)) as goals, count(IF(scorer!=".$playerID." AND scorer IS NOT NULL, 1, NULL)) as assists
					FROM (Select idgames, date(date) as date from NHLapiDB.games) as DATES JOIN
					((Select game from NHLapiDB.gamesPlayedIn where player = ".$playerID.") as GP LEFT JOIN
					(SELECT game, scorer, assist1, assist2 FROM NHLapiDB.goals WHERE scorer= ".$playerID." 
					OR assist1 = ".$playerID." OR assist2 = ".$playerID.") AS POINTS ON GP.game=POINTS.game) ON idgames=GP.game GROUP BY date;";
		$runGraph = mysqli_query($connection, $query);
		$date = array();
		$goals = array();
		$assists = array();
		while ($lineData = mysqli_fetch_assoc($runGraph)) {
					array_push($date, $lineData['date']);
					array_push($goals, $lineData['goals']);
					array_push($assists, $lineData['assists']);
		}
		return array($date, $goals, $assists);
		
	}
	
	function radarGraphSkater($connection, $playerID){
		$query = "SELECT count(*) as gp, hits, sum(hitsT) as hitsT, hit, sum(hitT) as hitT, goals, sum(goalsT) as goalsT,
					assists, sum(assistsT) as assistsT, shots, sum(shotsT) as shotsT, drawnPs, sum(drawnPsT) as drawnPsT,
					takenPs, sum(takenPsT) as takenPsT, gives, sum(givesT) as givesT, takes, sum(takesT) as takesT, blocks, sum(blocksT) as blocksT from
					(select count(IF(hitter = ".$playerID." , 1, NULL)) as hits, count(IF(hittee = ".$playerID." , 1, NULL)) as hit from NHLapiDB.hits) AS HITS,
					(select count(IF(scorer = ".$playerID.", 1, NULL)) as goals, count(IF(assist1 = ".$playerID." OR assist2 = ".$playerID.", 1, NULL)) 
					as assists from NHLapiDB.goals where shootout=false) AS POINTS,
					(select count(IF(shooter = ".$playerID.", 1, NULL)) as shots from NHLapiDB.shots where shootout=false) AS SHOTS,
					(select count(IF(drew = ".$playerID.", 1, NULL)) as drawnPs, count(IF(offender = ".$playerID.", 1, NULL)) as takenPs from NHLapiDB.penalties) AS PIMS,
					(select count(IF(giver = ".$playerID.", 1, NULL)) as gives from NHLapiDB.giveaway) AS GIVE,
					(select count(IF(taker = ".$playerID.", 1, NULL)) as takes from NHLapiDB.takeaway) AS TAKE,
					(select count(IF(blocker = ".$playerID.", 1, NULL)) as blocks from NHLapiDB.blockedShots) AS BLOCKS,
					(select hitsT, hitT, goalsT, assistsT, shotsT, drawnPsT, takenPsT, givesT, takesT, count(*) as blocksT from
					(select game, team, hitsT, hitT, goalsT, assistsT, shotsT, drawnPsT, takenPsT, givesT, count(*) as takesT from
					(select game, team, hitsT, hitT, goalsT, assistsT, shotsT, drawnPsT, takenPsT, count(*) as givesT from
					(select game, team, hitsT, hitT, goalsT, assistsT, shotsT, count(IF(team = t2, NULL, 1)) as drawnPsT, count(IF(team = t2, 1, NULL))as takenPsT from
					(select game, team, hitsT, hitT, goalsT, assistsT, count(*)+goalsT as shotsT from
					(select game, team, hitsT, hitT, count(*) as goalsT, count(assist1)+count(assist2) as assistsT from
					(select game, team, count(IF(team = t2, 1, NULL)) as hitsT, count(IF(team = t2, NULL, 1)) as hitT from
					(select game, team from NHLapiDB.gamesPlayedIn where player = ".$playerID.") as gp JOIN
					(select game as g2, team as t2 from NHLapiDB.hits) as minihits on game=g2 group by game) as sub1 join
					(select game as g2, team as t2, scorer, assist1, assist2 from NHLapiDB.goals where shootout=false) as minipoints on game=g2 and team=t2 
					group by game) as sub2 join
					(select game as g2, team as t2 from NHLapiDB.shots where shootout=false) as minishots on game=g2 and team=t2 group by game) as sub3 join
					(select game as g2, team as t2 from NHLapiDB.penalties) as minipims on game=g2 group by game) as sub4 join
					(select game as g2, team as t2 from NHLapiDB.giveaway) as minigiveaways on game=g2 and team=t2 group by game) as sub5 join
					(select game as g2, team as t2 from NHLapiDB.takeaway) as minitakeaways on game=g2 and team=t2 group by game) as sub6 join
					(select game as g2, team as t2 from NHLapiDB.blockedShots) as miniblocks on game=g2 and team=t2 group by game) as teamdata;";
					
		$runQuery = mysqli_query($connection, $query);
		if(!$runQuery)
			echo "Error ";
		else
			$result = mysqli_fetch_assoc($runQuery);
		return $result;
		
	}
	

?>