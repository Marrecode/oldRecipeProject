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
    if ($_GET['id'] == '') {
        echo 'inget id';
    } else {
        // Hämta recept baserat på id i URL.
        $q = $db->prepare("SELECT rowid,* FROM Recipes WHERE rowid=:id");
        $q->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
        $ret = $q->execute();
        $recipe = $ret->fetchArray();

        // Hämta ingredienserna.
        $q = $db->prepare("SELECT LOWER(Ingredient), Amount FROM RecipesIngredients WHERE RecipeID=:id");
        $q->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
        $ingredients = $q->execute();

        // Ta fram 3 liknande recept genom att göra en sökning på nuvarande
        // recepts ingredienser.
        while($i = $ingredients->fetchArray()) {
            $ing_array[] = $i[0];
            $amounts[] = $i[1];
        }
        $ing_string = "'" . implode("','", $ing_array) . "'";

        $sql = <<<SQL
        SELECT Recipes.rowid, Recipes.*, COUNT(*) AS Count FROM Recipes
        JOIN RecipesIngredients ON Recipes.rowid = RecipesIngredients.RecipeID
        WHERE LOWER(RecipesIngredients.Ingredient) IN ({$ing_string})
        AND Recipes.rowid <> {$recipe['rowid']}
        GROUP BY Recipes.rowid
        ORDER BY Count DESC, Recipes.Rating DESC
        LIMIT 3
SQL;
        $similar = $db->query($sql);
    }
?>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?= $recipe['Name'] ?></title>
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
                    <input type="text" id="sokruta" name="sok" placeholder="Sök receptnamn" onkeydown="if (event.keyCode == 13) document.getElementById('sokknapp').click()"/>
                    <button type="button" id="sokknapp" onclick="textSearch()">Hitta</button>
                </div>

                <div id="recipepage">
                    
                    <div class="recipebox" id="recipeheader">
                        <div>
						<h1 class="title"><?= $recipe['Name'] ?></h1>
						</div>
							<div>
						<div id="betygbox">
						<a href="betyg.php?a=<?php echo $_GET['id'];?>&b=1"><img class="betygikon" src="bilder/thumbsup.png" alt="Betyg Upp"></a>
						<a href="betyg.php?a=<?php echo $_GET['id'];?>&b=-1"><img class="betygikon" src="bilder/thumbsdown.png" alt="Betyg Ner"></a>
						<span id="betygval" class=<?= ($recipe['Rating'] >= 0) ? "green" : "red"?>><?= $recipe['Rating'] ?></span>
						</div>
						</div>

                        <div class="recipepic" style="background-image: url('bilder/<?php echo $recipe['Picture']; ?>'), url('bilder/no_image.jpg')"></div>
                       
						
						
                           
                        <div class="recipedescription">
                            <p class="r_description"><?= $recipe['Description'] ?></p>
                            <p class="">2 portioner</p>

                        </div>

                    </div>

                    <hr class="side">  

                    <div class="recipecontent">
                        <div class="ingridients">
                            <h2 class="">Ingredienser</h2>
                                <ul class="ingridient-list">
                                    <?php // Ingredienslistan
                                    foreach($ing_array as $key => $i) { ?>
                                    <li><?= $amounts[$key] ."<b>". ' ' . $i."</b>" ?></li>
                                    <?php } ?>
                                </ul>
                        </div>
                        <div class="instructions">
                            <h2>Gör såhär</h2>
                            <?= $recipe['Instructions'] ?>
                        </div>
                    </div><!--end recipecontent-->

                </div><!-- end recipepage-->

                    <div id="related_recipe">
                        <h2>Liknande recept</h2>
                        <ul class="teaser">
                            <?php
                            while($i = $similar->fetchArray()) { ?>
                            <li>
                                <a href="recipe.php?id=<?= $i['rowid'] ?>" title="<?= $i['Name'] ?>">
									<div class="relatedImage"  style="background-image: url('bilder/<?php echo $i['Picture']; ?>'), url('bilder/no_image.jpg')"></div>
                                    <figcaption><?= $i['Name'] ?></figcaption>
									
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div><!--end related_recipe-->
            </div>
            <footer>
                <p>Hannes Birgersson, Martin Gustavsson, Johan Stubbergaard, Maria Nguyen, Jenny Vuong</p>
            </footer>
        
        </div>
    </body>
</html>