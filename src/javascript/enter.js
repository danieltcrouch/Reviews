function autofillTab( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 9 )
    {
        autofill( e );
    }
}

function autofill( e )
{
    var charCode = (typeof e.which === "number") ? e.which : e.keyCode;
    if ( charCode === 13 || charCode === 9 )
    {
        var search = $('#title').val();
        if ( search )
        {
            if ( search.search( /tt\d{7}/i ) === -1 )
            {
                $.post(
                    "php/addRatings.php",
                    {
                        action: "getMovieData",
                        title: search
                    },
                    autofillCallback
                );
            }
            else
            {
                autofillById( search );
            }
        }
        else
        {
            clear();
        }
    }
}

function autofillCallback( response )
{
    var movieResponse = JSON.parse( response );
    if ( movieResponse.search )
    {
        if ( movieResponse.isSuccess )
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
                    var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + movieResponse.search + "'>Google</a><br/>Then enter ID:";
                    showPrompt( "Enter ID", html, autofillById, "tt0082971", true );
                }
            });
        }
        else
        {
            var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + movieResponse.search + "'>Google</a><br/>Then enter ID:";
            showPrompt( "Enter ID", html, autofillById, "tt0082971", true );
        }
    }
}

function autofillById( id )
{
    if ( id )
    {
        $.post(
            "php/addRatings.php",
            {
                action: "getMovieDataById",
                id: id
            },
            autofillByIdCallback
        );
    }
}

function autofillByIdCallback( response )
{
    response = JSON.parse( response );
    if ( response.isSuccess )
    {
        fillData( response );
    }
}

function fillData( movie )
{
    clear();
    $('#title').val( movie.title );
    $('#year').val( movie.year );
    $('#id').val( movie.id );
    $('#poster').val( movie.poster );

    loadCallback( $('#id').val() );
}

function checkSubmit()
{
    if ( $('#id').val() )
    {
        showBinaryChoice( "Submit Movie", "Submit to Movie Review List or Ranked List?", "Movies", "Rankings", function( answer ) {
            if ( answer )
            {
                saveMovie();
            }
            else
            {
                saveRankedMovie();
            }
        } );
    }
    else
    {
        showToaster( "No movie ID present." );
    }
}

function saveMovie()
{
    $.post(
        "php/addRatings.php",
        {
            action: "checkOverwrite",
            id: $('#id').val()
        },
        checkOverwriteCallback
    );
}

function checkOverwriteCallback( response )
{
    response = JSON.parse( response );
    if ( response && response.isSuccess )
    {
        submit();
    }
    else if ( response && response.message === "Duplicate" )
    {
        showConfirm( "Entry Exists", "This movie has already been rated. Overwrite?", function( answer ) {
            if ( answer )
            {
                submit( true );
            }
        });
    }
    else
    {
        showToaster( response.message || "An error has occurred." );
    }
}

function submit( overwrite )
{
    $.post(
        "php/addRatings.php",
        {
            action: "saveMovie",
            id:     $('#id').val(),
            title:  $('#title').val(),
            year:   $('#year').val(),
            index:  $('#index').val(),
            rating: $('#rating').val(),
            review: $('#review').val() || "***",
            overwrite: overwrite
        },
        submitCallback
    );
}

function submitCallback()
{
    clear();
    showToaster( "Success!" );
}

function clear()
{
    $('#title').val( "" );
    $('#year').val( "" );
    $('#index').val( "" );
    $('#rating').val( "" );
    $('#review').val( "" );
    $('#id').val( "" );
}

// function load()
// {
//     showPrompt( "Load Movie", "Enter a previously rated movie:", loadCallback, "Raiders of the Lost Ark | tt0082971" );
// }

function loadCallback( response )
{
    if ( response )
    {
        $.post(
            "php/addRatings.php",
            {
                action: "load",
                movie: response
            },
            loadMovie
        );
    }
}

function loadMovie( response )
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
    }
    else
    {
        showToaster( "Movie not previously reviewed." );
    }
}

function remove()
{
    showPrompt( "Delete Movie", "Enter a previously rated movie:", removeCallback, "Raiders of the Lost Ark | tt0082971" );
}

function removeCallback( response )
{
    if ( response )
    {
        if ( response.search( /tt\d{7}/i ) === -1 )
        {
            $.post(
                "php/addRatings.php",
                {
                    action: "getMovieData",
                    title: response
                },
                confirmRemoveMovie
            );
        }
        else
        {
            removeMovie( response );
        }
    }
}

function confirmRemoveMovie( response )
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
                removeMovie( movieResponse.id );
            }
            else
            {
                var html = "Try finding the ID here: <a class='link' href='https://www.google.com/search?q=IMDB%20" + movieResponse.search + "'>Google</a><br/>Then enter ID:";
                showPrompt( "Enter ID", html, removeMovie, "tt0082971", true );
            }
        });
    }
    else
    {
        showToaster( "Movie not found." );
    }
}

function removeMovie( id )
{
    $.post(
        "php/addRatings.php",
        {
            action: "remove",
            id:     id
        },
        function( response ) {
            response = JSON.parse( response );
            showToaster( ( response && response.isSuccess ) ? "Movie removed." : "Movie not found." );
        }
    );
}

function download()
{
    showBinaryChoice(
        "Download",
        "Download Ratings or View To-Watch List?", "Download", "To-Watch",
        function( answer ) {
            if ( answer )
            {
                $.post(
                    "php/addRatings.php",
                    {action: "download"},
                    downloadCallback
                );
            }
            else
            {
                $.post(
                    "php/addRatings.php",
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