<?php

function getAverageRanking( $type )
{
	$mysqli = getMySql();

	$rankings = array();
	$result = $mysqli->query( "SELECT id FROM averageRankings WHERE type = '$type' ORDER BY rank, count DESC " );
	if ( $result && $result->num_rows > 0 )
	{
        while( $row = $result->fetch_array() )
        {
            array_push( $rankings, $row['id'] );
        }
	}

    return $rankings;
}

function saveRanking( $type, $rankings )
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

    $tableName = strtolower( $type ) . "Rankings";
	$result = $mysqli->query( "INSERT INTO $tableName ( id, rank ) VALUES $rankSql " );
    return $result;
}

function getMySql()
{
    return new mysqli( 'localhost', 'religiv3_admin', '1corinthians3:9', 'religiv3_reviews' );
}

if ( isset( $_POST['action'] ) && function_exists( $_POST['action'] ) )
{
	$action = $_POST['action'];
    $result = null;

	if ( isset( $_POST['type'] ) && isset( $_POST['rankings'] ) )
	{
		$result = $action( $_POST['type'], $_POST['rankings'] );
	}
	elseif ( isset( $_POST['type'] ) )
	{
		$result = $action( $_POST['type'] );
	}
	else
	{
		$result = $action();
	}

	echo json_encode( $result );
}

?>