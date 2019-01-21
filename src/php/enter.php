<?php
include_once( "utility.php" );

function getMovieData( $title )
{
    $result['isSuccess'] = false;
    $result['search'] = $title;

    $title = trim( $title );
    $searchTitle = preg_replace('/\'/', '%27', $title);
    $searchTitle = preg_replace('/\s+/', '+', $searchTitle);
    //OMDB API now requires an API Key (&apikey=8f0ce8a6) -> go to their site if this one stops working
    $url = "http://www.omdbapi.com/?t=$searchTitle&y=&plot=short&r=json&apikey=8f0ce8a6";
    $response = json_decode( file_get_contents( $url ) );

    if ( $response->Response === "True" )
    {
        $result['isSuccess'] = true;
        $result['id'] = $response->imdbID;
        $result['title'] = $response->Title;
        $result['poster'] = $response->Poster;
        $result['year'] = $response->Year;
    }

    return $result;
}

function getMovieDataById( $id )
{
    $result['isSuccess'] = false;

    //OMDB API now requires an API Key (&apikey=8f0ce8a6) -> go to their site if this one stops working
    $url = "http://www.omdbapi.com/?i=$id&y=&plot=short&r=json&apikey=8f0ce8a6";
    $response = json_decode( file_get_contents( $url ) );

    if ( $response->Response === "True" )
    {
        $result['isSuccess'] = true;
        $result['id'] = $response->imdbID;
        $result['title'] = $response->Title;
        $result['year'] = $response->Year;
    }

    return $result;
}

function loadFromListFile( $id )
{
    $result['isSuccess'] = false;

    $file = fopen( "../archive/ratings.csv", "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $index = 1;
    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $rowId = trim( $row[ $columns['iIndex'] ] );
        if ( $id === $rowId )
        {
            $result['isSuccess'] = true;
            $result['id'] = $rowId;
            $result['title'] = trim( $row[ $columns['tIndex'] ] );
            $result['year'] = trim( $row[ $columns['yIndex'] ] );
            $result['rating'] = trim( $row[ $columns['rIndex'] ] );
            $result['review'] = trim( $row[ $columns['cIndex'] ] );
            $result['index'] = $index;
        }
        $index++;
        $row = fgetcsv( $file );
    }

    return $result;
}

function loadFromRankFile( $id )
{
    $result['isSuccess'] = false;

    $files = getRankFiles();
    foreach ( $files as $name => $file )
    {
        $columns = getColumns( fgetcsv( $file ) );

        $index = 1;
        $row = fgetcsv( $file );
        while ( $row !== false )
        {
            $rowId = trim( $row[ $columns['iIndex'] ] );
            if ( $id === $rowId )
            {
                $result['isSuccess'] = true;
                $result['id'] = $rowId;
                $result['title'] = trim( $row[ $columns['tIndex'] ] );
                $result['year'] = trim( $row[ $columns['yIndex'] ] );
                $result['rating'] = trim( $row[ $columns['rIndex'] ] );
                $result['review'] = trim( $row[ $columns['cIndex'] ] );
                $result['list'] = $name;
                $result['index'] = $index;
                break;
            }
            $index++;
            $row = fgetcsv( $file );
        }

        if ( $result['isSuccess'] )
        {
            closeRankFiles( $files );
            break;
        }
    }

    return $result;
}

function getRankFiles()
{
    return [
        "Disney"    => fopen( "../archive/rank-Disney.csv", "r" ),
        "Marvel"    => fopen( "../archive/rank-Marvel.csv", "r" ),
        "StarWars"  => fopen( "../archive/rank-StarWars.csv", "r" )
    ];
}

function closeRankFiles( $files )
{
    foreach ( $files as $file )
    {
        fclose( $file );
    }
}


/*********************SUBMIT*********************/


