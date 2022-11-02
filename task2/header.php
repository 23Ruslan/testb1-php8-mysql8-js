<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Table page</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
<main>
    <div class='pre-spreadsheet'>
        <a href='http://localhost/b1/task2/uploads/table.xls' onclick="location.href=sessionStorage.getItem('currentFileName');" target='__blank'>Download the spreadsheet you have uploaded as .xls.</a>
        <br>
        <a onclick="this.href='data:text/html;charset=UTF-8,'+encodeURIComponent(document.documentElement.outerHTML)" href="#" download="page.html">Download THIS spreadsheet as .html (server generated).</a>
        <br>
        <a href='http://localhost/b1/task2/table.txt' download="http://localhost/b1/task2/table.txt" target='__blank'>Download THIS spreadsheet as .txt (server generated).</a>
    </div>