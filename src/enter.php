<?php include("$_SERVER[DOCUMENT_ROOT]/php/startup.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>New Ratings</title>
    <?php includeHeadInfo(); ?>
    <script src="javascript/enter.js"></script>
    <script src="javascript/compare.js"></script>
</head>

<body>

	<!--Header-->
    <?php includeHeader(); ?>
    <div class="col-10 header">
        <div class="title center">New Ratings</div>
    </div>

    <!--Main-->
    <div class="main">
        <div class="col-10 center" style="padding-bottom: 0">
            <div class="center" style="margin-bottom: 1em">
                <button id="movie" name="mediaType" class="button selectedButton" style="width: 5em; margin: .25em;">Movies</button>
                <button id="book"  name="mediaType" class="button inverseButton" style="width: 5em; margin: .25em;">Books</button>
            </div>
            <input id="title" type="search" class="input" onkeydown="autoFillTab( event )" onkeyup="autoFill( event )" placeholder="Title (or ID)">
        </div>
        <div id="movieInputs">
            <div class="col-3r center" style="padding-bottom: 0">
                <input id="year" type="number" class="input" placeholder="Year">
            </div>
            <div class="col-3r center" style="padding-bottom: 0">
                <input id="rating"  type="number" class="input"                       placeholder="Rating">
                <input id="list"    type="text"   class="input" style="display: none" placeholder="Franchise" disabled>
            </div>
            <div class="col-3r center" style="padding-bottom: 0">
                <input id="index" type="number" class="input" placeholder="Index" autocomplete="do-nothing">
            </div>
        </div>
        <div class="col-10 center">
            <div><textarea id="review" class="input" placeholder="Review"></textarea></div>
            <div id="movieTypeButtons" class="center" style="margin-bottom: 1em">
                <button id="full" name="movieType" class="button selectedButton" style="width: 5em; margin: .25em;">Listed</button>
                <button id="rank" name="movieType" class="button inverseButton" style="width: 5em; margin: .25em;">Ranked</button>
            </div>
            <div><button id="submit"   class="button" style="width: 10em; margin-bottom: 1em" onclick="submit()">Submit</button></div>
            <div><button id="delete"   class="button" style="width: 10em; margin-bottom: 1em" onclick="checkDelete()">Delete</button></div>
            <div><button id="addImage" class="button" style="width: 10em; margin-bottom: 1em" onclick="addImage()">Add Image</button></div>
            <div><button id="download" class="button" style="width: 10em; margin-bottom: 1em" onclick="view()">View</button></div>
            <input id="id" type="hidden" value="">
            <input id="image" type="hidden" value="">
        </div>
    </div>

</body>

<script>
    setRadioCallback( "mediaType", function( mediaType ) {
        setMediaType( mediaType );
    });
    setRadioCallback( "movieType", function( movieType ) {
        setMovieType( movieType );
    });

    setMediaType( getSelectedRadioButton( "mediaType" ).id );
    setMovieType( getSelectedRadioButton( "movieType" ).id );
</script>

<?php include("html/list-modal.html"); ?>
<?php include("html/sort-modal.html"); ?>
<?php includeModals(); ?>
</html>