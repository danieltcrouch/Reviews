var averageList = [];
var myListTitle = [];
var myListDetail = [];

var listType;

//Franchise specific because of DB and setMyList functionality
// Refactor this method if that is changed
function compareFranchiseRankings( type )
{
    listType = type;
    setMyList( type );
    setAverageList( type );
    openSortModal( myListDetail, scoreRankings, true );
}

function setMyList( type )
{
    myListDetail = getFranchiseFromId( type );
    myListTitle = myListDetail.map( movie => movie.title );
}

function setAverageList( type )
{
    $.post(
        "php/database.php",
        {
            action:    "getAverageRanking",
            type:      type
        },
        setAverageListCallback
    );
}

function setAverageListCallback( list )
{
    list = JSON.parse( list );
    if ( list && list.length > 0 )
    {
        averageList = list.map( id => myListDetail.find( movie => { return movie.id === id } ) );
    }
    else
    {
        averageList = myListDetail;
    }
}

function scoreRankings( answers, isText )
{
    var score = getScore( answers );
    var avgScore = getAverageScore( answers );
    var results = isText ? getTextResultDisplay( answers ) : getResultDisplay( answers );
    displayResults( score, avgScore, results );
}

function getScore( answers )
{
    saveRanking( answers );
    return getRankScore( myListTitle, answers );
}

function getAverageScore( answers )
{
    return getRankScore( averageList.map( movie => movie.title ), answers );
}

//Uses SPEARMAN'S CORRELATION
function getRankScore( originalList, newList )
{
    var sum = 0;
    var count = originalList.length;

    for ( var i = 0; i < count; i++ )
    {
        var diff = 0;
        for ( var j = 0; j < newList.length; j++ )
        {
            if ( originalList[i] === newList[j] )
            {
                diff = i - j;
                break;
            }
        }
        sum += Math.pow( diff, 2 );
    }

    var score = 1 - ( 6 * sum ) / ( count * ( Math.pow( count, 2 ) - 1 ) );
    score = ( score + 1 ) * 50;
    var roundedScore = score.toPrecision( 4 );
    score = ( score < 100 && roundedScore == 100 ) ? 99.99 : ( score < 100 ) ? roundedScore : 100;

    return score;
}

function saveRanking( answers )
{
    var ids = [];
    for ( var i = 0; i < answers.length; i++ )
    {
        ids.push( myListDetail[ myListTitle.indexOf( answers[i] ) ].id );
    }

    $.post(
        "php/database.php",
        {
            action:    "saveRanking",
            type:      listType,
            rankings:  ids
        }
    );
}

function getTextResultDisplay( answers )
{
    var result = "<span style='font-weight: bold'>Your Rankings:</span><br/>";
    result += answers.join("<br/>");
    result += "<br/><br/>";
    result += "<span style='font-weight: bold'>My Rankings:</span><br/>";
    result += myListTitle.join("<br/>");
    result += "<br/><br/>";
    result += "<span style='font-weight: bold'>Average Rankings:</span><br/>";
    result += averageList.join("<br/>");
    return result;
}

function getResultDisplay( answers )
{
    var result = "<div style='display: flex; width: 18em; margin: auto'>" +
                 "  <span style='flex: 1; align-content: center; font-weight: bold'>You</span>" +
                 "  <span style='flex: 1; align-content: center; font-weight: bold'>Me</span>" +
                 "  <span style='flex: 1; align-content: center; font-weight: bold'>Average</span>" +
                 "</div>";

    var yList = answers.map( title => myListDetail.find( movie => { return movie.title === title } ) );
    var mList = myListDetail;
    var aList = averageList;

    for ( var i = 0; i < yList.length; i++ )
    {
        var yTitle = yList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        var mTitle = mList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        var aTitle = aList[i].title.replace(/'/g, "&apos;").replace(/"/g, "&quot;");
        result += "<div>" +
                  " <img style='width: 5em; margin-right: 1em' src='" + yList[i].image + "' title='" + yTitle + "' alt='" + yTitle + "'>" +
                  " <img style='width: 5em; margin-right: 1em' src='" + mList[i].image + "' title='" + mTitle + "' alt='" + mTitle + "'>" +
                  " <img style='width: 5em'                    src='" + aList[i].image + "' title='" + aTitle + "' alt='" + aTitle + "'>" +
                  "</div>";
    }

    return result;
}

function displayResults( score, avgScore, resultDisplay )
{
    showMessage( "Comparison Results", "Our rankings match: <span style='font-weight: bold'>" + score + "%</span><br/>\n" +
                                       "Your rankings match the average: <span style='font-weight: bold'>" + avgScore + "%</span><br/>\n" +
                                       "<br/>\n" +
                                       resultDisplay );
}