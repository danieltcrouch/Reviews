var relevantList = [];

function compareRankings( type )
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

    var placeholder = relevantList.join('\n');
    showBigPrompt( "Compare Rankings", "Enter your ranking by rearranging the series below:", scoreRankings, placeholder );
}

function scoreRankings( answer )
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

    showConfirm( "Comparison Results", "If 0% is the opposite and 100% a total match, our rankings match:" +
                                       "<div class='center' style='font-size: 1.5em'>" + score + "%</div>\n" +
                                        answers.join("<br/>") );
}