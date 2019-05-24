var enterMediaType;
var enterMovieType;
var isOverwrite;

var genres = [];
var rankList = [];

function setMediaType( mediaType )
{
    enterMediaType = mediaType;
    if ( mediaType === "movie" )
    {
        $('#movieInputs').show();
        $('#movieTypeButtons').show();
        $('#delete').show();

        $('#addImage').hide();
    }
    else
    {
        $('#addImage').show();

        $('#movieInputs').hide();
        $('#movieTypeButtons').hide();
        $('#delete').hide();
    }

    clear();
}

function isMovie()
{
    return enterMediaType === "movie";
}

function getByMediaType( movieValue, bookValue )
{
    var result = null;
    switch ( enterMediaType ) {
        case "movie":
            result = movieValue;
            break;
        case "book":
            result = bookValue;
    }
    return result;
}

function setMovieType( movieType )
{
    enterMovieType = movieType;
    var id = $('#id').val();
    clear();
    autoFillById( id );

    if ( isFullList() )
    {
        $('#rating').show();
        $('#list').hide();
        setIndexRankHandlers( false );
    }
    else
    {
        $('#rating').hide();
        $('#list').show();
        $('#list').attr( "placeholder", isFranchiseList() ? "Franchise" : "Genre" );
        setIndexRankHandlers( true );
    }
}

function isFullList()
{
    return enterMovieType === "full";
}

function isGenreList()
{
    return enterMovieType === "genre";
}

function isFranchiseList()
{
    return enterMovieType === "franchise";
}

function getByMovieType( fullValue, genreValue, franchiseValue )
{
    var result = null;
    switch ( enterMovieType ) {
        case "full":
            result = fullValue;
            break;
        case "genre":
            result = genreValue;
            break;
        case "franchise":
            result = franchiseValue;
    }
    return result;
}


/********************AUTOFILL********************/


function autoFillTab( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 9 )
    {
        autoFill( e );
    }
}

function autoFill( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 13 || charCode === 9 )
    {
        var search = $('#title').val();
        if ( search )
        {
            var isImdbId = isMovie() && search.search(/tt\d{7,8}/i) >= 0;
            var isGoodreadsId = !isMovie() && !isNaN( search ) && !["300", "1984", "2001", "11/22/63", "1408" ].includes( search ) ;
            if ( isImdbId || isGoodreadsId )
            {
                autoFillById( search );
            }
            else
            {
                autoFillByTitle( search );
            }
        }
        else
        {
            clear();
        }
    }
}

function autoFillById( id )
{
    if ( id )
    {
        $.post(
            "php/enter.php",
            {
                action: isMovie() ? ( getByMovieType( "getMovieById", "getGenreMovieById", "getFranchiseMovieById" ) ) : "getBookById",
                id: id
            },
            autoFillByIdCallback
        );
    }
}

function autoFillByIdCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        fillData( response );
    }
}

function autoFillByTitle( title )
{
    $.post(
        "php/enter.php",
        {
            action: isMovie() ? ( getByMovieType( "getMovieByTitle", "getGenreMovieByTitle", "getFranchiseMovieByTitle" ) ) : "getBookByTitle",
            title: title
        },
        autoFillByTitleCallback
    );
}

function autoFillByTitleCallback( response )
{
    response = JSON.parse( response );

    var term = getByMediaType( "movie", "book" );
    if ( response && response.isSuccess )
    {
        var title     = getByMediaType( "Movie Match", "Library Look-up" );
        var info      = getByMediaType( "ID: " + response.id, response.author );
        var imageAlt  = getByMediaType( "Movie Poster", "Book Cover" );
        var innerHTML = "Is this the correct " + term + "?<br/><br/>" +
                        "<strong>" + response.title + "</strong> (" + response.year + ")<br/>" +
                        info + "<br/><br/>" +
                        "<img src='" + response.image + "' height='300px' alt='" + imageAlt + "'>";
        showConfirm( title, innerHTML, function( answer ) {
            ( answer ) ? fillData( response ) : showToaster( "Try entering " + getByMediaType( "IMDB", "GoodReads" ) + " ID." );
        });
    }
    else
    {
        showToaster( "No " + term + " found." );
        clear();
    }
}

