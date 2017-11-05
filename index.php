<?php
    //error_reporting(0);

    require "./OldCar.php";
    $myCar = new OldCar("sql_oldcar", "cardata");
    $myCar -> handle();
