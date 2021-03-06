<?php
session_start();

$project    = "reviews";
$siteTitle  = "R&S Reviews";
$pageTitle  = "R&S Reviews";
$image      = "https://reviews.religionandstory.com/images/reviews.jpg";
$description= "View all of Daniel Crouch's movie and book reviews. See rankings for Disney Classics, the MCU, and the Start Wars Franchise.";
$keywords   = "review,ranking,rating,movie,movies,lists,criticism,Disney,Marvel,MCU,Avengers,Star Wars";
$homeUrl    = "https://reviews.religionandstory.com";
$style      = "red";

function getRootPath()
{
    $public = "public_html";
    $path = $_SERVER['DOCUMENT_ROOT'];
    $length = strpos( $path, $public ) + strlen( $public );
    return substr( $path, 0, $length ) . "/";
}

function getSubPath()
{
    return getRootPath() . "reviews/";
}

function includeHeadInfo( $title = null )
{
    global $siteTitle;
    global $pageTitle;
    global $image;
    global $description;
    global $keywords;
    global $style;

    $pageTitle = ($title) ? ("Review: " . $title) : $pageTitle;

    include( getRootPath() . "common/html/head.php" );
    echo '<link href="https://fonts.googleapis.com/css?family=Abril+Fatface" rel="stylesheet">';
    echo '<style>
              :root {
                 --mainColor:   #9D2235;
                 --hoverColor:  #BD4255;
                 --normalFont:  "Times New Roman", serif;
                 --titleFont:   "Abril Fatface", serif;
                 --titleWeight: normal;
              }
          </style>';
}

function includeHeader()
{
    global $homeUrl;
    include( getRootPath() . "common/html/header.php" );
}

function includeModals()
{
    include( getRootPath() . "common/html/modal.html" );
    include( getRootPath() . "common/html/modal-choice.html" );
    include( getRootPath() . "common/html/modal-prompt.html" );
    include( getRootPath() . "common/html/modal-prompt-big.html" );
    include( getRootPath() . "common/html/toaster.html" );
}

function getHelpImage()
{
    echo "https://religionandstory.com/common/images/question-mark.png";
}

function getConstructionImage()
{
    echo "https://image.freepik.com/free-icon/traffic-cone-signal-tool-for-traffic_318-62079.jpg";
}

?>