<?php

function addAllTeams($connection) {
	//set up api pull of active team data
    $team_url = "https://statsapi.web.nhl.com/api/v1/teams";
    $team_json = file_get_contents($team_url);
    $team_array = json_decode($team_json, true);

	//loop through active teams and create INSERT QUERY
	foreach($team_array['teams'] as $nextTeam) {
        $query = "INSERT into NHLapiDB.teams (idteams, teamName, location, division, conference, acronym) VALUES ("
			.$nextTeam['id'].",'".$nextTeam['teamName']."','".$nextTeam['locationName']."','".$nextTeam['division']['name']
			."','".$nextTeam['conference']['name']."','".$nextTeam['abbreviation']."');";
        $runQuery = mysqli_query($connection, $query);
		if(!$runQuery){
			//test is a bit different looks to see if team already exists before error message
			$query2 = "SELECT idteams FROM teams WHERE idteams = "
					.$nextTeam['id'].";";
				$runQuery = mysqli_query($connection, $query2);
				if(!$runQuery)
					echo "unsuccessful"."<br>".$query2."<br>";
				if (mysqli_num_rows($runQuery)==0)
					echo "ERROR: Team not added."."<br>".$query."<br>";
		}
    };
}

function addPlayer($connection, $pid, $tid, $active){
	//set up api pull of player data
	$player_url = "https://statsapi.web.nhl.com/api/v1/people/".$pid;
	$player_json = file_get_contents($player_url);
	$player_stuff = json_decode($player_json, true);
	$player = $player_stuff['people'][0];
							
	//add player to database						
	$query = "INSERT INTO NHLapiDB.players (idplayers, fName, lName, birthdate, nationality, height, weight,"
		." shootsLeft, rookie, active, currentTeam, primeNumber, primePosition) VALUES ("
		.$player['id'].", \"".$player['firstName']."\", \"".$player['lastName']."\", '"
		.$player['birthDate']."', '".$player['nationality']."', \"".$player['height'].", "
		.$player['weight'].", ".(($player['shootsCatches']=='L')? 'true' : 'false').", "
		.($player['rookie'] ? 'true' : 'false').", ".($active? 'true' : 'false').", "
		.$tid.", ".(($player['primaryNumber'])? $player['primaryNumber']:'NULL').", '".$player['primaryPosition']['abbreviation']."');";
	$runQuery = mysqli_query($connection, $query);
	if(!$runQuery){
		$query2 = "SELECT idplayers FROM players WHERE idplayers = ".$pid.";";
		$runQuery = mysqli_query($connection, $query2);
		if(!$runQuery)
			echo "unsuccessful"."<br>".$query2."<br>";
		if (mysqli_num_rows($runQuery)!=1)
			echo "unsuccessful"."<br>".$query."<br>";
	}
}

function addAllActivePlayers($connection) {
	//set up api pull of all teams with active rosters
	$team_url = "https://statsapi.web.nhl.com/api/v1/teams/?expand=team.roster";
    $team_json = file_get_contents($team_url);
    $team_array = json_decode($team_json, true);
        
    //Outer loop iterates through the teams 
    foreach($team_array['teams'] as $team) {
		$teamID = $team['id'];
		
		//query to pull all active players from a team
        $query = "SELECT idplayers FROM players WHERE currentTeam = ".$teamID." AND active = TRUE;";
        $runQuery = mysqli_query($connection, $query);
		if(!$runQuery)
			echo "unsuccessful"."<br>".$query."<br>";
            
        //Create array for the active players in the DB            
        $players = array();
        while ($result = mysqli_fetch_assoc($runQuery))
            array_push($players, $result['idplayers']);
 
        //Inner loop to iterate through the roster
        foreach($team['roster']['roster'] as $apiPlayer){
            //flag for if a player is missing   
			$add = TRUE;
            
			//check player against current roster to see if already active on team
            for($j = 0; $j < sizeOf($players) && $add; $j++)
                if ($apiPlayer['person']['id'] == $players[$j] ) {
					$players[$j] = NULL;
                    $add = FALSE;
                }
					
			//Those not active or on correct team are corrected or created if not in DB
            if($add) {
				//check if in database
                $query = "SELECT idplayers FROM players WHERE idplayers = "
					.$apiPlayer['person']['id'].";";
				$runQuery = mysqli_query($connection, $query);
				if(!$runQuery)
					echo "unsuccessful"."<br>".$query."<br>";
				if (mysqli_num_rows($runQuery)>0){
					$query = "UPDATE NHLapiDB.players SET active = TRUE, currentTeam = ".$teamID
						." WHERE idplayers = ".$apiPlayer['person']['id'].";";
					$runQuery = mysqli_query($connection, $query);
					if(!$runQuery)
						echo "unsuccessful"."<br>".$query."<br>";
				}else{
					addPlayer($connection, $apiPlayer['person']['id'], $teamID, true);
				}
            }
        }
		//Those no longer active on the team are made inactive
        for ($j = 0; $j < sizeOf($players); $j++)
            if ($players[$s]){
				$query = "UPDATE NHLapiDB.players SET active = FALSE WHERE idplayers = ".$players[$s].";";
				$runQuery = mysqli_query($connection, $query);
				if(!$runQuery)
					echo "unsuccessful"."<br>".$query."<br>";
			}
    }
}

