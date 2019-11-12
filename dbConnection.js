var mysql = require('mysql');

var connect = mysql.createConnection({
    host: "localhost",
    user: "kett"
});

con.connect(function(err) {
    if (err) throw err;
    console.log("Connected!");
});