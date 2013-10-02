<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Movie Rater</title>
	<link rel="stylesheet" href="../lib/jqueryui/css/pepper-grinder/jquery-ui-1.8.20.custom.css">
	<script src="../lib/jqueryui/js/jquery-1.7.2.min.js"></script>
	<script src="../lib/jqueryui/js/jquery-ui-1.8.20.custom.min.js"></script>
	<script type="text/javascript">
	$(function() {
		$( "#accordion" ).accordion();
		$( "#datepicker" ).datepicker();
                $( "#datepicker" ).datepicker( "option", "dateFormat", 'yy-mm-dd' );
	});
	</script>
</head>
<body>

<form action="?" method="post">

<?php
$date=$_POST["date"];
$channel=$_POST["channel"];

echo "<input id=\"datepicker\" name=\"date\" type=\"text\">";

echo "<select id=\"channel\" name=\"channel\">";
echo "  <option value=\"SHOW\">Showtime</option>";
echo "  <option value=\"SHO2\">Showtime 2</option>";
echo "  <option value=\"SHOCSE\">Showtime Showcase</option>";
echo "  <option value=\"SHOWX\">Showtime Extreme</option>";
echo "  <option value=\"SHOWB\">Showtime Beyond</option>";
echo "  <option value=\"TMC\">The Movie Channel</option>";
echo "  <option value=\"TMCX\">The Movie Channel Xtra</option>";
echo "  <option value=\"AMC\">American Movie Classics</option>";
echo "  <option value=\"Flix\">Flix</option>";
echo "</select>";

echo "<script type=\"text/javascript\">";
echo "$(function() {";
echo "  $(\"#channel\").val('$channel');";
if ($date != "") {
  echo "  $(\"#datepicker\").val('$date');";
} else {
  echo "  $( \"#datepicker\").datepicker('setDate', 'today');";
}

echo "});</script>";
?>

<input type="submit" value="Go" />
</form>

<?php

class Movie {
  var $name = '';
  var $rating = '';
  var $imdb_url = '';
  var $imdb_id = '';
  var $actors = '';
  var $genre = '';
  var $poster = '';
  var $plot = '';
  var $year = '';
}

function DOMInnerHTML($element) 
{ 
    $innerHTML = ""; 
    $children = $element->childNodes; 
    foreach ($children as $child) 
    { 
        $tmp_dom = new DOMDocument(); 
        $tmp_dom->appendChild($tmp_dom->importNode($child, true)); 
        $innerHTML.=trim($tmp_dom->saveHTML()); 
    } 
    return $innerHTML; 
} 

function getIMDBData($url) {
  preg_match("/\/title\/(.*)\//",$url,$match);
  $url = "http://www.imdbapi.com/?i=".$match[1]."&r=xml";
  $xmlStr = file_get_contents($url);
  $data = simplexml_load_string($xmlStr);
  return $data;
}

function getRating($movie) {
  $movie->rating = "";
  $movie->year = "";
  $movie->genre = "";
  $movie->actors = "";
  $movie->poster = "";
  $movie->plot = "";

  $link = mysql_connect('127.0.0.1', 'USER', 'PASSWORD');
  if (!$link) {
      die('Could not connect: ' . mysql_error());
  }

  $query = "select rating, year, genre, actors, poster, plot from movie_db.movies where imdb_id = '" . $movie->imdb_id . "'";
  $result = mysql_query($query);
  if (mysql_num_rows($result)!=0) {
    $movie->rating = mysql_result($result,0,"rating");
    $movie->year = mysql_result($result,0,"year"); 
    $movie->genre = mysql_result($result,0,"genre");
    $movie->actors = mysql_result($result,0,"actors");
    $movie->poster = mysql_result($result,0,"poster");
    $movie->plot = mysql_result($result,0,"plot");
  } else {
    $data = getIMDBData($movie->imdb_id);
    $movie->rating = $data->movie[imdbRating];
    $movie->year = $data->movie[year];
    $movie->genre = $data->movie[genre];
    $movie->actors = $data->movie[actors];
    $movie->poster = $data->movie[poster];
    $movie->plot = $data->movie[plot];
    $query = "insert into movie_db.movies (imdb_id, name, rating, year, genre, actors, poster, plot) values ('".mysql_real_escape_string($movie->imdb_id)."','".mysql_real_escape_string($movie->name)."','".$movie->rating."','".mysql_real_escape_string($movie->year)."','".mysql_real_escape_string($movie->genre)."','".mysql_real_escape_string($movie->actors)."','".mysql_real_escape_string($movie->poster)."','".mysql_real_escape_string($movie->plot)."')";
    mysql_query($query);
  }
  mysql_close($link);
}


if (isset($_POST["date"])) {
  $imdb_homepage = file_get_contents('http://www.imdb.com/tvgrid/'.$_POST["date"].'/'.$_POST["channel"].'/');
} else {
  date_default_timezone_set('America/Detroit');
  $date = date('yyyy-mm-dd');
  $imdb_homepage = file_get_contents('http://www.imdb.com/tvgrid/'.$date.'/SHOW/');
}

$dom = new DOMDocument();
@$dom->loadHTML($imdb_homepage);
$x = new DOMXPath($dom); 

echo "<div id=\"accordion\">";

foreach($x->query("//a") as $node) 
{
  if (preg_match("/\/title\//",$node->getAttribute("href"))) {
    $movie = new Movie();
    $movie->name = DOMInnerHTML($node);
    $movie->imdb_id = $node->getAttribute("href");
    $movie->imdb_url = "http://imdb.com" . $node->getAttribute("href");
    getRating($movie);

    echo "<h3><a href=\"#\">" . $movie->rating . "/10 -  " . $movie->name . "</a></h3>";
    echo "<div style=\"font-size:12px;\">";
    if ($movie->poster != 'N/A') {
      echo "<img src=\"". $movie->poster . "\" width=\"200\" /><br />";
    }
    echo $movie->rating . " - <a href=\"" . $movie->imdb_url . "\">" . $movie->name . "</a><br />";
    echo "Year: " . $movie->year . "<br />";
    echo "Actors: " . $movie->actors . "<br />";
    echo "Genre: " . $movie->genre . "<br />";
    echo "Plot: " . $movie->plot . "<br /><br />";
    echo "<a href=\"" . $movie->imdb_url . "\">More Info About " . $movie->name . "</a></div>";
  }
} 


?>
</body>
</html>
