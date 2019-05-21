<?php

function getAverageRanking( $list )
{
	$mysqli = getMySql();

	$rankings = array();
	$result = $mysqli->query( "SELECT id FROM averageRankings WHERE type = '$list' ORDER BY rank, count DESC " );
	if ( $result && $result->num_rows > 0 )
	{
        while( $row = $result->fetch_array() )
        {
            array_push( $rankings, $row['id'] );
        }
	}

    return $rankings;
}

function saveRanking( $list, $rankings )
{
	$mysqli = getMySql();

	$prefix  = "";
	$rankSql = "";
    for ( $i = 0; $i < count($rankings); $i++ )
    {
        $id = $rankings[$i];
        $rankSql .= $prefix . "(";
        $rankSql .= "'$id'" . "," . ($i+1);
        $rankSql .= ")";
        $prefix  = ",";
    }

    $tableName = getTableName( $list );
	$result = $mysqli->query( "INSERT INTO $tableName ( id, rank ) VALUES $rankSql " );
    return $result;
}

function updatePersonalRankings( $list, $movies )
{
    $mysqli = getMySql();

    $caseSql = "CASE ";
    for ($i = 0; $i < count($movies); $i++) {
        $id = $movies[$i]['id'];
        $rank = $i + 1;
        $caseSql .= "WHEN id = '$id' THEN '$rank'\n";
    }
    $caseSql .= "END ";

    $tableName = getTableName( $list );
    $result = $mysqli->query("UPDATE $tableName SET rank = ($caseSql) WHERE personal = '1' ");
    return $result;
}

function getTableName( $list )
{
    return strtolower( $list ) . "Rankings";
}

function getMySql()
{
    return new mysqli( 'localhost', 'religiv3_admin', '1corinthians3:9', 'religiv3_reviews' );
}

if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

	if ( isset( $_POST['list'] ) && isset( $_POST['rankings'] ) )
	{
		$result = $action( $_POST['list'], $_POST['rankings'] );
	}
    elseif ( isset( $_POST['list'] ) && isset( $_POST['movies'] ) )
   	{
   	    $result = $action( $_POST['list'], $_POST['movies'] );
   	}
	elseif ( isset( $_POST['list'] ) )
	{
		$result = $action( $_POST['list'] );
	}
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}

?>