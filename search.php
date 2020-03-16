<!DOCTYPE html>
<?php
    /* Ansvarig:
     * Johan Stubbergaard
     */
    include('db.php');

    $db = new DB();
    if(!$db){
        echo $db->lastErrorMsg();
        exit();
    }

    // Om inget sidnummer anges i URLen, sätt $page till 1
    $page = $_GET['p'] ?: 1;
    $offset = $page * 10 - 10;

    // Om 'r' har ett värde, gör en namnsökning på det, annars sök på ingredienser i 's'.
    if ($_GET['r']) {
        $search_mode = 'r';

        // Sökning
        $sql = <<<SQL
        Select rowid, * FROM Recipes
        WHERE UPPER(Name) LIKE UPPER(:search)
        ORDER BY Rating DESC
        LIMIT 10 OFFSET :offset
SQL;
        $q = $db->prepare($sql);
        $q->bindValue(':search', '%'.$_GET['r'].'%', SQLITE3_TEXT);
        $q->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $results = $q->execute();
        
        // Antal träffar
        $q = $db->prepare("SELECT COUNT(*) FROM Recipes WHERE UPPER(Name) LIKE UPPER(:search)");
        $q->bindValue(':search', "%" . $_GET['r'] . "%", SQLITE3_TEXT);
        $hits = $q->execute()->fetchArray()[0];
    } else if ($_GET['s']) {
        $search_mode = 's';
        // Bygg sträng i format 'foo','bar','apa' av ingredienserna i URLen (escapad).
        $ing_array = explode(',', $db->escapeString($_GET['s']));
        $ingredients = "'" . mb_strtolower(implode("','", $ing_array), 'UTF-8') . "'";

        // Typ av rätt. 1 = förrätt, 2 = huvudrätt, 3 = efterrätt.
        if (($_GET['c'] == '') || ($_GET['c'] == 0)) {
            $course = "> 0";
        } else {
            $course = "= " . $db->escapeString($_GET['c']);
        }

        // Själva sökningen.
        $sql = <<<SQL
        SELECT Recipes.rowid, Recipes.*, COUNT(*) AS Count FROM Recipes
        JOIN RecipesIngredients ON Recipes.rowid = RecipesIngredients.RecipeID
        WHERE LOWER(RecipesIngredients.Ingredient) IN ({$ingredients})
        AND Course {$course}
        GROUP BY Recipes.rowid
        ORDER BY Count DESC, Recipes.Rating DESC
        LIMIT 10 OFFSET {$offset}
SQL;
        $results = $db->query($sql);

        // Hämta totalt antal träffar.
        $sql = <<<SQL
        SELECT COUNT(*) AS Count FROM (
        SELECT Recipes.rowid, Recipes.*, COUNT(*) AS Count FROM Recipes
        JOIN RecipesIngredients ON Recipes.rowid = RecipesIngredients.RecipeID
        WHERE LOWER(RecipesIngredients.Ingredient) IN ({$ingredients})
        AND Course {$course}
        GROUP BY Recipes.rowid)
SQL;
        $hits = $db->query($sql)->fetchArray()['Count'];
    } else {
        // Tom eller felaktig söksträng i URL.
        $search_mode = 'n';
    }

    // Ta fram navigeringslänkarna längst ner.
    $nextpage = sprintf("search.php?%s=%s&p=%d", $search_mode, $_GET[$search_mode], $page + 1);
    $prevpage = sprintf("search.php?%s=%s&p=%d", $search_mode, $_GET[$search_mode], $page - 1);

    foreach(range(1, ceil($hits/10) ?: 1) as $i) {
        if ($i == $page) {
            $navlinks[] = sprintf('<span id="currentpage">%d</span>', $i);
        } else {
            $navlinks[] = sprintf('<a href="search.php?%s=%s&p=%d">%d</a>', $search_mode, $_GET[$search_mode], $i, $i);
        }
    }
    if ($hits > 0) {
        $resultnav = implode(' - ', $navlinks);
    } else {
        $resultnav = '';
    }
?>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Välj recept</title>
        <link rel="stylesheet" href="style.css">
		<script type="text/javascript" src="Script.js" ></script>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
       <div id="wrapper">
            <a href="index.html"><header>
                <h1>Receptsökare!</h1>
            </header></a>
            <div id="main">
                <div id="sok">
                    <input type="text" id="sokruta" name="sok" placeholder="Sök receptnamn" onkeydown="if (event.keyCode == 13) document.getElementById('sokknapp').click()" <?= ($search_mode == 'r' ? 'value=' . $_GET['r'] : '') ?> />
                    <button type="button" id="sokknapp" onclick="textSearch()">Hitta</button>
                </div>
                <div id="resultatarea">
                    <div id="hits">
                        <?php
                        if ($search_mode != 'n') {
                            echo $hits . ' träffar.';
                        } else {
                            echo 'Ingen söksträng. Hur tänkte du där?';
                        } ?>
                    </div>
                    <?php
                    while ($row = $results->fetchArray()) {
                        extract($row); // Alla element i $row blir egna variabler; motsvarar kolumnnamnen i db:n.
                        $q = $db->prepare("SELECT Ingredient FROM RecipesIngredients WHERE RecipeID = :id");
                        $q->bindValue(':id', $rowid);
                        $ing = $q->execute();
                        ?>
                        <a href="recipe.php?id=<?=$rowid?>">
                        <div class="resultbox">
                            <div class="bildbox" style="background-image: url('bilder/<?=$Picture?>'), url('bilder/no_image.jpg')"></div>
                            <div class="receptrubrik"><?= $Name ?></div>
                            <div class="recepttext"><?= $Description ?></div>
                            <div class="ingrlabel">Ingredienser:</div>
                            <div class="ratinglbl"> Betyg: <?= $Rating ?></div>
                            <div class="resultingrdbox">
                                <ul>
                                    <?php
                                    // Loopa genom ingredienserna i receptet och lägg i listan.
                                    while ($i = $ing->fetchArray()) { ?>
                                        <li class="resultingrd"><?= $i[0] ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                        </a>
                    <?php } ?>
                    <div id="pagination">
						<div id="previous">
							<a href="<?=$prevpage?>" <?= ($page <= 1 ? 'class="hidden"' : '') ?>>Föregående sida</a>
						</div>
						<div id="resultnav">
							<?=$resultnav?>
						</div>
						<div id="next">
							<a href="<?=$nextpage?>" <?= ($hits <= $page * 10 ? 'class="hidden"' : '') ?>>Nästa sida</a>
						</div>
					</div>
                </div>
                <div id="ingredienser" <?= ($search_mode != 's' ? 'class="hidden"' : '') ?>>
                    <p>Dina valda ingredienser:</p>
                    <ul>
                        <?php
                        // Befolka listan över valda ingredienser.
                        foreach($ing_array as $i) { ?>
                        <li><?= $i ?></li>
                        <?php } ?>
                    </ul>
                    <FORM ACTION="index.html">
                    <INPUT TYPE="submit" class="button bigbutton" id="redobutton" value="Ny sökning">
                    </FORM>
                </div>
            </div>
            <footer>
                <p>Hannes Birgersson, Martin Gustavsson, Johan Stubbengaard, Maria Nguyen, Jenny Vuong</p>
            </footer>
        </div>
    </body>
</html>
