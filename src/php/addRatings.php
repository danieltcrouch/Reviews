<?php
function getPath( $fileName )
{
    $newDirectory = "../archive/";
    $path = $newDirectory . $fileName;
    $pathInfo = pathinfo( $path );
    if ( !file_exists( $pathInfo['dirname'] ) )
    {
        mkdir( $pathInfo['dirname'], 0777, true );
    }
    return $path;
}

function getColumns( $firstRow )
{
    $result['iIndex'] = array_search( "ID", $firstRow, true );
    $result['tIndex'] = array_search( "Title", $firstRow, true );
    $result['yIndex'] = array_search( "Year", $firstRow, true );
    $result['cIndex'] = array_search( "Review", $firstRow, true );
    $result['rIndex'] = array_search( "Rating", $firstRow, true );
    $result['pIndex'] = array_search( "Image", $firstRow, true );

    return $result;
}

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

function checkOverwrite( $id )
{
    $result['isSuccess'] = true;

    $file = fopen( "../resources/ratings.csv", "r" );
    $headers = fgetcsv( $file );
    $idIndex = array_search( "ID", $headers, true );

    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $rowId = trim( $row[$idIndex] );
        if ( $id === $rowId )
        {
            $result['isSuccess'] = false;
            $result['message'] = "Duplicate";
            break;
        }
        $row = fgetcsv( $file );
    }

    fclose( $file );

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
            $list = "StarWars";
            break;
        default:
            $list = null;
    }
    return $list;
}

function checkRankedOverwrite( $list, $id )
{
    $result['isSuccess'] = true;

    $list = getListName( $list );
    if ( $list )
    {
        $result['list'] = $list;
        $file = fopen( "../resources/$list.csv", "r" );
        $columns = getColumns( fgetcsv( $file ) );

        $rank = 1;
        $row = fgetcsv( $file );
        while ( $row !== false )
        {
            $rowId = trim( $row[$columns['iIndex']] );
            if ( $id === $rowId )
            {
                $result['isSuccess'] = false;
                $result['message'] = "Duplicate";
                $result['rank'] = $rank;
                break;
            }
            $rank++;
            $row = fgetcsv( $file );
        }
        fclose( $file );
    }
    else
    {
        $result['isSuccess'] = false;
        $result['message'] = "List name is invalid.";
    }

    return $result;
}

function compareTitles( $searchTitle, $rowTitle )
{
    $result = false;
    if ( stripos( $rowTitle, $searchTitle ) !== false ) //todo - develop for more sophisticated search
    {
        $result = true;
    }
    return $result;
}

function findTitle( $fileName, $title )
{
    $result['isSuccess'] = false;
    $file = fopen( $fileName, "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $rank = 1;
    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        if ( compareTitles( $title, trim( $row[$columns['tIndex']] ) ) )
        {
            $result['isSuccess'] = true;
            $result['rank'] = $rank;
            break;
        }
        $rank++;
        $row = fgetcsv( $file );
    }
    fclose( $file );

    return $result;
}

function validateRank( $list, $answer )
{
    $result['isSuccess'] = false;

    $fileName = "../resources/$list.csv";
    $fileHandle = file( $fileName, FILE_SKIP_EMPTY_LINES );
    $count = count( $fileHandle ); //including added title

    $answer = strtolower( $answer );
    if ( is_numeric( $answer ) )
    {
        $result['rank'] = (int)$answer;
    }
    else
    {
        switch ( $answer )
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

        if ( stripos( $answer, "before" ) === 0 || stripos( $answer, "after" ) === 0 ||
             stripos( $answer, "above" )  === 0 || stripos( $answer, "below" ) === 0 )
        {
            $rankOfMovieResult = findTitle( $fileName, explode( ' ', $answer, 2 )[1] );
            if ( $rankOfMovieResult['isSuccess'] )
            {
                $atPosition = stripos( $answer, "before" ) === 0 || stripos( $answer, "above" ) === 0;
                $result['rank'] = $atPosition ? $rankOfMovieResult['rank'] : $rankOfMovieResult['rank'] + 1;
            }
        }
    }

    if ( !is_numeric( $result['rank'] ) || $result['rank'] > $count )
    {
        $result['message'] = "Invalid Rank";
    }
    else
    {
        $result['isSuccess'] = true;
    }

    return $result;
}

function insertMovie( $fileName, $movie, $rank )
{
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

function saveMovie( $id, $title, $year, $index, $rating, $review, $overwrite )
{
    $fileName = "../resources/ratings.csv";

    $isOverwrite = ( isset( $overwrite ) && $overwrite );
    $loadedMovie = ( $index && $isOverwrite ) ? load( $id ) : array();
    $rank = ( isset($loadedMovie['index']) && $loadedMovie['index'] == $index ) ? null : $index;
    if ( $rank )
    {
        if ( $isOverwrite )
        {
            remove( $id );
        }
        insertMovie( $fileName, array( $title, $id, $year, $rating, $review ), (int)$rank );
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

    copy( $fileName, getPath( "ratings " . date( "Y-m-d H:i:s" ) . ".csv" ) ); //for historical purposes
}

function saveRankedMovie( $list, $rank, $id, $title, $year, $image, $review, $overwrite )
{
    $isOverwrite = ( isset( $overwrite ) && $overwrite );

    $fileName = "../resources/$list.csv";
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

    copy( $fileName, getPath( "$list " . date( "Y-m-d H:i:s" ) . ".csv" ) ); //for historical purposes
}

function load( $movie )
{
    $result['isSuccess'] = false;

    $file = fopen( "../resources/ratings.csv", "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $index = 1;
    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $rowId = trim( $row[ $columns['iIndex'] ] );
        $rowTitle = trim( $row[ $columns['tIndex'] ] );
        if ( $movie === $rowId || stripos( $rowTitle, $movie ) !== false )
        {
            $result['isSuccess'] = true;
            $result['id'] = $rowId;
            $result['title'] = $rowTitle;
            $result['year'] = trim( $row[ $columns['yIndex'] ] );
            $result['rating'] = trim( $row[ $columns['rIndex'] ] );
            $result['review'] = trim( $row[ $columns['cIndex'] ] );
            $result['index'] = $index;
            if ( $movie === $rowId || strtoupper( $rowTitle) === strtoupper( $movie ) )
            {
                break;
            }
        }
        $index++;
        $row = fgetcsv( $file );
    }

    return $result;
}

function remove( $id )
{
    $result['isSuccess'] = false;

    $fileName = "../resources/ratings.csv";
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

function download()
{
    $result['text'] = file_get_contents( "../resources/ratings.csv" );
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
	elseif ( isset( $_POST['list'] ) && isset( $_POST['answer'] ) )
	{
		$result = $action( $_POST['list'], $_POST['answer'] );
	}
	elseif ( isset( $_POST['id'] ) )
	{
		$result = $action( $_POST['id'] );
	}
	elseif ( isset( $_POST['movie'] ) )
	{
		$result = $action( $_POST['movie'] );
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