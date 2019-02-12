var enterMediaType;
var enterMovieType;
var isOverwrite;

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

function setMovieType( movieType )
{
    enterMovieType = movieType;
    autoFillById( $('#id').val() );
}

function isFullList()
{
    return enterMovieType === "full";
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
            var isImdbId = isMovie() && search.search(/tt\d{7}/i) >= 0;
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
                action: isMovie() ? ( isFullList() ? "getMovieById" : "getRankMovieById" ) : "getBookById",
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
            action: isMovie() ? ( isFullList() ? "getMovieByTitle" : "getRankMovieByTitle" ) : "getBookByTitle",
            title: title
        },
        autoFillByTitleCallback
    );
}

function autoFillByTitleCallback( response )
{
    response = JSON.parse( response );
    if ( response && response.isSuccess )
    {
        var title = isMovie() ? "Movie Match" : "Library Look-up";
        var term = isMovie() ? "movie" : "book";
        var info = isMovie() ? ("ID: " + response.id) : response.author;
        var imageAlt = isMovie() ? "Movie Poster" : "Book Cover";
        var innerHTML = "Is this the correct " + term + "?<br/><br/>" +
                        "<strong>" + response.title + "</strong> (" + response.year + ")<br/>" +
                        info + "<br/><br/>" +
                        "<img src='" + response.image + "' height='300px' alt='" + imageAlt + "'>";
        showConfirm( title, innerHTML, function( answer ) {
            if ( answer )
            {
                fillData( response );
            }
            else
            {
                promptGoogle( $('#title').val() );
            }
        });
    }
    else
    {
        promptGoogle( $('#title').val() );
    }
}

function promptGoogle( search )
{
    var db = isMovie() ? "IMDB" : "GoodReads";
    var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=" + db + "%20" + search + "'>Google</a><br/>Then enter ID:";
    showPrompt( "Enter ID", html, autoFillById, "tt0082971", true );
}

function fillData( response )
{
    clear();
    $('#title').val( response.title );
    $('#year').val( response.year );

    $('#rating').val( response.rating );
    $('#review').val( response.review );
    $('#index').val( response.index );

    $('#id').val( response.id );
    $('#list').val( response.list );
    $('#image').val( response.image );

    isOverwrite = response.isPreviouslyReviewed;
    if ( !isOverwrite )
    {
        var term = isMovie() ? "Movie" : "Book";
        showToaster( term + " not previously reviewed." );
    }
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

function checkSubmit()
{
    if ( $('#id').val() )
    {
        if ( isOverwrite )
        {
            var term = isFullList() ? "rated" : "ranked";
            showConfirm( "Entry Exists", "This movie has already been " + term + ". Overwrite?", function( answer ) {
                if ( answer )
                {
                    isFullList() ? submitMovie() : getList();
                }
            });
        }
        else
        {
            isFullList() ? submitMovie() : getList();
        }
    }
    else
    {
        showToaster( "No movie ID present." );
    }
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


function getList()
{
    if ( $('#list').val() )
    {
        getRanking();
    }
    else
    {
        showPrompt( "Enter List", "Enter the relevant list: &ldquo;Disney&rdquo; | &ldquo;Marvel&rdquo; | &ldquo;StarWars&rdquo; ", function( answer ) {
            $('#list').val( answer );
            ( isOverwrite || Number($('#index').val()) <= 0 ) ? getRanking() : submitRank( $('#index').val() );
        }, "", true );
    }
}

function getRanking()
{
    var innerHTML = "Where would you like to rank this movie?<br/>" +
                    "(e.g. 1, 2, 3, top, bottom, above [Movie], below [Movie])";
    showPrompt( "Where Does It Rank?", innerHTML, function( answer ) {
        $.post(
            "php/enter.php",
            {
                action: "validateRank",
                list: $('#list').val(),
                rank: answer,
                overwrite: isOverwrite
            },
            getRankingCallback
        );
    }, $('#index').val(), true );
}

function getRankingCallback( response )
{
    response = JSON.parse( response );
    if ( response && response.isSuccess )
    {
        submitRank( response.rank );
    }
    else
    {
        showToaster( response.message || "Invalid Ranking" );
    }
}

function submitRank( rank )
{
    $.post(
        "php/enter.php",
        {
            action: "saveRankedMovie",
            list:   $('#list').val(),
            rank:   rank,
            title:  $('#title').val(),
            id:     $('#id').val(),
            year:   $('#year').val(),
            image:  $('#image').val(),
            review: $('#review').val() || "***",
            overwrite: isOverwrite
        },
        submitCallback
    );
}


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
    $.post(
        "php/enter.php",
        {
            action: isFullList() ? "deleteMovie" : "deleteRankMovie",
            list:   isFullList() ? undefined : $('#list').val(),
            id:     $('#id').val()
        },
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
    //In the future, I may allow this button to be used for Rank Movies
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