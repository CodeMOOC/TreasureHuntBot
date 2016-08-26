<?php
require_once('log.php');
require_once('lib.php');

$q = "SELECT * FROM status WHERE state >= 30";
$res = db_table_query($q);

?>

<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <title>Urbino Code Hunt - Statistiche</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Slider stylesheet -->
    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css">

    <!-- Local stylesheet -->
    <link rel="stylesheet" href="src/css/frontend.css" type="text/css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="src/js/frontend.js"></script>

</head>
<body onload="JavaScript:timedRefresh(30000);">

<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Urbino Code Hunt</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a href="groups.php">Gruppi</a></li>
                <li class="active"><a href="#">Statistiche</a></li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</nav>

<div class="container" style="margin-top: 60px">

    <div class="starter-template">
        <?php
        $begin = 'Inizio';
        $end = 'Vittoria!';
        $width_small = '4.95%';
        $width_big = '9.9%';
        $align_right = 'right';
        $align_left = 'left';
        for($i = 0; $i <= 10; $i++){
            echo "<div align='" . ($i == 0 ? $align_left : $align_right) . "' style='width: " . ($i == 0 ? $width_small : ($i == 1 ? $width_small : $width_big)) . "; float: left;'>" . ($i == 0 ? $begin : ($i == 10 ? $end : $i)) . "</div>";
        }
        echo "</br>";
        foreach($res as $var){


            echo "</br>";
            echo "$var[2]";
            echo "<div id='slider" . $var[1] . "' class='coloredSlider' value='" . $var[7] . "'></div>";
            echo '<script type="text/javascript">',
                'initSlider("slider' . $var[1] . '");',
            '</script>';
            echo '<script type="text/javascript">',
                'updateSlider("slider' . $var[1] . '",' . $var[7] .');',
            '</script>';
        }
        ?>
    </div>

</div><!-- /.container -->

</body>
</html>
