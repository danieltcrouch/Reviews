<?php
include_once( "utility.php" );
include_once( "utilityBook.php" );
include_once( "utilityMovie.php" );


/******************MOVIE LOAD********************/


function getMovieByTitle( $title, $type )
{
    return ( $type === "full" ) ? getMovieFromFull( $title, "title" ) : getMovieFromRankByTitle( $title, $type );
}

function getMovieById( $id, $type )
{
    return ( $type === "full" ) ? getMovieFromFull( $id, "id" ) : getMovieFromRankById( $id, $type );
}

function getMovieFromFull( $value, $valueType )
{
    $movie = getMovieFromFullList( $value, $valueType );
    if ( $movie )
    {
        $movie['isPreviouslyReviewed'] = true;
        $movie['index']++;
        $movie = addImdbFields( $movie );
    }
    else
    {
        $movie = ( $valueType === "type" ) ? getMovieFromImdbByTitle( $value ) : getMovieFromImdbById( $value );
        $movie['isPreviouslyReviewed'] = false;
    }
    $movie['isSuccess'] = (bool) $movie['id'];
    return $movie;
}

function getMovieFromRankByTitle( $title, $type )
{
    $id = getMovieFromImdbByTitle( $title )['id'];
    return getMovieFromRankById( $id, $type );
}

function getMovieFromRankById( $id, $type )
{
    $movie = getMovieFromImdbById( $id );
    $movie['isPreviouslyReviewed'] = false;
    $previouslyRankedMovie = getRankMovieFromFilesById( $type, $id );
    if ( $previouslyRankedMovie )
    {
        $movie['isPreviouslyReviewed'] = true;
        $movie['review'] = $previouslyRankedMovie['review'];
        $movie['index'] = $previouslyRankedMovie['index'];
        $movie['list'] = $previouslyRankedMovie['list'];
        $movie['image'] = $previouslyRankedMovie['image'];
    }
    return $movie;
}


/*******************BOOK LOAD********************/


function getBookByTitle( $title )
{
    $id = getBookIdFromFile( $title );
    $result = $id ? getBookById( $id ) : getBookFromGoodreadsByTitle( $title );
    $result['isPreviouslyReviewed'] = $id ? true : false;
    return $result;
}

function getBookById( $id )
{
    $result = getReviewedBookFromGoodreads( $id );
    $result['isPreviouslyReviewed'] = true;
    if ( !$result['isSuccess'] )
    {
        $result = getBookFromGoodreadsById( $id );
        $result['isPreviouslyReviewed'] = false;
    }
    return $result;
}


/*********************************************************************************************************************/


if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    //getMovieByTitle
    if ( isset( $_POST['title'] ) && isset( $_POST['type'] ) )
    {
        $result = $action( $_POST['title'], $_POST['type'] );
    }
    //getMovieById
	elseif ( isset( $_POST['id'] ) && isset( $_POST['type'] ) )
    {
        $result = $action( $_POST['id'], $_POST['type'] );
    }
    //getBookByTitle
	elseif ( isset( $_POST['title'] ) )
	{
		$result = $action( $_POST['title'] );
	}
    //getBookById
	elseif ( isset( $_POST['id'] ) )
	{
		$result = $action( $_POST['id'] );
	}
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}
?>