<?php
    include('/var/www/dbConnection.php');
	ini_set('max_execution_time', 0);
	
    if($_SERVER["REQUEST_METHOD"] == "POST") {
		//set up api pull of game data
		$game_url = "https://statsapi.web.nhl.com/api/v1/game/".$_POST["gameNumber"]."/feed/live";
		$game_json = file_get_contents($game_url);
        $game_array = json_decode($game_json, true);
		
		$datetime = str_replace("_"," ",$game_array['metaData']['timeStamp']);
		$datetime = substr($datetime, 0, 4)."-" .substr($datetime, 4,4);
		$datetime = substr($datetime, 0, 7)."-" .substr($datetime, 7);
		
		$boxscore = $game_array['liveData']['boxscore'];
		$linescore = $game_array['liveData']['linescore'];
		
		//adds game data to the database
		$query = "INSERT INTO NHLapiDB.games (idgames, home, away, date, overtime, shootout, homeWIN, "
			." homePPGoals, homePPOpps, awayPPGoals, awayPPOpps) VALUES (".$game_array['gamePk'].", "
			.$game_array['gameData']['teams']['home']['id'].", ".$game_array['gameData']['teams']['away']['id'].", ".$datetime.", "
			.(($linescore['currentPeriod']==4)? 'true' : 'false').", "
			.(($linescore['currentPeriod']==5)? 'true' : 'false').", "
			.(($linescore['teams']['home']['goals']+$linescore['shootoutInfo']['home']['scores']
				> $linescore['teams']['away']['goals']+$linescore['shootoutInfo']['home']['scores'])? 'true' : 'false').", "
			.$boxscore['teams']['home']['teamStats']['teamSkaterStats']['powerPlayGoals'].", "
			.$boxscore['teams']['home']['teamStats']['teamSkaterStats']['powerPlayOpportunities'].", "
			.$boxscore['teams']['away']['teamStats']['teamSkaterStats']['powerPlayGoals'].", "
			.$boxscore['teams']['away']['teamStats']['teamSkaterStats']['powerPlayOpportunities'].");";	
		$runQuery = mysqli_query($connection, $query);
		
		//logs play time for each player adding to database if players dont exist
		foreach($boxscore['teams']['away']['players'] as $boxPlayer){
			$timePlayed = $boxPlayer['stats']['skaterStats']['timeOnIce']
				.$boxPlayer['stats']['goalieStats']['timeOnIce'];
			if($timePlayed)
				//TODO replace echos with SQL that adds player when fails
				echo $boxPlayer['person']['fullName']." - ".$boxPlayer['stats']['skaterStats']['timeOnIce']
					.$boxPlayer['stats']['goalieStats']['timeOnIce']."<br>";
		}
		foreach($boxscore['teams']['home']['players'] as $boxPlayer){
			$timePlayed = $boxPlayer['stats']['skaterStats']['timeOnIce']
				.$boxPlayer['stats']['goalieStats']['timeOnIce'];
			if($timePlayed)
				//TODO replace echos with SQL that adds player when fails
				echo $boxPlayer['person']['fullName']." - ".$boxPlayer['stats']['skaterStats']['timeOnIce']
					.$boxPlayer['stats']['goalieStats']['timeOnIce']."<br>";
		}
		break;

		//flag to capture if a goal is a penalty shot
		$penaltyShot = -2;
		
		//big loop creates a specific query for each event type in switch statement
		//after the switch executes the query if one exists for that event
		foreach($game_array['liveData']['plays']['allPlays'] as $event){
			switch($event["result"]["event"]) {
				case "Goal":
					$scorer = NULL;
					$assist1 = NULL;
					$assist2 = NULL;
					$goalie = NULL;
					foreach($event['players'] as $player)
						switch($player['playerType']){
							case "Scorer":
								$scorer = $player['player']['id'];
								break;
							case "Goalie":
								$goalie = $player['player']['id'];
								break;
							default:
								if($assist1)
									$assist2 = $player['player']['id'];
								else
									$assist1 = $player['player']['id'];
								break;
						}
					$query = "INSERT INTO NHLapiDB.goals (game, eventIdx, time, period, scorer, assist1, assist2, goalie, "
						."team, powerPlay, penaltyShot, shorthanded, xposition, yposition, shotType) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].", '00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$scorer.", ".$assist1.", ".$assist2.", ".$goalie.", ".$event['team']['id'].", ".(($event['result']['strength']['code']=='PPG')? 'true' : 'false').", "
						.(($event['about']['eventIdx']==$penaltyShot)? 'true' : 'false').", ".(($event['result']['strength']['code']=='SHG')? 'true' : 'false').", "
						.$event['coordinates']['x'].", ".$event['coordinates']['y'].", '".$event['result']['secondaryType']."');";
				
					break;
            
				case "Shot":
					$shooter = NULL;
					$goalie = NULL;
					foreach($event['players'] as $player)
						if ($player['playerType']=='Shooter')
							$shooter = $player['player']['id'];
						else
							$goalie = $player['player']['id'];
					$query = "INSERT INTO NHLapiDB.shots (game, eventIdx, time, period, shooter, goalie, team, xposition, yposition, shotType) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].", '00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$shooter.", ".$goalie.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].", '"
						.$event['result']['secondaryType']."');";
					break;
				case "Blocked Shot":
					$shooter = NULL;
					$blocker = NULL;
					foreach($event['players'] as $player)
						if ($player['playerType']=='Shooter')
							$shooter = $player['player']['id'];
						else
							$blocker = $player['player']['id'];
					$query = "INSERT INTO NHLapiDB.blockedShots (game, eventIdx, time, period, shooter, blocker, team, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$shooter.", ".$blocker.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");";
					break;
				case "Faceoff":
					$winner = NULL;
					$loser = NULL;
					foreach($event['players'] as $player)
						if ($player['playerType']=='Winner')
							$winner = $player['player']['id'];
						else
							$loser = $player['player']['id'];
					$query = "INSERT INTO NHLapiDB.faceoffs (game, eventIdx, time, period, winner, loser, team, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$winner.", ".$loser.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");";
					break;
				case "Missed Shot":
					$shooter = $event[players][0]['player']['id'];
					$query = "INSERT INTO NHLapiDB.missedShots (game, eventIdx, time, period, shooter, team, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$shooter.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");"; 
					break;
				case "Penalty":
					$offender = NULL;
					$drew = NULL;
					foreach($event['players'] as $player)
						if ($player['playerType']=='DrewBy')
							$drew = $player['player']['id'];
						else
							$offender = $player['player']['id'];
					$query = "INSERT INTO NHLapiDB.penalties (game, eventIdx, time, period, offender, drew, team, "
						."severity, minutes, rule, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$offender.", ".$drew.", ".$event['team']['id'].", '".$event['result']['penaltySeverity']."', ".$event['result']['penaltyMinutes'].", '"
						.$event['result']['secondaryType']."', ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");";
					if($event['result']['penaltySeverity'] == "Penalty Shot")
						$penaltyShot = $event['about']['eventIdx']+1;
					$query = NULL;
					break;
				case "Takeaway":
					$taker = $event[players][0]['player']['id'];
					$query = "INSERT INTO NHLapiDB.takeaway (game, eventIdx, time, period, taker, team, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$taker.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");";
					break;
				case "Giveaway":
					$giver = $event[players][0]['player']['id'];
					$query = "INSERT INTO NHLapiDB.giveaway (game, eventIdx, time, period, giver, team, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$giver.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");";
					break;
				case "Hit":
					$hitter = NULL;
					$hittee = NULL;
					foreach($event['players'] as $player)
						if ($player['playerType']=='Hitter')
							$hitter = $player['player']['id'];
						else
							$hittee = $player['player']['id'];
					$query = "INSERT INTO NHLapiDB.hits (game, eventIdx, time, period, hitter, hittee, team, xposition, yposition) VALUES ( "
						.$game_array['gamePk'].", ".$event['about']['eventIdx'].",'00:".$event['about']['periodTime']."', ".$event['about']['period'].", "
						.$hitter.", ".$hittee.", ".$event['team']['id'].", ".$event['coordinates']['x'].", ".$event['coordinates']['y'].");";
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
    <form method = "post" action = "<?php echo $_SERVER['PHP_SELF'];?>">
		Game Number <input type="text" name="gameNumber"><br>
       <button type = "submit"> Get Game </button>
   </form>
</body>
</html>