function addGameData($connection, $gid){
	//set up api pull of game data
	$game_url = "https://statsapi.web.nhl.com/api/v1/game/".$gid."/feed/live";
	$game_json = file_get_contents($game_url);
    $game_array = json_decode($game_json, true);
	
	$datetime = str_replace("T"," ", $game_array['gameData']['datetime']['dateTime']);
	$datetime = str_replace("Z","", $datetime);
		
	$boxscore = $game_array['liveData']['boxscore'];
	$linescore = $game_array['liveData']['linescore'];
		
	//adds game data to the database
	$query = "INSERT INTO NHLapiDB.games (idgames, home, away, date, overtime, shootout, homeWIN, "
		." homePPGoals, homePPOpps, awayPPGoals, awayPPOpps) VALUES (".$game_array['gamePk'].", "
		.$game_array['gameData']['teams']['home']['id'].", ".$game_array['gameData']['teams']['away']['id'].", '".$datetime."', "
		.(($linescore['currentPeriod']==4)? 'true' : 'false').", "
		.(($linescore['currentPeriod']==5)? 'true' : 'false').", "
		.(($linescore['teams']['home']['goals']+$linescore['shootoutInfo']['home']['scores']
			> $linescore['teams']['away']['goals']+$linescore['shootoutInfo']['home']['scores'])? 'true' : 'false').", "
		.$boxscore['teams']['home']['teamStats']['teamSkaterStats']['powerPlayGoals'].", "
		.$boxscore['teams']['home']['teamStats']['teamSkaterStats']['powerPlayOpportunities'].", "
		.$boxscore['teams']['away']['teamStats']['teamSkaterStats']['powerPlayGoals'].", "
		.$boxscore['teams']['away']['teamStats']['teamSkaterStats']['powerPlayOpportunities'].");";	
	$runQuery = mysqli_query($connection, $query);
	if(!$runQuery)
		echo "unsuccessful on:"."<br>".$query."<br>";
		
	//logs play time for each player adding to database if players dont exist
	foreach($boxscore['teams']['away']['players'] as $boxPlayer){
		$timePlayed = $boxPlayer['stats']['skaterStats']['timeOnIce']
			.$boxPlayer['stats']['goalieStats']['timeOnIce'];
		if($timePlayed){
			addPlayer($connection, $boxPlayer['person']['id'], "NULL", false);
			$query = "INSERT INTO NHLapiDB.gamesPlayedIn (game, player, team, time) VALUES ( ".$game_array['gamePk'].", "
				.$boxPlayer['person']['id'].", ".$game_array['gameData']['teams']['away']['id'].", '00:".$timePlayed."');";
			$runQuery = mysqli_query($connection, $query);
			if(!$runQuery)
				echo "unsuccessful on:"."<br>".$query."<br>";
		}
	}
	foreach($boxscore['teams']['home']['players'] as $boxPlayer){
		$timePlayed = $boxPlayer['stats']['skaterStats']['timeOnIce']
			.$boxPlayer['stats']['goalieStats']['timeOnIce'];
		if($timePlayed){
			addPlayer($connection, $boxPlayer['person']['id'], "NULL", false);
			$query = "INSERT INTO NHLapiDB.gamesPlayedIn (game, player, team, time) VALUES ( ".$game_array['gamePk'].", "
				.$boxPlayer['person']['id'].", ".$game_array['gameData']['teams']['home']['id'].", '00:".$timePlayed."');";
			$runQuery = mysqli_query($connection, $query);
			if(!$runQuery)
				echo "unsuccessful on:"."<br>".$query."<br>";
		}
	}

	//flag to capture if a goal is a penalty shot
	$penaltyShot = -2;
	$home = $game_array['gameData']['teams']['home']['id'];
	//homeStartsLeft boolean
	$hSL = ($linescore['periods'][0]['home']['rinkSide']=="left");
	echo (($hSL)? 'Home starts left  ':'Home starts right  ').$home.' is home'."<br>";
	//big loop creates a specific query for each event type in switch statement
	//after the switch executes the query if one exists for that event
	foreach($game_array['liveData']['plays']['allPlays'] as $event){
		$coordMod = (($hSL)?1:-1) * (($event['team']['id']==$home)?1:-1) * (($event['about']['period']!=2)?1:-1);
		switch($event["result"]["event"]) {
			case "Goal":
				$scorer = "NULL";
				$assist1 = "NULL";
				$assist2 = "NULL";
				$goalie = "NULL";
				foreach($event['players'] as $player)
					switch($player['playerType']){
						case "Scorer":
							$scorer = $player['player']['id'];
							break;
						case "Goalie":
							$goalie = $player['player']['id'];
							break;
						default:
							if($assist1 != "NULL")
								$assist2 = $player['player']['id'];
							else
								$assist1 = $player['player']['id'];
							break;
					}
				$query = "INSERT INTO NHLapiDB.goals (game, eventIdx, time, period, scorer, assist1, assist2, goalie, "
					."team, powerPlay, penaltyShot, shorthanded, xposition, yposition, shotType, shootout) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].", '00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$scorer.", ".$assist1.", ".$assist2.", ".$goalie.", ".$event['team']['id'].", ".(($event['result']['strength']['code']=='PPG')? 'true' : 'false').", "
					.(($event['about']['eventIdx']==$penaltyShot)? 'true' : 'false').", ".(($event['result']['strength']['code']=='SHG')? 'true' : 'false').", "
					.$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.", '".$event['result']['secondaryType']."', "
					.(($event["about"]["period"]==5)? 'true':'false').");";		
				break;
            
			case "Shot":
				$shooter = "NULL";
				$goalie = "NULL";
				foreach($event['players'] as $player)
					if ($player['playerType']=='Shooter')
						$shooter = $player['player']['id'];
					else
						$goalie = $player['player']['id'];
				$query = "INSERT INTO NHLapiDB.shots (game, eventIdx, time, period, shooter, goalie, team, xposition, yposition, shotType, shootout) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].", '00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$shooter.", ".$goalie.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.", '"
					.$event['result']['secondaryType']."', ".(($event["about"]["period"]==5)? 'true':'false').");";
				break;
			
			case "Blocked Shot":
				$shooter = "NULL";
				$blocker = "NULL";
				foreach($event['players'] as $player)
					if ($player['playerType']=='Shooter')
						$shooter = $player['player']['id'];
					else
						$blocker = $player['player']['id'];
				$query = "INSERT INTO NHLapiDB.blockedShots (game, eventIdx, time, period, shooter, blocker, team, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$shooter.", ".$blocker.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.");";
				break;
			
			case "Faceoff":
				$winner = "NULL";
				$loser = "NULL";
				foreach($event['players'] as $player)
					if ($player['playerType']=='Winner')
						$winner = $player['player']['id'];
					else
						$loser = $player['player']['id'];
				$query = "INSERT INTO NHLapiDB.faceoffs (game, eventIdx, time, period, winner, loser, team, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$winner.", ".$loser.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.");";
				break;
			
			case "Missed Shot":
				$shooter = $event[players][0]['player']['id'];
				$query = "INSERT INTO NHLapiDB.missedShots (game, eventIdx, time, period, shooter, team, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$shooter.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.");"; 
				break;
			
			case "Penalty":
				$offender = "NULL";
				$drew = "NULL";	
				foreach($event['players'] as $player)
					if ($player['playerType']=='DrewBy')
						$drew = $player['player']['id'];
					else
						$offender = $player['player']['id'];
				$query = "INSERT INTO NHLapiDB.penalties (game, eventIdx, time, period, offender, drew, team, "
					."severity, minutes, rule, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$offender.", ".$drew.", ".$event['team']['id'].", '".$event['result']['penaltySeverity']."', ".$event['result']['penaltyMinutes'].", '"
					.$event['result']['secondaryType']."', ".(($event['coordinates']['x']==NULL) ? 'NULL':$event['coordinates']['x']).", "
					.(($event['coordinates']['y']==NULL) ? 'NULL':$event['coordinates']['y']).");";
				if($event['result']['penaltySeverity'] == "Penalty Shot")
					$penaltyShot = $event['about']['eventIdx']+1;
				break;
			
			case "Takeaway":
				$taker = $event[players][0]['player']['id'];
				$query = "INSERT INTO NHLapiDB.takeaway (game, eventIdx, time, period, taker, team, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$taker.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.");";
				break;
			
			case "Giveaway":
				$giver = $event[players][0]['player']['id'];
				$query = "INSERT INTO NHLapiDB.giveaway (game, eventIdx, time, period, giver, team, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$giver.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.");";
				break;
			
			case "Hit":
				$hitter = "NULL";
				$hittee = "NULL";
				foreach($event['players'] as $player)
					if ($player['playerType']=='Hitter')
						$hitter = $player['player']['id'];
					else
						$hittee = $player['player']['id'];
				$query = "INSERT INTO NHLapiDB.hits (game, eventIdx, time, period, hitter, hittee, team, xposition, yposition) VALUES ( "
					.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
					.$hitter.", ".$hittee.", ".$event['team']['id'].", ".$event['coordinates']['x']*$coordMod.", ".$event['coordinates']['y']*$coordMod.");";
				break;
			
			default:
				$query = NULL;
				break;
		}
		if($query){
			$runQuery = mysqli_query($connection, $query);
			if(!$runQuery)
				echo "unsuccessful on:"."<br>".$query."<br>";
		}
	}
	
}

