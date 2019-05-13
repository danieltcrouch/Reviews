<?php
include_once( "utility.php" );

function saveSearch( $title, $type )
{
    appendToFile( getPath( "searches.txt" ), $type . " - " . $title . "\n", false );
}

/**********************BOOK**********************/


include_once( "utilityBook.php" );

function getFullBookList()
{
    return getBookListFromGoodreads( "read" );
}

function getFavoritesList()
{
    return getBookListFromGoodreads( "favorites", "title" );
}

function getTempFullBookList()
{
    return getBookListFromFileByShelf( "read" );
}

function getTempFavoritesList()
{
    return getBookListFromFileByShelf( "favorites" );
}

function getBookByTitle( $title )
{
    $id = getBookIdFromFile( $title );
    $result = getReviewedBookFromGoodreads( $id );
    return $result;
}


/**********************MOVIE*********************/


include_once( "utilityMovie.php" );

function getFullMovieList()
{
    return array_reverse( getMovieListFromFile( getPath( "ratings.csv" ) ) );
}

function getTenList()
{
    return getMultiMovieListFromFile( getPath( "genres.csv" ) );
}

function getDisneyList()
{
    return getMovieListFromFile( getPath( "rank-Disney.csv" ) );
}

function getMarvelList()
{
    return getMovieListFromFile( getPath( "rank-Marvel.csv" ) );
}

function getStarWarsList()
{
    return getMovieListFromFile( getPath( "rank-StarWars.csv" ) );
}

function getMovieByTitle( $title )
{
    $result['isSuccess'] = false;

    $movie = getMovieFromFile( $title );
    if ( $movie )
    {
        $result = $movie;
        $movieData = getMovieFromImdbById( $result['id'] );
        $result['image'] = $movieData['image'];
        $result['rtScore'] = $movieData['rtScore'];
        $result['isSuccess'] = true;
    }
    return $result;
}


/*********************************************************************************************************************/


if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    if ( isset( $_POST['title'] ) && isset( $_POST['author'] ) )
    {
        $result = $action( $_POST['title'], $_POST['author'] );
    }
    elseif ( isset( $_POST['title'] ) && isset( $_POST['type'] ) )
    {
        $result = $action( $_POST['title'], $_POST['type'] );
    }
    elseif ( isset( $_POST['title'] ) )
    {
        $result = $action( $_POST['title'] );
    }
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}
?>
