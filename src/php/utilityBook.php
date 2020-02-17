<?php
include_once( "utility.php" );

function getBookList()
{
    if ( empty($GLOBALS['fullBookList']) )
    {
        $GLOBALS['fullBookList'] = getBookListFromFile( getPath( "book-read.csv" ) );
    }
    return $GLOBALS['fullBookList'];
}


/********************INTERNET********************/


function requestGoodReads( $endpoint, array $params = array() )
{
    $params['key'] = "nyo2HHxoCfMtYFnzaGzaaQ"; //https://www.goodreads.com/api/keys
    $paramString = ( !empty( $params ) ? http_build_query( $params, "", "&" ) : "" );
    $url = "https://www.goodreads.com/$endpoint?$paramString";
    $response = file_get_contents( $url );
    $xmlArray = (array)simplexml_load_string( $response, 'SimpleXMLElement', LIBXML_NOCDATA );
    return ( empty($xmlArray) || $xmlArray[0] === false ) ? $response : json_decode( json_encode( $xmlArray ), 1 );
}

function getBookFromGoodreadsById( $id )
{
    $response = requestGoodReads( "book/show/$id.xml", [] );
    return getBookData( $response );
}

function getBookFromGoodreadsByTitle( $title )
{
    $response = requestGoodReads( 'book/title.xml', [
        'title' => $title
    ]);
    return getBookData( $response );
}

function getReviewedBookFromGoodreads( $id )
{
    $response['isSuccess'] = false;
    if ( $id )
    {
        $response = requestGoodReads( 'review/show_by_user_and_book', [
            'user_id' => "55277264",
            'book_id' => $id
        ]);
        $response = getBookData( $response['review'] );
    }

    return $response;
}

function getListFromGoodreads( $shelf, $sortType )
{
    $index = 1;
    $books = [];
    do {
        $response = requestGoodReads('review/list', [
            'v' => "2",
            'id' => "55277264",
            'shelf' => $shelf,
            'sort' => $sortType,
            'page' => $index,
            'per_page' => "100"
        ]);
        $books = array_merge( $books, $response['reviews']['review'] );
        $total = $response['reviews']['@attributes']['total'];
        $index++;
    } while ( $total > count($books) );
    return $books;
}

function getBookListFromGoodreads( $shelf, $sortType = "date_read" )
{
    $result = [];
    $books = getListFromGoodreads( $shelf, $sortType );
    $images = getImages();

    $index = 0;
    foreach ( $books as $book )
    {
        if ( $book['book']['id'] )
        {
            $result[$index] = getBookData( $book, $images[$book['book']['id']] );
            $index++;
        }
    }

    if ( $shelf === "read" )
    {
        $GLOBALS['fullBookList'] = $result;
    }
    saveFullBooksToFile( $shelf, $result );
    return $result;
}

function getBookData( $response, $imageOverride = null )
{
    $isSuccess  = ($response) ? true : false;
    $id         = $response['book']['id'];
    $index      = array_search( $id, array_column( getBookList(), 'id' ) );
    $year       = ( $response['book']['work'] && is_numeric( $response['book']['work']['original_publication_year'] ) ) ?
                    $response['book']['work']['original_publication_year'] :
                    ( ( $index && is_numeric( getBookList()[$index]['year'] ) ) ?
                        getBookList()[$index]['year'] :
                        ( is_numeric( $response['book']['publication_year'] ) ? $response['book']['publication_year'] : "" ) );
    $author     = getAuthor( $response['book']['authors'] );
    $review     = getCleanedReview( $response['body'] );
    $image      = $imageOverride ?? $response['book']['image_url'];

    return [
        'isSuccess' => $isSuccess,
        'id'        => $id,
        'title'     => $response['book']['title'],
        'year'      => $year,
        'author'    => $author,
        'image'     => $image,
        'review'    => $review,
        'rating'    => $response['rating'],
        'grRating'  => $response['book']['average_rating'],
        'url'       => getCDATA( $response['url'] )
    ];
}


/********************FILE I/O********************/


function saveFullBooksToFile( $shelf, $books )
{
    saveListToFile(
        getPath( "book-$shelf.csv" ),
        array( "Title", "Author", "ID", "Year", "Rating", "Review", "Image", "URL" ),
        $books,
        function( $book ) {
            return array( $book['title'], $book['author'], $book['id'], $book['year'], $book['rating'], $book['review'], $book['image'], $book['url'] );
    } );
}

function getBookIdFromFile( $title )
{
    $bookTitles = [];
    array_walk( getBookList(), function($value) use( &$bookTitles ) {
        $bookTitles[$value['id']] = $value['title'];
    });
    $bookId = findEntry( $bookTitles, $title );

    return $bookId;
}

function getBookListFromFileByShelf( $shelf )
{
    return array_values( ( $shelf === "read" ) ? getBookList() : getBookListFromFile( getPath( "book-$shelf.csv" ) ) );
}

function getBookListFromFile( $fileName )
{
    return getListFromFile( $fileName, function( $row, $columns ) {
        return [
            "id"     => $row[$columns['iIndex']],
            "title"  => $row[$columns['tIndex']],
            "author" => $row[$columns['aIndex']],
            "year"   => $row[$columns['yIndex']],
            "review" => $row[$columns['cIndex']],
            "rating" => $row[$columns['rIndex']],
            "image"  => $row[$columns['pIndex']],
            "url"    => $row[$columns['uIndex']]
        ];
    } );
}


/*********************OTHER**********************/


function getImages()
{
    $file = fopen( getPath( "book-images.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $images = createEntryList( $file, $columns['iIndex'], $columns['pIndex'] );
    fclose( $file );
    return $images;
}

function getAuthor( $authorsData )
{
    return $authorsData['author']['name']; //to retrieve other authors, will have to refactor from array to simpleXmlElement
}

function getCleanedReview( $review )
{
    $result = is_array( $review ) ? implode( $review ) : $review;
    $result = trim( $result );
    if ( strlen( $result ) > 1000 )
    {
        $result = substr( $result, 0, 1000 ) . "...";
    }

    $result = str_replace( "<a ", "<a class='link'", $result );

    $result = empty( $result ) ? "No Review" : $result;
    return $result;
}

function getCDATA( $url )
{
    return preg_replace( '/^\s*\/\/<!\[CDATA\[([\s\S]*)\/\/\]\]>\s*\z/', '$1', $url );
}
?>
