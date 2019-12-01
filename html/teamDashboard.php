<?php
    include('/var/www/dbConnection.php');
    include('/var/www/html/apiFunctions.php');
	include('/var/www/html/lineGraphQueries.php');
    
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        
        //raw data clense this
        $raw_teamName = $_POST['teamName'];
        
        //clean data
        $raw_teamName = str_replace(".", "%", $raw_teamName);
		$raw_teamName = str_replace(" ", "%", $raw_teamName);
        $raw_teamName = trim($raw_teamName);
        $teamName = filter_var($raw_teamName, FILTER_SANITIZE_STRING);
        $teamName = "%".$teamName."%";

        //The query to grab the team name
        $queryTeam = "Select teamName, idteams, location, division from NHLapiDB.teams "
		."WHERE CONCAT(location, ' ', teamName) LIKE '".$teamName."';";
        $run_queryTeam = mysqli_query($connection, $queryTeam);
        $resultTeam = mysqli_fetch_assoc($run_queryTeam);
		
		$query = "SELECT COUNT(home) AS GP, "
		."COUNT(IF(home=".$resultTeam['idteams']." AND homeWIN OR home!=".$resultTeam['idteams']." AND NOT homeWIN, 1, NULL)) AS W, "
		."COUNT(IF(NOT(home=".$resultTeam['idteams']." AND homeWIN OR home!=".$resultTeam['idteams']
		." AND NOT homeWIN) AND NOT (overtime OR shootout), 1, NULL)) AS L, COUNT(IF(NOT(home=".$resultTeam['idteams']
		." AND homeWIN OR home!=".$resultTeam['idteams']." AND NOT homeWIN) AND (overtime OR shootout), 1, NULL)) AS OT "
		."FROM (SELECT home, homeWIN, overtime, shootout FROM NHLapiDB.games WHERE home = ".$resultTeam['idteams']
		." OR away = ".$resultTeam['idteams'].") AS RECORD;";
		
		$runQuery = mysqli_query($connection, $query);
		$record = mysqli_fetch_assoc($runQuery);
		
		$runGraph = lineGraphTeamQuery($connection, $resultTeam['idteams']);
		
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
			//$ppTotal = $ppTotal + (int)$lineData['Powerplays']);
            array_push($ppAgainst, $lineData['PenaltyKills']);
			//$ppaTotal = $ppaTotal + (int)$lineData['PenaltyKills']);

		}
		
		
    }
        

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
                       <input id = "playerField" type = "text" name = "teamName" placeholder ="Enter Team"><input id = "searchBtn" type = "submit" value = "Search">
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
                           
                             <h3> <?php echo (($resultTeam) ? $resultTeam['location'] : "")." "
							 .(($resultTeam) ? $resultTeam['teamName'] : "");?> </h3>
                            </div>
    <!--                        Note: ٠ -->
                      

                            <div class = "teamCardDetails">
								<p style = "text-align: center">
                                <?php echo (($resultTeam['division'])?$resultTeam['division']:'Division'); ?></p> 
    <!--                           This will be the Record-->
                                <p style = "text-align: center">
                                <?php echo (($record['W'])?$record['W']:'-')."  •  "
                                            .(($record['L'])?$record['L']:'-')."  •  "
                                            .(($record['OT'])?$record['OT']:'-'); ?></p> 
                           

                            </div>
                        </div>
                        <div class = "graphCard">
                             <div class = "teamCardName">
								Goals <input type = "radio" name = "graphType" value = "goals">
								Shots<input type = "radio" name = "graphType" value = "shots">
								PowerPlay <input type = "radio" name = "graphType" value = "powerplays">
                             
        
                            </div>
                             
							<canvas id = "myChart"> </canvas> 

                      
                   

                        </div>

                        <div class = "graphCard">
                            <div class = "teamCardName">
							
							</div>
							
							<canvas id = "spiderChart"> </canvas>
                        </div>
                    </div>    
					<br>
					<!--<div class="heatmapCard">
                        <div class = "teamCardName">
                            <h3> Goals Heat Map </h3>
                        </div>
						<div id='heatMap' class="heatmap" name="playerGoals">
		                    <canvas width="480" height="204" style="position:absolute; left: 0; top: 0"></canvas>
						</div>
					</div> -->
                       
                    <div class = "graphCard">

                    </div>
                        
                </div>
				
				
            </div>
        </div>
    </div>
   
    
        
    <script>
    

		let date = <?php echo '["' . implode('", "', $date) . '"]' ?>;
		let goalsFor  = <?php echo '["' . implode('", "', $goalsFor) . '"]' ?>;
		let goalsAgainst = <?php echo '["' . implode('", "', $goalsAgainst) . '"]' ?>;
		let shotsFor = <?php echo '["' . implode('", "', $shotsFor) . '"]' ?>;
		let shotsAgainst = <?php echo '["' . implode('", "', $shotsAgainst) . '"]' ?>;
		let ppFor = <?php echo '["' . implode('", "', $ppFor) . '"]' ?>;
		let ppAgainst = <?php echo '["' . implode('", "', $ppAgainst) . '"]' ?>;

        let myChart = document.getElementById('myChart').getContext('2d');
     
        let massChart = new Chart(myChart, {
            type: 'line', //bar, horizontal bar, pie, line, donut, radar, polarArea
            data: {
				labels: date,
				datasets:[
				{
					label: 'Goals For',
					data: goalsFor,
					backgroundColor:'rgba(255, 0, 0, 0.4)'},
				{
					label: 'Goals Against',
					data:goalsAgainst,
					backgroundColor: 'rgba(0,0,255,0.4'}
				]
            },
            options: {
                title: {
                    display: true,
                    text: 'Goals Per Game'
                }
            }
        });
		
		
		let gTotal = <?php echo $gTotal;?> 
		let gaTotal = <?php echo $gaTotal;?>
		
		let sTotal = <?php echo $sTotal;?>
		
		let saTotal = <?php echo $saTotal;?>
		/*
		let ppTotal = <?php echo $ppTotal;?>
		
		let ppaTotal = <?php echo $ppaTotal;?>
		*/
		
		console.log(gTotal);
		console.log(gaTotal);
		console.log(sTotal);
		console.log(saTotal);
		
		let spiderChartID = document.getElementById('spiderChart').getContext('2d');
     
        let spiderChart = new Chart(spiderChartID, {
            type: 'radar', //bar, horizontal bar, pie, line, donut, radar, polarArea
            data: {
				labels: ['Goals', 'PowerPlays', 'PowerPlays Given', 'Goals Against', 'Shots Against', 'Shots'],
				datasets:[
				{
					label: 'Goals For',
					
					data: [gTotal, gaTotal, sTotal, saTotal/*, ppTotal, ppaTotal*/],
					backgroundColor:'rgba(0, 0, 255, 0.7)'
				}
	
				]
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