<?php
include_once( "utility.php" );


/*******************BOOK LOAD********************/


include_once( "utilityBook.php" );

function getBookByTitle( $title )
{
    $id = getBookIdFromFile( $title );
    return getBookById( $id );
}

function getBookById( $id )
{
    $result = getBookFromGoodreads( $id );
    $result['id'] = $id;
    return $result;
}


/******************MOVIE LOAD********************/


include_once( "utilityMovie.php" );

function getMovieByTitle( $title )
{
    $movie = getMovieFromImdbByTitle( $title );
    $movie['isPreviouslyReviewed'] = false;
    $previouslyRatedMovie = getMovieList()[ $movie['id'] ];
    if ( $previouslyRatedMovie )
    {
        $movie['isPreviouslyReviewed'] = true;
        $movie['rating'] = $previouslyRatedMovie['rating'];
        $movie['review'] = $previouslyRatedMovie['review'];
        $movie['index'] = $previouslyRatedMovie['index'];
    }
    return $movie;
}

function getMovieById( $id )
{
    $movie = getMovieFromImdbById( $id );
    $movie['isPreviouslyReviewed'] = false;
    $previouslyRatedMovie = getMovieList()[ $movie['id'] ];
    if ( $previouslyRatedMovie )
    {
        $movie['isPreviouslyReviewed'] = true;
        $movie['rating'] = $previouslyRatedMovie['rating'];
        $movie['review'] = $previouslyRatedMovie['review'];
        $movie['index'] = $previouslyRatedMovie['index'];
    }
    return $movie;
}


/*******************RANK LOAD********************/


function getRankMovieByTitle( $title )
{
    $movie = getMovieFromImdbByTitle( $title );
    $movie['isPreviouslyReviewed'] = false;
    $previouslyRankedMovie = getRankMovieFromFilesById( $movie['id'] );
    if ( $previouslyRankedMovie )
    {
        $movie['isPreviouslyReviewed'] = true;
        $movie['rating'] = $previouslyRankedMovie['rating'];
        $movie['review'] = $previouslyRankedMovie['review'];
        $movie['index'] = $previouslyRankedMovie['index'];
        $movie['list'] = $previouslyRankedMovie['list'];
        $movie['image'] = $previouslyRankedMovie['image'];
    }
    return $movie;
}

function getRankMovieById( $id )
{
    $movie = getMovieFromImdbById( $id );
    $movie['isPreviouslyReviewed'] = false;
    $previouslyRankedMovie = getRankMovieFromFilesById( $id );
    if ( $previouslyRankedMovie )
    {
        $movie['isPreviouslyReviewed'] = true;
        $movie['rating'] = $previouslyRankedMovie['rating'];
        $movie['review'] = $previouslyRankedMovie['review'];
        $movie['index'] = $previouslyRankedMovie['index'];
        $movie['list'] = $previouslyRankedMovie['list'];
        $movie['image'] = $previouslyRankedMovie['image'];
    }
    return $movie;
}


/*****************MOVIE SUBMIT*******************/


function saveMovie( $id, $title, $year, $index, $rating, $review, $overwrite )
{
    $fileName = getPath( "ratings.csv" );
    $isOverwrite = filter_var( $overwrite, FILTER_VALIDATE_BOOLEAN );
    $movie = [
        'title'     => $title,
        'id'        => $id,
        'year'      => $year,
        'rating'    => $rating,
        'review'    => $review
    ];

    $movies = getMovieList();

    if ( $isOverwrite )
    {
        $originalIndex = getIndexFromListById( $movies, $id );
        unset( $movies[$originalIndex] );
    }

    if ( $index )
    {
        array_splice( $movies, $index, 0, array( $movie ) );
    }
    else
    {
        array_push( $movies, $movie );
    }

    saveFullMoviesToFile( $movies );
    archive( $fileName );
}


/******************RANK SUBMIT*******************/


function saveRankedMovie( $list, $rank, $id, $title, $year, $image, $review, $overwrite )
{
    $isOverwrite = filter_var( $overwrite, FILTER_VALIDATE_BOOLEAN );
    $movie = [
        'title'     => $title,
        'id'        => $id,
        'year'      => $year,
        'image'     => $image,
        'review'    => $review
    ];

    $list = getListName( $list );
    $movies = getMovieListFromFile( getPath( "rank-$list.csv" ) );

    if ( $isOverwrite )
    {
        $originalIndex = getIndexFromListById( $movies, $id );
        unset( $movies[$originalIndex] );
    }

    array_splice( $movies, $rank, 0, array( $movie ) );
    saveRankMoviesToFile( $list, $movies );
}

