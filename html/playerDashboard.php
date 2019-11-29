<?php
    include('/var/www/dbConnection.php');
    include('/var/www/html/apiFunctions.php');
    
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        
        //raw data clense this
        $raw_playerName = $_POST['playerName'];
        
        //clean data
        $raw_playerName = str_replace(".", "%", $raw_playerName);
        $raw_playerName = trim($raw_playerName);
        $playerName = filter_var($raw_playerName, FILTER_SANITIZE_STRING);
        $playerName = "%".$playerName."%";
        
        //query to grab the player searched
        $queryPlayer = "SELECT * FROM NHLapiDB.players WHERE CONCAT(fName, ' ', lName) LIKE '".$playerName."'";
        $run_queryPlayer = mysqli_query($connection, $queryPlayer);
        $resultPlayer = mysqli_fetch_assoc($run_queryPlayer); 

        //The query to grab the team name
        $queryTeam = "Select teamName from NHLapiDB.teams WHERE idteams = ".$resultPlayer['currentTeam']."";
        $run_queryTeam = mysqli_query($connection, $queryTeam);
        $resultTeam = mysqli_fetch_assoc($runQueryTeam);
        
    }
        

?>


<!DOCTYPE html
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="stylesheet" href = "playerDashStyle.css">

</head>
<body>
    <div class = "flexContainer">
        <div class = "sideNav">
            <div class = "title"> 
            
            </div>
        </div>
        <div class = "informationNav">
            <div class = "topNav">
               <div class = "userForm">
                   <form method = "post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                       <input id = "playerField" type = "text" name = "playerName" placeholder ="Enter Player"><input id = "searchBtn" type = "submit" value = "Search">
                   </form>
               </div>
            </div>
            <div class = "content">
               <div class = "dashboardTitle">
                    <h2> Player Dashboard</h2>
                </div>
                
                <div class = "playerInfo">
                    <div class = "playerCard">
                        <div class = "playerCardName">
                        <?php
                       if ($resultPlayer) {
                        ?>

                         <h3> <?php echo $resultPlayer['fName']." ".$resultPlayer['lName']." | ".$resultPlayer['primeNumber'];?> </h3>
                       
                        </div>
                        
<!--                        Note: ٠ -->
                    <?php
                       }
                    ?>

                        <div class = "playerCardDetails">
                            <p style = "text-align: center"> C  •  Left  • Chicago Blackhawks </p> 
                            <p style = "text-align: center"> 5'11  •  183lb  •  Age: 31  •  USR </p>
                           
                        </div>
                    </div>
                        
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>