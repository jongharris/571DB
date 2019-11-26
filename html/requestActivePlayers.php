<?php
    include('/var/www/dbConnection.php');
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $team_url = "https://statsapi.web.nhl.com/api/v1/teams/?expand=team.roster";
        $team_json = file_get_contents($team_url);
        $team_array = json_decode($team_json, true);
        
      //  echo $team_array['teams'][0]['roster']['roster'][0]['person']['fullName'];
        
        //Outer loop iterates through the teams
        //Note that i is NOT the ID for a team as some teams have ID's in the 50's
        //Note look up sizeOf for array bounds
       
        
        for($i = 0; $i <= 30; $i++) {
            echo $team_array['teams'][$i]['teamName']."</br>";
            //current teamID
            $currentID = $team_array['teams'][$i]['id'];

            //query to pull all active players from a team
            $query = "SELECT idplayers FROM players WHERE currentTeam = ".$currentID." AND active = TRUE;";
            $runQuery = mysqli_query($connection, $query);
            
            //Create array for the active players in the DB            
            $players = array();
            while ($result = mysqli_fetch_assoc($runQuery)) {
                //Store active players from the DB into an array
                array_push($players, $result['idplayers']);
            }
 
            //Inner loop to iterate through the roster
            for($k = 0; $k < sizeOf($team_array['teams'][$i]['roster']['roster']); $k++) {
               
                $add = TRUE;
                
                    for($j = 0; $j < sizeOf($players) && $add; $j++) {
                        
                        if ($team_array['teams'][$i]['roster']['roster'][$k]['person']['id'] == $players[$j] ) {
                            echo "found ".$players[$j];
							$players[$j] = NULL;
                            $add = FALSE;
                        } 
                        
                    } 
                
                    if($add) {
                        //insert and reset add to true
                        $query = "SELECT idplayers FROM players WHERE idplayers = "
							.$team_array['teams'][$i]['roster']['roster'][$k]['person']['id'].";";
						$runQuery = mysqli_query($connection, $query);
						if (mysqli_num_rows($runQuery)>0){
							$query = "UPDATE NHLapiDB.players SET active = TRUE, currentTeam = ".$currentID."WHERE idplayers = "
								.$team_array['teams'][$i]['roster']['roster'][$k]['person']['id'].";";
							$runQuery = mysqli_query($connection, $query);
						}else{
							$player_url = "https://statsapi.web.nhl.com/api/v1/people/".$team_array['teams'][$i]['roster']['roster'][$k]['person']['id'];
							$player_json = file_get_contents($player_url);
							$player_stuff = json_decode($player_json, true);
							
							$query = "INSERT INTO NHLapiDB.players (idplayers, fName, lName, birthdate, nationality, height, weight,"
							." shootsLeft, rookie, active, currentTeam, primeNumber, primePosition) VALUES ("
							.$player_stuff['people'][0]['id'].", '".$player_stuff['people'][0]['firstName']."', '".$player_stuff['people'][0]['lastName']."', '"
							.$player_stuff['people'][0]['birthDate']."', '".$player_stuff['people'][0]['nationality']."', \"".$player_stuff['people'][0]['height'].", "
							.$player_stuff['people'][0]['weight'].", ".(($player_stuff['people'][0]['shootsCatches']=='L')? 'true' : 'false').", "
							.($player_stuff['people'][0]['rookie'] ? 'true' : 'false').", true, ".$currentID
							.", ".$player_stuff['people'][0]['primaryNumber'].", '".$player_stuff['people'][0]['primaryPosition']['abbreviation']."');";
							
							echo $query."<br>";
							$runQuery = mysqli_query($connection, $query);
							if(!$runQuery)
								echo "unsuccessful";
						}
						$add = TRUE;
                    }
            }
            
            for ($s = 0; $s < sizeOf($players); $s++) {
                if ($players[$s]){
					$query = "UPDATE NHLapiDB.players SET active = FALSE WHERE idplayers = ".$players[$s].";";
					$runQuery = mysqli_query($connection, $query);
				}
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
       <button type = "submit"> Request Active Players </button>
   </form>
</body>
</html>