
const TEAMS_URL = "https://statsapi.web.nhl.com/api/v1/teams/1";

const promise = fetch(TEAMS_URL);
//make html for this
promise
    .then(function(response) {
    const processingPromise = response.json();
    return processingPromise;
})
    .then(function(processedResponse) {
    teamName = processedResponse.teams[0].name;
    console.log(teamName);
})
