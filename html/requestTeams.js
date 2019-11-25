function requestAllTeams() {
    const TEAMS_URL = "https://statsapi.web.nhl.com/api/v1/teams";
    const promise = fetch(TEAMS_URL);
    //make html for this
    promise
        .then(function(response) {
        const processingPromise = response.json();
        return processingPromise;
    })
        .then(function(processedResponse) {
        console.log(processedResponse);
        for (var i = 0; i < 31; i++) {
        
            //Access teams[i].id, teams[i].name, teams[i].division.name, teams[i].conference.name, teams[i].locationName
            //Store data from teams into DB using PHP       
            console.log(teamName = processedResponse.teams[i]);
            
            //Convert to JSON to post for PHP
            myJSON = JSON.stringify(processedResponse.teams[i]);
            
            
            //Send JSON to PHP
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "./index.php", !0);
            xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xhr.send(myJSON);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // in case we reply back from server
                    jsondata = JSON.parse(xhr.responseText);
                    console.log(jsondata);
                }
}
            //Print out to HTML
            var par = document.createElement("p");
            var text = document.createTextNode(myJSON);
            par.appendChild(text);
            document.body.appendChild(par)
      
           // document.getElementById("demo").innerHTML = myJSON;
            //SQL
        }
})
}