function checkOverwrite( $id )
{
    $file = fopen( getPath( "ratings.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $movies = createEntryList( $file, $columns['iIndex'], $columns['iIndex'] );
    $movieId = findEntry( $movies, $id );
    fclose( $file );
    return [
        "isOverwrite"   => !!$movieId,
        "message"       => ( $movieId ) ? "Duplicate" : null
    ];
}

function saveMovie( $id, $title, $year, $index, $rating, $review, $overwrite )
{
    $fileName = getPath( "ratings.csv" );

    $isOverwrite = ( isset( $overwrite ) && $overwrite );
    $loadedMovie = ( $index && $isOverwrite ) ? loadFromListFile( $id ) : array();
    $index = ( isset($loadedMovie['index']) && $loadedMovie['index'] == $index ) ? null : $index;
    if ( $index )
    {
        if ( $isOverwrite )
        {
            deleteMovie( $id );
        }
        insertMovie( $fileName, array( $title, $id, $year, $rating, $review ), (int)$index );
    }
    elseif ( $isOverwrite )
    {
        editMovie( $fileName, array( 'id' => $id, 'title' => $title, 'year' => $year, 'rating' => $rating, 'review' => $review ) );
    }
    else
    {
        $file = fopen( $fileName, "a" );
        fputcsv( $file, array( $title, $id, $year, $rating, $review ) );
        fclose( $file );
    }

    archive( $fileName );
}

function archive( $fileName )
{
    $fileBase = str_replace( ".csv", "", $fileName );
    unlink( "$fileBase 5.csv" );
    rename( "$fileBase 4.csv", "$fileBase 5.csv" );
    rename( "$fileBase 3.csv", "$fileBase 4.csv" );
    rename( "$fileBase 2.csv", "$fileBase 3.csv" );
    rename( "$fileBase 1.csv", "$fileBase 2.csv" );
    copy( $fileName, "$fileBase 1.csv" );
}

function insertMovie( $fileName, $movie, $rank )
{
    echo $rank;
    if ( $rank < 0 )
    {
        $fileHandle = file( $fileName, FILE_SKIP_EMPTY_LINES );
        $count = count( $fileHandle );
        $rank = $count + $rank + 1;
    }

    $tempName = "temp.csv";
    $input = fopen( $fileName, "r" );
    $output = fopen( $tempName, "w" );

    $row = fgetcsv( $input );
    $index = 1;
    while ( $row !== false )
    {
        fputcsv( $output, $row );
        if ( $index === $rank )
        {
            fputcsv( $output, $movie );
        }

        $index++;
        $row = fgetcsv( $input );
    }

    fclose( $input );
    fclose( $output );

    unlink( $fileName );
    rename( $tempName, $fileName );
}

function editMovie( $fileName, $movie )
{
    $tempName = "temp.csv";
    $input = fopen( $fileName, "r" );
    $output = fopen( $tempName, "w" );

    $row = fgetcsv( $input );
    $columns = getColumns( $row );

    while ( $row !== false )
    {
        if ( $row[ $columns['iIndex'] ] === $movie['id'] )
        {
            $row[ $columns['rIndex'] ] = $movie['rating'];
            $row[ $columns['cIndex'] ] = $movie['review'];
        }
        fputcsv( $output, $row );
        $row = fgetcsv( $input );
    }

    fclose( $input );
    fclose( $output );

    unlink( $fileName );
    rename( $tempName, $fileName );
}


/**********************RANK**********************/


function checkRankOverwrite( $list, $id )
{
    $result['isSuccess'] = true;

    $file = fopen( getPath( "rank-$list.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $movies = createEntryList( $file, false, $columns['iIndex'] );
    $movieIndex = findEntry( $movies, $id );

    if ( $movieIndex )
    {
        $result['isSuccess'] = false;
        $result['message'] = "Duplicate";
        $result['list'] = $list;
        $result['rank'] = $movieIndex;
    }

    return $result;
}

function validateRank( $list, $rank )
{
    $result['isSuccess'] = false;

    $list = getListName( $list );
    $fileName = getPath( "rank-$list.csv" );
    $fileHandle = file( $fileName, FILE_SKIP_EMPTY_LINES );
    $count = count( $fileHandle ); //including added title

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
            $rankOfMovieResult = findTitle( $fileName, explode( ' ', $rank, 2 )[1] );
            if ( $rankOfMovieResult['isSuccess'] )
            {
                $atPosition = stripos( $rank, "before" ) === 0 || stripos( $rank, "above" ) === 0;
                $result['rank'] = $atPosition ? $rankOfMovieResult['rank'] : $rankOfMovieResult['rank'] + 1;
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

function getListName( $list )
{
    $list = strtolower( preg_replace('/\s+/', '', $list) );
    switch ( $list )
    {
        case "d":
        case "disney":
            $list = "Disney";
            break;
        case "m":
        case "mcu":
        case "marvel":
            $list = "Marvel";
            break;
        case "s":
        case "sw":
        case "starwars":
        case "star wars":
            $list = "StarWars";
            break;
        default:
            $list = null;
    }
    return $list;
}

function findTitle( $fileName, $title )
{
    $file = fopen( $fileName, "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $movies = createEntryList( $file, false, $columns['tIndex'] );
    $movieIndex = findEntry( $movies, $title );
    fclose( $file );
    return [
        'isSuccess' => !!$movieIndex,
        'rank'      => $movieIndex
    ];
}

function saveRankedMovie( $list, $rank, $id, $title, $year, $image, $review, $overwrite )
{
    $isOverwrite = ( isset( $overwrite ) && $overwrite );

    $fileName = getPath( "rank-$list.csv" );
    $tempName = "temp.csv";
    $input = fopen( $fileName, "r" );
    $output = fopen( $tempName, "w" );

    $index = 0;
    $row = fgetcsv( $input );
    $columns = getColumns( $row );
    fputcsv( $output, $row );

    while ( $row !== false )
    {
        $row = fgetcsv( $input );
        $index++;

        if ( $index == $rank )
        {
            fputcsv( $output, array( $title, $id, $year, $image, $review ) );
        }
        if ( !( $isOverwrite && $row[$columns['iIndex']] === $id ) )
        {
            fputcsv( $output, $row );
        }
    }

    fclose( $input );
    fclose( $output );

    unlink( $fileName );
    rename( $tempName, $fileName );
}


/*********************DELETE********************/


function deleteMovie( $id )
{
    $result['isSuccess'] = false;

    $fileName = getPath( "ratings.csv" );
    $tempName = "temp.csv";
    $input = fopen( $fileName, "r" );
    $output = fopen( $tempName, "w" );

    $row = fgetcsv( $input );
    $columns = getColumns( $row );
    while ( $row !== false )
    {
        if ( $row[ $columns['iIndex'] ] !== $id )
        {
            fputcsv( $output, $row );
        }
        else
        {
            $result['isSuccess'] = true;
        }
        $row = fgetcsv( $input );
    }

    fclose( $input );
    fclose( $output );

    unlink( $fileName );
    rename( $tempName, $fileName );

    return $result;
}

function deleteRankMovie( $list, $id ) //todo
{
    $result['isSuccess'] = false;

    $fileName = getPath( "rank-$list.csv" );
    $tempName = "temp.csv";
    $input = fopen( $fileName, "r" );
    $output = fopen( $tempName, "w" );

    $row = fgetcsv( $input );
    $columns = getColumns( $row );
    while ( $row !== false )
    {
        if ( $row[ $columns['iIndex'] ] !== $id )
        {
            fputcsv( $output, $row );
        }
        else
        {
            $result['isSuccess'] = true;
        }
        $row = fgetcsv( $input );
    }

    fclose( $input );
    fclose( $output );

    unlink( $fileName );
    rename( $tempName, $fileName );

    return $result;
}


/********************DOWNLOAD********************/


function download()
{
    $result['text'] = file_get_contents( "../archive/ratings.csv" );
    return $result;
}

function viewToWatch()
{
    $result = "";
    $file = fopen( getPath( "ToWatch.txt" ), "r" );
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