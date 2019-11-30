<?php
//    include('/var/www/dbConnection.php');
//    include('/var/www/html/apiFunctions.php');
//    
//    if($_SERVER["REQUEST_METHOD"] == "POST") {
//        
//        //raw data clense this
//        $raw_teamName = $_POST['teamName'];
//        
//        //clean data
//        $raw_teamName = str_replace(".", "%", $teamName);
//		$raw_teamName = str_replace(" ", "%", $raw_teamName);
//        $raw_teamName = trim($raw_teamName);
//        $teamName = filter_var($raw_teamName, FILTER_SANITIZE_STRING);
//        $teamName = "%".$teamName."%";
//
//        //The query to grab the team name
//        $queryTeam = "Select teamName, idteams location from NHLapiDB.teams WHERE teamName = ".$teamName."";
//        $run_queryTeam = mysqli_query($connection, $queryTeam);
//        $resultTeam = mysqli_fetch_assoc($runQueryTeam);
//        
//    }
        

?>


<!DOCTYPE html
<html lang="en">
<script src='http://www.patrick-wied.at/static/heatmapjs/assets/js/heatmap.min.js'></script>
<script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="stylesheet" href = "teamDashStyle.css">

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
                       <input id = "teamField" type = "text" name = "teamName" placeholder ="Enter Team"><input id = "searchBtn" type = "submit" value = "Search">
                   </form>
               </div>
            </div>
            <div class = "content">
               <div class = "dashboardTitle">
                    <h2> Team Dashboard</h2>
                </div>
                
                <div class = "teamInfo">
                   <div class = "row">
                        <div class = "teamCard">
                            <div class = "teamCardName">
                            <?php
                          // if ($resultPlayer) {
                            ?>
                             <h3> Chicago Blackhawks<?php //echo $resultTeam['location']." ".$resultTeam['teamName'];?> </h3>
                            </div>
    <!--                        Note: ٠ -->
                        <?php
                          // }
                        ?>

                            <div class = "teamCardDetails">

    <!--                           This will be the Record-->
                                <p style = "text-align: center">
                                <?php  ?></p> 
                                <p style = "text-align: center">
                                <?php  ?></p>

                            </div>
                        </div>
                        <div class = "graphCard">
                             <div class = "teamCardName">
                        
                             
        
                            </div>
                             
                            <canvas id = "myChart"> </canvas>

                      
                            


                        </div>

                        <div class = "graphCard">
                            Goals <input type = "radio" name = "graphType" value = "goals">
                            Shots<input type = "radio" name = "graphType" value = "shots">
                            PowerPlay <input type = "radio" name = "graphType" value = "powerplays">
                        </div>
                    </div>    
					<br>
					<div class="heatmapCard">
                        <div class = "teamCardName">
                            <h3> Goals Heat Map </h3>
                        </div>
						<div id='heatMap' class="heatmap" name="playerGoals">
		                    <canvas width="799" height="340" style="position:absolute; left: 0; top: 0"></canvas>
						</div>
					</div>
                       
                    <div class = "graphCard">

                    </div>
                        
                </div>
				
				
            </div>
        </div>
    </div>
    </div>
    
        
    <script>
    
        let myChart = document.getElementById('myChart').getContext('2d');
     
        let massChart = new Chart(myChart, {
            type: 'line', //bar, horizontal bar, pie, line, donut, radar, polarArea
            data: {
                labels:['Game 1', 'Game 2', 'Game 3', 'Game 4', 'Game 5'],
                datasets:[{
             
                
                }]
            },
            options: {
                title: {
                    display: true,
                    text: 'Goals Per Game'
                }
            }
        });
    </script>
</body>



</html>