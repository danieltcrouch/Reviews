var relevantList = [];

function compareRankings( type )
{
    setRelevantList( type );
    openCompareModal();
}

function setRelevantList( type )
{
    switch ( type )
    {
    case "Disney":
        relevantList = disneyList.map( movie => movie.title );
        break;
    case "Marvel":
        relevantList = marvelList.map( movie => movie.title );
        break;
    case "StarWars":
        relevantList = starWarsList.map( movie => movie.title );
    }
}

function openCompareModal()
{
    var modal = $('#compareModal');
    modal.show();
    setCloseHandlers( modal, null, scoreRankings );
    blurBackground();

    var listDiv = $('#modalList');
    listDiv.append( "<img width='1em' src='images/up.png'   alt='up'>" );
    listDiv.append( "<img width='1em' src='images/down.png' alt='down'>" );
}

function compareText()
{
    closeModal( $('#compareModal') );

    var placeholder = relevantList.join('\n');
    showBigPrompt( "Compare Rankings", "Enter your ranking by rearranging the series below:", scoreTextRankings, placeholder );
}

function scoreRankings()
{
    closeModal( $('#compareModal') );

    //todo - get their rankings

    displayResults();
}

function scoreTextRankings( answer )
{
    var sum = 0;
    var answers = answer.split('\n').map( movie => movie.trim() ).filter( Boolean );
    var count = relevantList.length;

    for ( var i = 0; i < count; i++ )
    {
        var diff = 0;
        for ( var j = 0; j < answers.length; j++ )
        {
            if ( relevantList[i] === answers[j] )
            {
                diff = i - j;
                break;
            }
        }
        sum += Math.pow( diff, 2 );
    }

    var score = 1 - ( 6 * sum ) / ( count * ( Math.pow( count, 2 ) - 1 ) );
    score = ( score + 1 ) * 50;
    score = ( score < 100 ) ? score.toPrecision( 4 ) : 100;

    displayResults( score, answers );
}

function displayResults( score, rankings )
{
    //todo - display side-by-side images with my rankings
    showConfirm( "Comparison Results", "If 0% is the opposite and 100% a total match, our rankings match:" +
                                       "<div class='center' style='font-size: 1.5em'>" + score + "%</div>\n" +
                                       rankings.join("<br/>") );
}