function removeGameData($connection, $gid){
	$query = "DELETE FROM NHLapiDB.games WHERE idgames = ".$gid.";";	
	$runQuery = mysqli_query($connection, $query);
	if(!$runQuery)
		echo "unsuccessful on:"."<br>".$query."<br>";
}

function addGamesSince($connection, $gid){
	//creating starting point if none exists
	if(!$gid){
		$query = "SELECT MAX(idgames) AS 'last' FROM NHLapiDB.games;";
		$runQuery = mysqli_query($connection, $query);
		if(!$runQuery){
			echo "unsuccessful on:"."<br>".$query."<br>";
			return;
		}
		$result = mysqli_fetch_assoc($runQuery);
		$gid = $result['last'];
		$gid = $gid + 1;
	}
	
	//fence post get api data for first game to be added
	$game_url = "https://statsapi.web.nhl.com/api/v1/game/".$gid."/feed/live";
	$game_json = file_get_contents($game_url);
    $game_array = json_decode($game_json, true);
	//loop adding a game then loading api for next
	while ($game_array["gameData"]["status"]["abstractGameState"] == "Final") {
		addGameData($connection, $gid);
		$gid = $gid + 1;
		$game_url = "https://statsapi.web.nhl.com/api/v1/game/".$gid."/feed/live";
		$game_json = file_get_contents($game_url);
		$game_array = json_decode($game_json, true);
	}	
}

?>