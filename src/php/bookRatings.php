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
    $result['aIndex'] = array_search( "Author", $firstRow, true );
    $result['yIndex'] = array_search( "Year", $firstRow, true );
    $result['cIndex'] = array_search( "Review", $firstRow, true );
    $result['rIndex'] = array_search( "Rating", $firstRow, true );
    $result['pIndex'] = array_search( "Image", $firstRow, true );

    return $result;
}

function requestGoodReads( $endpoint, array $params = array() )
{
    $params['key'] = "nyo2HHxoCfMtYFnzaGzaaQ"; //https://www.goodreads.com/api/keys
    $url = "https://www.goodreads.com/" . $endpoint . "?" . ( !empty( $params ) ? http_build_query( $params, "", "&" ) : "" );
    $response = file_get_contents( $url );

    $xmlArray = (array)simplexml_load_string( $response, 'SimpleXMLElement', LIBXML_NOCDATA );
    return ( empty($xmlArray) || $xmlArray[0] === false ) ? $response : json_decode( json_encode( $xmlArray ), 1 );
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

function getTrimmedReview( $review )
{
    $result = trim( $review );
    if ( strlen( $result ) > 1000 )
    {
        $result = substr( $result, 0, 1000 ) . "...";
    }
    return $result;
}

function getImages()
{
    $result = [];
    $file = fopen( "../resources/BookFavorites.csv", "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $result[trim( $row[$columns['iIndex']] )] = trim( $row[$columns['pIndex']] );
        $row = fgetcsv( $file );
    }

    fclose( $file );

    return $result;
}

function getBookIdFromFile( $title )
{
    $bookId = "";
    $file = fopen( getPath( "TempBookRatings-read.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        if ( compareTitles( $title, trim( $row[$columns['tIndex']] ) ) )
        {
            $bookId = $row[$columns['iIndex']];
            break;
        }
        $row = fgetcsv( $file );
    }

    fclose( $file );
    return $bookId;
}

//function getBookIdFromGR( $title, $author = "" )
//{
//    if ( preg_match( '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i', str_replace('-', '', $title), $matches) )
//    {
//        $bookId = requestGoodReads( 'book/isbn_to_id', [
//            'isbn' => $matches[1]
//        ]);
//    }
//    else
//    {
//        $bookDetails = requestGoodReads( 'book/title', [
//            'title' => $title,
//            'author' => $author
//        ]);
//        $bookId = $bookDetails['book']['id'];
//    }
//    return $bookId;
//}

function getBook( $title )
{
    $response = requestGoodReads( 'review/show_by_user_and_book', [
        'user_id' => "55277264",
        'book_id' => getBookIdFromFile( $title )
    ]);

    return [
        'isSuccess' => ($response) ? true : false,
        'title'     => $response['review']['book']['title'],
        'year'      => $response['review']['book']['publication_year'],
        'author'    => $response['review']['book']['authors']['author']['name'],
        'cover'     => $response['review']['book']['image_url'],
        'grRating'  => $response['review']['book']['average_rating'],
        'rating'    => $response['review']['rating'],
        'review'    => $response['review']['body']
    ];
}

function getList( $shelf, $sortType, $includeImages )
{
    $result = [];

    $index = 1;
    $books = [];
    do {
        $response = requestGoodReads( 'review/list', [
            'v'         => "2",
            'id'        => "55277264",
            'shelf'     => $shelf,
            'sort'      => $sortType,
            'page'      => $index,
            'per_page'  => "100"
        ]);
        $books = array_merge( $books, $response['reviews']['review'] );
        $total = $response['reviews']['@attributes']['total'] ;
        $index++;
    } while ( $total > count( $books ) );

    $file = null;
    if ( $shelf )
    {
        $file = fopen( getPath( "TempBookRatings-$shelf.csv" ), "w" );
        fputcsv( $file, array( "Title", "Author", "ID", "Year", "Rating", "Review", "Image" ) );
    }

    $images = [];
    if ( $includeImages )
    {
        $images = getImages(); //todo - need a way to add image via GUI
        //todo - need a way to download all files that are alterable (CSVs)
        //todo - which means I should also be saving these to archives any time they're changed
    }

    $index = 1;
    foreach ( $books as $book )
    {
        if ( $book['book']['id'] )
        {
            $year = $book['book']['publication_year'];
            $year = is_numeric( $year ) ? $year : "";
            $displayYear = is_numeric( $year ) ? "($year) " : "";

            $id = $book['book']['id'];
            $title = $book['book']['title'];
            $author = $book['book']['authors']['author']['name'];
            $rating = $book['rating'];
            $review = getTrimmedReview( $book['body'] );
            //$review = getTrimmedReview( $book['body'] ); //todo - reviews with links need a class added to the anchor tag to make them stand out
            $review = isset( $review ) ? $review : "No Review";
            $image = ( $images[$id] ) ? $images[$id] : $book['book']['image_url'];
            $item = "<div>$index. <strong>$title</strong>, $author $displayYear- <strong>$rating/5</strong> - $review</div>";
            $item .= $includeImages ? "<img src='$image' height='300px' alt='Book Cover' /><br/><br/>" : "";

            $index++;
            array_push( $result, $item );
            if ( $file )
            {
                fputcsv( $file, array( $title, $author, $id, $year, $rating, $review, $image ) );
            }
        }
    }

    if ( $file )
    {
        fclose( $file );
    }

    return $result;
}

function getBookList()
{
    return (object)[
        "read"  => getList( "read", "date_read", false ),
        "title" => getList( "read", "title", false )
    ];
}

function getFavoritesList()
{
    return getList( "favorites", "date_read", true );
}

function getTempList( $shelf, $includeImages )
{
    $result = [];
    $file = fopen( getPath( "TempBookRatings-$shelf.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );

    $index = 1;
    $row = fgetcsv( $file );
    while ( $row !== false )
    {
        $year = $row[$columns['yIndex']];
        $year = is_numeric( $year ) ? $year : "";
        $displayYear = is_numeric( $year ) ? "($year) " : "";

        $title = $row[$columns['tIndex']];
        $author = $row[$columns['aIndex']];
        $rating = $row[$columns['rIndex']];
        $image = $row[$columns['pIndex']];
        $review = getTrimmedReview( $row[$columns['cIndex']] );
        $review = isset( $review ) ? $review : "No Review";
        $item = "<div>$index. <strong>$title</strong>, $author $displayYear- <strong>$rating/5</strong> - $review</div>";
        $item .= $includeImages ? "<img src='$image' height='300px' alt='Book Cover' /><br/><br/>" : "";

        $index++;
        array_push( $result, $item );
        $row = fgetcsv( $file );
    }

    fclose( $file );
    return $result;
}

function getTempBookList()
{
    return getTempList( "read", false );
}

function getTempFavoritesList()
{
    return getTempList( "favorites", true );
}

function saveBookToRead( $title )
{
    $fileName = getPath( "ToRead.txt" );
    $file = fopen( $fileName, "a" );
    fwrite( $file, $title . "\n" );
    fclose( $file );
}

if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

    if ( isset( $_POST['title'] ) && isset( $_POST['author'] ) )
	{
		$result = $action( $_POST['title'], $_POST['author'] );
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