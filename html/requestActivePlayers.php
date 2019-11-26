<?php
    //  include('dbConnection.php');
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $team_url = "https://statsapi.web.nhl.com/api/v1/teams/?expand=team.roster";
        $team_json = file_get_contents($team_url);
        $team_array = json_decode($team_json, true);
        
      //  echo $team_array['teams'][0]['roster']['roster'][0]['person']['fullName'];
        
        //Outer loop iterates through the teams
        //Note that i is NOT the ID for a team as some teams have ID's in the 50's
        //Note look up sizeOf for array bounds
       
        
        for($i = 0; $i <= 30; $i++) {
            echo $i;
            echo $team_array['teams'][$i]['teamName'];
            echo "</br>";
            //current teamID
            $currentID = $team_array['teams'][$i]['id'];
            
            //query to pull all active players from a team
            $query = "SELECT idplayers FROM players WHERE currentTeam = ".$currentID." AND active = TRUE;";
            $runQuery = mysqli_query($connection, $query);
            
            //Create array for the active players in the DB
            
            $players = array();
            while ($result = mysqli_fetch_assoc($runQuery)) {
                //Store active players from the DB into an array
                players[] = $result['idplayers'] ;
            }
 
            //Inner loop to iterate through the roster
            for($k = 0; $k < sizeOf($team_array['teams'][$i]['roster']['roster']); $k++) {
               
                $add = TRUE;
                
                    for($j = 0; $j < sizeOf(players) && add; j++) {
                        
                        if ($team_array['teams'][$i]['roster']['roster'][$k]['person']['id'] == $players[$j] ) {
                            //set null and set add to false for early exit
                            add = FALSE;t
                        } 
                        
                    } 
                
                    if(add) {
                        //insert and reset add to true
                        
                        add = true;
                        
                    }
                }
            
            
            for ($s = 0; $s < sizeOf(players); $s++) {
                //remove any that are left in the array from the DB
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