//ARCHIVE
// function promptGoogle( search )
// {
//     var db = isMovie() ? "IMDB" : "GoodReads";
//     var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=" + db + "%20" + search + "'>Google</a><br/>Then enter ID:";
//     showPrompt( "Enter ID", html, autoFillById, "tt0082971", true );
// }

function fillData( response )
{
    clear();
    $('#title').val( response.title );
    $('#year').val( response.year );

    $('#rating').val( response.rating );
    $('#review').val( response.review );
    $('#index').val( response.index );

    var list = isFullList() ? "" : getRankListName( response.list );
    $('#list').val( list );
    $('#id').val( response.id );
    $('#image').val( response.image );

    isOverwrite = response.isPreviouslyReviewed;
    if ( isOverwrite )
    {
        if ( !isFullList() )
        {
            setRankList( response.list );
        }
    }
    else
    {
        showToaster( getByMediaType( "Movie", "Book" ) + " not previously reviewed." );
    }
}

function setRankList( list )
{
    $.post(
        "php/enter.php",
        {
            action: "get" + (isGenreList() ? "Genre" : "Franchise"),
            list:   list
        },
        function ( response ) {
            rankList = JSON.parse( response );
        }
    );
}

function clear()
{
    $('#title').val( "" );
    $('#year').val( "" );
    $('#index').val( "" );
    $('#rating').val( "" );
    $('#review').val( "" );
    $('#id').val( "" );
    $('#list').val( "" );
    $('#image').val( "" );
    isOverwrite = null;
    rankList = null;
}


/*********************SUBMIT*********************/


function submit()
{
    if ( isMovie() )
    {
        checkSubmit();
    }
    else
    {
        checkBookSubmit();
    }
}


/*********************MOVIE**********************/


function checkSubmit()
{
    var submit = getByMovieType( submitMovie, submitRank, submitRank );
    if ( validate() )
    {
        if ( isOverwrite )
        {
            var term = isFullList() ? "rated" : "ranked";
            showConfirm( "Entry Exists", "This movie has already been " + term + ". Overwrite?", function( answer ) {
                if ( answer )
                {
                    submit();
                }
            });
        }
        else
        {
            submit();
        }
    }
}

function validate()
{
    var result = true;

    var id = $('#id').val();
    var year = $('#year').val();
    var rating = $('#rating').val();
    var list = $('#list').val();
    var rank = $('#index').val();

    if ( !id )
    {
        result = false;
        showToaster( "No movie ID present." );
    }
    else if ( !year )
    {
        result = false;
        showToaster( "No year present." );
    }
    else if ( isFullList() )
    {
        if ( !rating )
        {
            result = false;
            showToaster( "No rating present." );
        }
    }
    else
    {
        if ( !list )
        {
            result = false;
            showToaster( "No list type present." );
        }
        else if ( !rank )
        {
            result = false;
            showToaster( "No rank present." );
        }
    }

    return result;
}

function submitMovie()
{
    $.post(
        "php/enter.php",
        {
            action: "saveMovie",
            index:  $('#index').val(),
            title:  $('#title').val(),
            id:     $('#id').val(),
            year:   $('#year').val(),
            rating: $('#rating').val(),
            review: $('#review').val() || "***",
            overwrite: isOverwrite
        },
        submitCallback
    );
}

function submitCallback( response )
{
    clear();
    showToaster( "Success!" );
}


/**********************RANK**********************/
//Genres and Franchise


function populateGenres()
{
    $.post(
        "php/enter.php",
        { action: "getGenres" },
        function( response ) {
            genres = JSON.parse( response );
        }
    );
}

