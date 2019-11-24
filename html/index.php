<?php
    include('/var/www/dbConnection.php');

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $team_url = "https://statsapi.web.nhl.com/api/v1/teams";
        $team_json = file_get_contents($team_url);
        $team_array = json_decode($team_json, true);
        
        for($i = 1; $i <= 30; $i++) {
            
            $query = "INSERT into teams (idteams, teamName, location, division, conference) VALUES ('$nextTeam['id']','$nextTeam['teamName']','$nextTeam['locationName']','$nextTeam['division']['name']','$nextTeam['conference']['name']')"

            //Test
            echo $team_array['teams'][$i]['name'];

            $runQuery = mysqli_query($connection, $query);

            if($runQuery) 
                echo "<br>Team added.";
            else 
                echo "<br>ERROR: Team not added.";
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
       <button type = "submit"> Request Teams </button>
   </form>
<!--    <button id="retrievePlayers" onclick= "requestAllActivePlayers()">Request all Active Players</button>-->
<!--    <button id="retrieveTeams" onclick= "requestAllTeams()">Request all Teams</button>-->

<!--    <script src = "./requestTeams.js"></script>-->
<!--    <script src = "./requestPlayers.js"></script>-->

</body>
</html>