function validateRank( $list, $rank )
{
    $result['isSuccess'] = false;

    $list = getListName( $list );
    $movies = getMovieListFromFile( getPath( "rank-$list.csv" ) );
    $count = count( $movies );

    $rank = strtolower( $rank );
    if ( is_numeric( $rank ) )
    {
        $result['rank'] = (int)$rank;
    }
    else
    {
        switch ( $rank )
        {
            case "top":
            case "start":
            case "first":
                $result['rank'] = 1;
                break;
            case "second":
                $result['rank'] = 2;
                break;
            case "third":
                $result['rank'] = 3;
                break;
            case "fourth":
                $result['rank'] = 4;
                break;
            case "fifth":
                $result['rank'] = 5;
                break;
            case "last":
            case "bottom":
            case "end":
                $result['rank'] = $count;
                break;
        }

        if ( stripos( $rank, "before" ) === 0 || stripos( $rank, "after" ) === 0 ||
             stripos( $rank, "above" )  === 0 || stripos( $rank, "below" ) === 0 )
        {
            $title = explode( ' ', $rank, 2 )[1];
            $rankOfMovieResult = getIndexFromListByTitle( $movies, $title );
            if ( $rankOfMovieResult['isSuccess'] )
            {
                $rankOfMovieResult['index']++; //start at 1
                $atPosition = stripos( $rank, "before" ) === 0 || stripos( $rank, "above" ) === 0;
                $result['rank'] = $atPosition ? $rankOfMovieResult['index'] : $rankOfMovieResult['index'] + 1;
            }
        }
    }

    if ( is_numeric($result['rank']) && $result['rank'] <= $count )
    {
        $result['isSuccess'] = true;
    }
    else
    {
        $result['message'] = "Invalid Rank";
    }

    return $result;
}


/*********************DELETE********************/


function deleteMovie( $id )
{
    $movies = getMovieList();
    $result['isSuccess'] = isset( $movies[$id] );
    unset( $movies[$id] );
    saveFullMoviesToFile( $movies );
    return $result;
}

function deleteRankMovie( $list, $id )
{
    $list = getListName( $list );
    $movies = getMovieListFromFile( getPath( "rank-$list.csv" ) );
    $result['isSuccess'] = isset( $movies[$id] );
    unset( $movies[$id] );
    saveRankMoviesToFile( $list, $movies );
    return $result;
}


/**********************IMAGE*********************/


function submitBookImage( $id, $url )
{
    $file = fopen( getPath( "book-images.csv" ), "a" );
    fputcsv( $file, array( $id, $url ) );
    fclose( $file );
}


/********************DOWNLOAD********************/


function downloadAll()
{
    if ( extension_loaded('zip') )
    {
        $zip = new ZipArchive();
        $zipName = time() . ".zip";

        $isOpen = $zip->open( $zipName, ZIPARCHIVE::CREATE );
        if ( $isOpen )
        {
            $folder = "../archive";
            foreach ( scandir($folder) as $file )
            {
                $file = "$folder/" . $file;
                if ( file_exists($file) && is_file($file) )
                {
                    $zip->addFile($file);
                }
            }
            $zip->close();
        }

        if ( file_exists( $zipName ) )
        {
            ignore_user_abort(true);
            header( "Content-type: application/zip" );
            header( "Content-Length: " . filesize($zipName) );
            header( "Content-Disposition: attachment; filename='$zipName'" );
            readfile( $zipName );
            unlink( $zipName );
        }
    }
    else
    {
        echo "ZIP Extension not available";
    }
}

function viewSearches()
{
    $result = "";
    $file = fopen( getPath( "searches.txt" ), "r" );
    while ( ($line = fgets( $file )) !== false )
    {
        $result .= "<div>$line</div>";
    }
    fclose( $file );
    return $result;
}


/*********************************************************************************************************************/


if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    if ( isset( $_POST['id'] ) && isset( $_POST['title'] ) && isset( $_POST['year'] ) && isset( $_POST['index'] ) && isset( $_POST['rating'] ) && isset( $_POST['review'] ) )
    {
        $result = $action( $_POST['id'], $_POST['title'], $_POST['year'], $_POST['index'], $_POST['rating'], $_POST['review'], isset( $_POST['overwrite'] ) ? $_POST['overwrite'] : false );
    }
	elseif ( isset( $_POST['list'] ) && isset( $_POST['rank'] ) && isset( $_POST['id'] ) && isset( $_POST['title'] ) && isset( $_POST['year'] ) && isset( $_POST['image'] ) && isset( $_POST['review'] ) )
    {
        $result = $action( $_POST['list'], $_POST['rank'], $_POST['id'], $_POST['title'], $_POST['year'], $_POST['image'], $_POST['review'], isset( $_POST['overwrite'] ) ? $_POST['overwrite'] : false );
    }
	elseif ( isset( $_POST['list'] ) && isset( $_POST['id'] ) )
	{
		$result = $action( $_POST['list'], $_POST['id'] );
	}
	elseif ( isset( $_POST['list'] ) && isset( $_POST['rank'] ) )
	{
		$result = $action( $_POST['list'], $_POST['rank'] );
	}
    elseif ( isset( $_POST['id'] ) && isset( $_POST['url'] ) )
   	{
   		$result = $action( $_POST['id'], $_POST['url'] );
   	}
	elseif ( isset( $_POST['id'] ) )
	{
		$result = $action( $_POST['id'] );
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