function setIndexRankHandlers( getListModal )
{
    var index = $('#index');
    index.attr( "placeholder", getListModal ? "Click to change Rank" : "Index" );
    if ( getListModal )
    {
        index.click( getRanking );
        index.keypress( getRanking );
    }
    else
    {
        index.unbind( "click", getRanking );
        index.unbind( "keypress", getRanking );
    }
}

function getRanking()
{
    if ( rankList )
    {
        var id = $('#id').val();
        if ( id && !rankList.some( m => m.id === id ) )
        {
            rankList.push( {
                id:     id,
                title:  $('#title').val(),
                image:  $('#image').val()
            } );
        }

        openSortModal( rankList, getRankingCallback, true );
    }
    else
    {
        if ( isGenreList() && genres )
        {
            openGenreModal(
                genres,
                function( listName, response ) {
                    $('#list').val( getRankListName( listName ) );
                    rankList = response;
                    getRanking();
                },
                true
            );
        }
        else if ( isFranchiseList() )
        {
            openFranchiseModal( function( listName, response ) {
                $('#list').val( getRankListName( listName ) );
                rankList = response;
                getRanking();
            } );
        }
    }
}

function getRankingCallback( response )
{
    var mainIndex = response.indexOf( $('#title').val() );
    var changed = false;
    rankList.sort(function( a, b ){
        var result = response.indexOf(a.title) - response.indexOf(b.title);
        changed = changed || result < 0;
        return result;
    });
    if ( mainIndex >= 0 )
    {
        $('#index').val( mainIndex + 1 );
    }

    if ( changed )
    {
        submitRanks();
    }
}

function submitRanks()
{
    $.post(
        "php/enter.php",
        {
            action: "saveRankedMovies",
            type:   enterMovieType,
            list:   getRankListId( $('#list').val() ),
            movies: rankList
        },
        function() {
            showToaster( "Ranks updated." );
            if ( isFranchiseList() )
            {
                updatePersonalRankings();
            }
        }
    );
}

function updatePersonalRankings() //used for Averages
{
    $.post(
        "php/database.php",
        {
            action: "updatePersonalRankings",
            list:   getRankListId( $('#list').val() ),
            movies: rankList
        }
    );
}

function submitRank()
{
    $.post(
        "php/enter.php",
        {
            action: "saveRankedMovie",
            type:   enterMovieType,
            list:   getRankListId( $('#list').val() ),
            rank:   $('#index').val(),
            title:  $('#title').val(),
            id:     $('#id').val(),
            year:   $('#year').val(),
            image:  $('#image').val(),
            review: $('#review').val() || "***"
        },
        submitCallback
    );
}

function getRankListId( list )
{
    var result;

    if ( isGenreList() )
    {
        var genre = (genres) ? genres.find( genre => genre.title === list ) : null;
        result = (genre) ? genre.id : list;
    }
    else
    {
        result = getRankListName( list );
        result = (result === "Star Wars") ? "StarWars" : result;
    }

    return result;
}

function getRankListName( list )
{
    var result;

    if ( isGenreList() )
    {
        var genre = (genres) ? genres.find( genre => genre.id === list ) : null;
        result = (genre) ? genre.title : list;
    }
    else
    {
        list = (list) ? list.toLowerCase() : "";
        switch ( list )
        {
        case "d":
        case "disney":
            result = "Disney";
            break;
        case "m":
        case "mcu":
        case "marvel":
            result = "Marvel";
            break;
        case "s":
        case "sw":
        case "starwars":
        case "star wars":
            result = "Star Wars";
        }
    }

    return result;
}


/*********************GENRE**********************/


// test when genre is entered that doesn't exist
// function editGenres()
// {
//     var genreText = genreNames.join('\n');
//     showBigPrompt(
//         "Edit Genres",
//         "Choose genreNames to appear on main page:",
//         editGenresCallback,
//         genreText );
// }
//
// function editGenresCallback( response )
// {
//     genreNames = response.split('\n').map( movie => { return {id: movie.trim(), title: movie.trim() }; } ).filter( Boolean );
//     $.post(
//         "php/enter.php",
//         {
//             action: "",
//             type:   null
//         },
//         null
//     );
// }


