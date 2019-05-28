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


/**********************MOVIE*********************/


include_once( "utilityMovie.php" );

function getFullMovieList()
{
    return getMovieListFromFile( getPath( "ratings.csv" ) );
}

function getGenreLists()
{
    $genres = getGenresFromFile();
    return getMovieListsFromFiles( $genres, "genre-" );
}

function getDisneyList()
{
    return getMovieListFromFile( getPath( "franchise-Disney.csv" ) );
}

function getMarvelList()
{
    return getMovieListFromFile( getPath( "franchise-Marvel.csv" ) );
}

function getStarWarsList()
{
    return getMovieListFromFile( getPath( "franchise-StarWars.csv" ) );
}


/*********************************************************************************************************************/


if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    //saveSearch
    if ( isset( $_POST['title'] ) && isset( $_POST['type'] ) )
    {
        $result = $action( $_POST['title'], $_POST['type'] );
    }
    //getFullBookList | getFavoritesList | getTempFullBookList | getTempFavoritesList | getFullMovieList | getGenreLists | getDisneyList | getMarvelList | getStarWarsList
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}
?>
