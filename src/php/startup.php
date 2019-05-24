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

function includeHeadInfo( $reviewOverride = null )
{
    global $siteTitle;
    global $pageTitle;
    global $image;
    global $description;
    global $keywords;
    global $style;

    $pageTitle   = $reviewOverride ? $reviewOverride['title'] : $pageTitle;
    $image       = $reviewOverride ? $reviewOverride['image'] : $image;
    $description = $reviewOverride ? $reviewOverride['desc']  : $description;

    include("$_SERVER[DOCUMENT_ROOT]/../common/html/head.php");
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
    include("$_SERVER[DOCUMENT_ROOT]/../common/html/header.php");
}

function includeModals()
{
    include("$_SERVER[DOCUMENT_ROOT]/../common/html/modal.html");
    include("$_SERVER[DOCUMENT_ROOT]/../common/html/modal-binary.html");
    include("$_SERVER[DOCUMENT_ROOT]/../common/html/modal-prompt.html");
    include("$_SERVER[DOCUMENT_ROOT]/../common/html/modal-prompt-big.html");
    include("$_SERVER[DOCUMENT_ROOT]/../common/html/toaster.html");
}

function getHelpImage()
{
    echo "https://religionandstory.com/common/images/question-mark.png";
}

function getConstructionImage()
{
    echo "https://image.freepik.com/free-icon/traffic-cone-signal-tool-for-traffic_318-62079.jpg";
}

function getReviewOverride( $title, $id )
{
    $reviewOverride = null;
    if ( $title || $id )
    {
        include("$_SERVER[DOCUMENT_ROOT]/php/utilityMovie.php");
        $movie = ( $title ) ? getMovieFromImdbByTitle( $title ) : getMovieFromImdbById( $id );
        $id = $movie['isSuccess'] ? $movie['id'] : null;

        if ( $id )
        {
            $movieReviews = getMovieListFromFile( "$_SERVER[DOCUMENT_ROOT]/archive/ratings.csv" );
            $index = getIndexFromListById( $movieReviews, $id );
            if ( $index >= 0 )
            {
                $reviewOverride['title'] = $movie['title'];
                $reviewOverride['image'] = $movie['image'];
                $reviewOverride['desc']  = $movieReviews[$index]['review'];
            }
        }
    }
    return $reviewOverride;
}

?>