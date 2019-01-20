<?php include("$_SERVER[DOCUMENT_ROOT]/php/startup.php"); ?>
<!DOCTYPE html>
<html>
<head>
	<title>New Ratings</title>
    <?php includeHeadInfo(); ?>
    <script src="javascript/enterMovie.js"></script>
    <script src="javascript/enterBook.js"></script>
</head>

<body>

	<!--Header-->
    <?php includeHeader(); ?>
    <div class="col-10 header">
        <div class="title center">New Ratings</div>
    </div>

<!--
TODO

SUBMIT BUTTON
[MOVIE]
    (Remains the same mostly)
    (Delete Button allows remove movie in ranking)
[BOOK]
    (Check book list to determine add or update)
        (Link to GoodReads to Update)   -> https://www.goodreads.com/review/edit/30259180
        (Link to GoodReads to Add)      -> https://www.goodreads.com/review/list/55277264-daniel-crouch?utf8=%E2%9C%93&search%5Bquery%5D=tech-wise+family
    (Type review and add to clipboard; toaster to remind) -> https://www.w3schools.com/howto/howto_js_copy_clipboard.asp

DELETE BUTTON
    (Use title and ID from inputs rather than prompt)

ADD IMAGE BUTTON
    (Check book ID and title match or retrieve ID from title as with Submit)
    (If review is image URL, use it, else prompt for image URL)
        (Always overwrite if existing)

VIEW BUTTON
    Download All -> https://www.allphptricks.com/create-a-zip-file-using-php-and-download-multiple-files/
    View Searches

(Archive List (5x), each Ranking, books, book-favorites, watch, read)
    No longer need resources/

-->

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
                <input id="rating" type="number" class="input" placeholder="Rating">
            </div>
            <div class="col-3r center" style="padding-bottom: 0">
                <input id="index" type="number" class="input" placeholder="Index">
            </div>
        </div>
        <div class="col-10 center">
            <div><textarea id="review" class="input" placeholder="Review"></textarea></div>
            <div id="movieTypeButtons" class="center" style="margin-bottom: 1em">
                <button id="list" name="movieType" class="button selectedButton" style="width: 5em; margin: .25em;">Listed</button>
                <button id="rank" name="movieType" class="button inverseButton" style="width: 5em; margin: .25em;">Ranked</button>
            </div>
            <div><input id="submit" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="checkSubmit()" value="Submit"></div>
            <div><input id="delete" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="remove()" value="Delete"></div>
            <div><input id="addImage" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="alert()" value="Add Image"></div>
            <div><input id="download" type="button" class="button" style="width: 10em; margin-bottom: 1em" onclick="download()" value="View"></div>
            <input id="id" type="hidden" value="">
            <input id="poster" type="hidden" value="">
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
<?php includeModals(); ?>
</html>