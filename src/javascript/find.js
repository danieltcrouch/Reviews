/********************MOVIE********************/


function findMovie( value, type, callback )
{
    value = value.trim();
    if ( value )
    {
        var isImdbId = value.search(/tt\d{7,8}/i) >= 0;
        if ( isImdbId )
        {
            findMovieById( value, type, callback );
        }
        else
        {
            findMovieByTitle( value, type, callback );
        }
    }
}

function findMovieById( id, type, callback )
{
    $.post(
        "php/find.php",
        {
            action: "getMovieById",
            type:   type,
            id:     id
        },
        function( response ) {
            response = JSON.parse( response );
            response.search = id;
            response.isSearchId = true;
            callback( response );
        }
    );
}

function findMovieByTitle( title, type, callback )
{
    $.post(
        "php/find.php",
        {
            action: "getMovieByTitle",
            type:   type,
            title:  title
        },
        function( response ) {
            response = JSON.parse( response );
            response.search = title;
            response.isSearchId = false;
            callback( response );
        }
    );
}


/********************BOOK********************/


function findBook( value, callback )
{
    value = value.trim();
    if ( value )
    {
        var isGoodreadsId = !isNaN( value ) && !["300", "1984", "2001", "11/22/63", "1408" ].includes( value ) ;
        if ( isGoodreadsId )
        {
            findBookById( value, callback );
        }
        else
        {
            findBookByTitle( value, callback );
        }
    }
}

function findBookById( id, callback )
{
    $.post(
        "php/find.php",
        {
            action: "getBookById",
            id:     id
        },
        function( response ) {
            response = JSON.parse( response );
            response.search = id;
            response.isSearchId = true;
            callback( response );
        }
    );
}

function findBookByTitle( title, callback )
{
    $.post(
        "php/find.php",
        {
            action: "getBookByTitle",
            title:  title
        },
        function( response ) {
            response = JSON.parse( response );
            response.search = title;
            response.isSearchId = false;
            callback( response );
        }
    );
}