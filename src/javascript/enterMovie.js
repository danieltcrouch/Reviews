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
    }
    else
    {
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
            if ( search.search(/tt\d{7}/i) >= 0 )
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
    $.post(
        "php/enter.php",
        {
            action: "getMovieDataById",
            id: id
        },
        autoFillByIdCallback
    );
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
            action: "getMovieData",
            title: title
        },
        autoFillByTitleCallback
    );
}

function autoFillByTitleCallback( response )
{
    var movieResponse = JSON.parse( response );
    if ( movieResponse && movieResponse.isSuccess )
    {
        var innerHTML = "Is this the correct movie?<br/><br/>" +
                        "<strong>" + movieResponse.title + "</strong> (" + movieResponse.year + ")<br/>" +
                        "(" + movieResponse.id + ")<br/><br/>" +
                        "<img src='" + movieResponse.poster + "' height='300px' alt='Movie Poster'>";
        showConfirm( "Movie Match", innerHTML, function( answer ) {
            if ( answer )
            {
                fillData( movieResponse );
            }
            else
            {
                promptGoogle( movieResponse.search );
            }
        });
    }
    else
    {
        promptGoogle( movieResponse.search );
    }
}

function promptGoogle( search )
{
    var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + search + "'>Google</a><br/>Then enter ID:";
    showPrompt( "Enter ID", html, autoFillById, "tt0082971", true );
}

function fillData( movie )
{
    clear();
    $('#title').val( movie.title );
    $('#year').val( movie.year );
    $('#id').val( movie.id );
    $('#poster').val( movie.poster );

    loadFromFile( movie.id );
}

function loadFromFile( id )
{
    $.post(
        "php/enter.php",
        {
            action: isFullList() ? "loadFromFullFile" : "loadFromRankFile",
            id:     id
        },
        loadFromFileCallback
    );
}

function loadFromFileCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        $('#title').val( response.title );
        $('#year').val( response.year );
        $('#index').val( response.index );
        $('#rating').val( response.rating );
        $('#review').val( response.review );
        $('#id').val( response.id );

        if ( !isFullList() )
        {
            $('#list').val( response.list );
            $('#poster').val( response.image );
        }
    }
    else
    {
        var term = isFullList() ? "rated" : "ranked";
        showToaster( "Movie not previously " + term + "." );
    }
    isOverwrite = response.isSuccess;
}

function clear()
{
    $('#title').val( "" );
    $('#year').val( "" );
    $('#index').val( "" );
    $('#rating').val( "" );
    $('#review').val( "" );
    $('#id').val( "" );
    isOverwrite = null;
}


/*********************IMAGE**********************/


function addImage()
{

}


/*********************SUBMIT*********************/


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
                    isFullList() ? submit() : getList();
                }
            });
        }
        else
        {
            isFullList() ? submit() : getList();
        }
    }
    else
    {
        showToaster( "No movie ID present." );
    }
}

function submit()
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
            isOverwrite ? getRanking() : submitRank( $('#index').val() );
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
                rank: answer
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
            image:  $('#poster').val(),
            review: $('#review').val() || "***",
            overwrite: isOverwrite
        },
        submitCallback
    );
}


/*********************DELETE********************/


function checkDelete()
{
    var innerHTML = "Are you sure you would like to delete this movie?<br/><br/>" +
                    "<strong>" + $('#title').val() + "</strong> (" + $('#year').val() + ")<br/>" +
                    "(" + $('#id').val() + ")<br/><br/>" +
                    "<img src='" + $('#poster').val() + "' height='300px' alt='Movie Poster'>";
    showConfirm( "Delete Movie", innerHTML, function( answer ) {
        if ( answer )
        {
            deleteMovie();
        }
    });
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
            showToaster( ( response && response.isSuccess ) ? "Movie removed." : "Movie not found." );
        }
    );
}


/********************DOWNLOAD********************/


function download()
{
    showBinaryChoice(
        "Download",
        "Download Ratings or View To-Watch List?", "Download All", "View Searches",
        function( answer ) {
            if ( answer )
            {
                $.post(
                    "php/enter.php",
                    {action: "download"},
                    downloadCallback
                );
            }
            else
            {
                $.post(
                    "php/enter.php",
                    {action: "viewToWatch"},
                    function( response ) {
                        showMessage( "Movies To-Watch", JSON.parse( response ).text );
                    }
                );
            }
        }
    );
}

function downloadCallback( response )
{
    var text = JSON.parse( response ).text;
    var url = null;
    var blob = new Blob( [text], {type: 'text/csv'} );
    if ( url !== null )
    {
        window.URL.revokeObjectURL( url );
    }
    url = window.URL.createObjectURL( blob );

    var a = document.createElement( "a" );
    document.body.appendChild( a );
    a.href = url;
    a.style = "display: none";
    a.download = "Ratings.csv";
    a.click();
    window.URL.revokeObjectURL( url );
}