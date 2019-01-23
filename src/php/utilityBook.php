<?php
include_once( "utility.php" );

function requestGoodReads( $endpoint, array $params = array() )
{
    $params['key'] = "nyo2HHxoCfMtYFnzaGzaaQ"; //https://www.goodreads.com/api/keys
    $url = "https://www.goodreads.com/" . $endpoint . "?" . ( !empty( $params ) ? http_build_query( $params, "", "&" ) : "" );
    $response = file_get_contents( $url );

    $xmlArray = (array)simplexml_load_string( $response, 'SimpleXMLElement', LIBXML_NOCDATA );
    return ( empty($xmlArray) || $xmlArray[0] === false ) ? $response : json_decode( json_encode( $xmlArray ), 1 );
}

function getFullBookList()
{
    if ( empty($_SESSION['fullBookList']) )
    {
        $_SESSION['fullBookList'] = getTempListFromFile( "read" ); //updates list
    }
    return $_SESSION['fullBookList'];
}

function getBookIdFromFile( $title )
{
    $bookTitles = [];
    array_walk( getFullBookList(), function($value, $key) use( &$bookTitles ) {
        $bookTitles[$key] = $value['title'];
    });
    $bookId = findEntry( $bookTitles, $title );

    return $bookId;
}

function getBookFromGoodreads( $id )
{
    $response = requestGoodReads( 'review/show_by_user_and_book', [
        'user_id' => "55277264",
        'book_id' => $id
    ]);

    return [
        'isSuccess' => ($response) ? true : false,
        'title'     => $response['review']['book']['title'],
        'year'      => $response['review']['book']['publication_year'],
        'author'    => $response['review']['book']['authors']['author']['name'],
        'image'     => $response['review']['book']['image_url'],
        'grRating'  => $response['review']['book']['average_rating'],
        'rating'    => $response['review']['rating'],
        'review'    => $response['review']['body']
    ];
}

function getBook( $title )
{
    $id = getBookIdFromFile( $title );
    $result = getBookFromGoodreads( $id );
    $result['id'] = $id;
    return $result;
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

function getImages()
{
    $file = fopen( getPath( "book-images.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $images = createEntryList( $file, $columns['iIndex'], $columns['pIndex'] );
    fclose( $file );
    return $images;
}

function getHTML( $index, $url, $title, $author, $year, $rating, $review, $includeImages, $image )
{
    $result = "<div>$index. <a class='link' href='$url'>$title</a>, $author $year- <strong>$rating/5</strong> - $review</div>";
    $result .= $includeImages ? "<img src='$image' height='300px' alt='Book Cover' /><br/><br/>" : "";
    return $result;
}

function getBookListFromGoodreads( $shelf, $sortType )
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

function saveListToFile( $shelf, $books )
{
    $file = fopen( getPath( "book-$shelf.csv" ), "w" );
    fputcsv( $file, array( "Title", "Author", "ID", "Year", "Rating", "Review", "Image", "URL" ) );
    foreach ( $books as $book )
    {
        fputcsv( $file, $book );
    }
    fclose( $file );
}

function getListHTML( $shelf, $sortType, $includeImages )
{
    $result = [];
    $bookData = [];
    $books = getBookListFromGoodreads( $shelf, $sortType );
    $images = $includeImages ? getImages() : [];

    $index = 1;
    foreach ( $books as $book )
    {
        if ( $book['book']['id'] )
        {
            $year        = is_numeric( $book['book']['publication_year'] ) ? $book['book']['publication_year'] : "";
            $displayYear = is_numeric( $year ) ? "($year) " : "";

            $id     = $book['book']['id'];
            $title  = $book['book']['title'];
            $author = $book['book']['authors']['author']['name'];
            $rating = $book['rating'];
            $review = getCleanedReview( $book['body'] );
            $url    = getCDATA( $book['url'] );
            $image  = ( $images[$id] ) ? $images[$id] : $book['book']['image_url'];

            array_push( $result, getHTML( $index, $url, $title, $author, $displayYear, $rating, $review, $includeImages, $image ) );
            array_push( $bookData, array( $title, $author, $id, $year, $rating, $review, $image, $url ) );

            $index++;
        }
    }

    saveListToFile( $shelf, $bookData );
    return $result;
}

function getBookList()
{
    $result = [
        "read"  => getListHTML( "read", "date_read", false ),
        "title" => getListHTML( "read", "title", false )
    ];
    $_SESSION['fullBookList'] = $result['read'];
    return $result;
}

function getFavoritesList()
{
    return getListHTML( "favorites", "date_read", true );
}

function getTempListFromFile( $shelf )
{
    $file = fopen( getPath( "book-$shelf.csv" ), "r" );
    $columns = getColumns( fgetcsv( $file ) );
    $result = createEntryObjectList( $file, $columns, function( $row, $columns ) {
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
    });
    fclose( $file );
    return $result;
}

function getTempList( $shelf, $includeImages )
{
    $result = [];
    $books = ( $shelf === "read" ) ? getFullBookList() : getTempListFromFile( $shelf );

    $index = 1;
    foreach ( $books as $book )
    {
        $displayYear = is_numeric( $book['year'] ) ? "($book[year]) " : "";
        array_push( $result, getHTML( $index, $book['url'], $book['title'], $book['author'], $displayYear, $book['rating'], $book['review'], $includeImages, $book['image'] ) );
        $index++;
    }

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
?>