/*********************DELETE********************/


function checkDelete()
{
    if ( $('#id').val() )
    {
        var innerHTML = "Are you sure you would like to delete this movie?<br/><br/>" +
                        "<strong>" + $('#title').val() + "</strong> (" + $('#year').val() + ")<br/>" +
                        "(" + $('#id').val() + ")<br/><br/>" +
                        "<img src='" + $('#image').val() + "' height='300px' alt='Movie Poster'>";
        showConfirm( "Delete Movie", innerHTML, function( answer ) {
            if ( answer )
            {
                deleteMovie();
            }
        });
    }
    else
    {
        showToaster( "No movie ID present." );
    }
}

function deleteMovie()
{
    var data = isFullList() ?
        {
            action: "deleteMovie",
            id:     $('#id').val()
        } :
        {
            action: "deleteRankMovie",
            type:   enterMovieType,
            list:   getRankListId( $('#list').val() ),
            id:     $('#id').val()
        };

    $.post(
        "php/enter.php",
        data,
        function( response ) {
            response = JSON.parse( response );
            var success = ( response && response.isSuccess );
            showToaster( success ? "Movie removed." : "Movie not found." );
            if ( success )
            {
                clear();
            }
        }
    );
}


/*********************BOOK***********************/


function checkBookSubmit()
{
    if ( $('#id').val() )
    {
        submitBook();
    }
    else
    {
        showToaster( "No book ID present." );
    }
}

function submitBook()
{
    document.getElementById("review").select();
    document.execCommand("copy");
    showToaster( "Review copied to clipboard" );

    if ( isOverwrite )
    {
        window.open( "https://www.goodreads.com/review/edit/" + $('#id').val() );
    }
    else
    {
        window.open( "https://www.goodreads.com/review/list/55277264-daniel-crouch?utf8=%E2%9C%93&search%5Bquery%5D=" + $('#title').val() );
    }

    clear();
}


/*********************IMAGE**********************/


function addImage()
{
    if ( $('#id').val() )
    {
        var url = $('#review').val();
        var isImage = url.match(/\.(jpeg|jpg|gif|png)$/) != null &&
            url.startsWith("http") &&
            !url.includes(" ");

        if (!isImage)
        {
            showPrompt(
                "Enter Image URL",
                "Enter an image URL to use for this book:",
                function ( response ) { response ? submitImage( response ) : null; },
                "",
                true
            );
        }
        else
        {
            submitImage( url );
        }
    }
    else
    {
        showToaster( "No book ID present." );
    }
}

function submitImage( url )
{
    $.post(
        "php/enter.php",
        {
            action: "submitBookImage",
            id:     $('#id').val(),
            url:    url
        },
        submitCallback
    );
}


/********************DOWNLOAD********************/


function view()
{
    showBinaryChoice(
        "Download",
        "Download Ratings or View To-Watch List?", "Download All", "View Searches",
        function( answer ) {
            if ( answer )
            {
                download();
            }
            else
            {
                viewSearches();
            }
        }
    );
}

function viewSearches()
{
    $.post(
        "php/enter.php",
        {action: "viewSearches"},
        function( response ) {
            showMessage( "User Searches", JSON.parse( response ) );
        }
    );
}

function download()
{
    var downloadForm = document.createElement('FORM');
    downloadForm.name='downloadForm';
    downloadForm.method='POST';
    downloadForm.action='php/enter.php';

    var formAction = document.createElement('INPUT');
    formAction.type='HIDDEN';
    formAction.name='action';
    formAction.value='downloadAll';
    downloadForm.appendChild( formAction );

    document.body.appendChild( downloadForm );
    downloadForm.submit();
}