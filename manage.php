<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<link rel="stylesheet" href="style.css">
		<style>
		 .table { padding:10px; width:900px; }
		 .left { float:left; width:450px; }
		 .right { float:right; width:450px; }
		 input { width:80%; margin: 3px; }
		 textarea { width:80%; height:100px; }
		.nyingrd { width: 175px;}
		</style>
		<script>
  	var i = 1;
  	function addrow(box, text1, text2) {
		/*Lägger till en till form input rad i diven box*/
  		i++;
  		var input = document.createElement("INPUT");

  		input.setAttribute("type", "text");
  		input.setAttribute("name", "ingrdfield1[]");
  		input.setAttribute("placeholder", text1);
  		input.className = "nyingrd";
  		document.getElementById(box).appendChild(input);

  		if (text2 == "Mängd") {
  			var t = document.createElement("INPUT");
  			t.setAttribute("type", "text");
  			t.setAttribute("name", "ingrdfield2[]");
  			t.setAttribute("placeholder", text2);
  			t.className = "nyingrd";
  			document.getElementById(box).appendChild(t);
  		} else {

  			var selector = document.createElement('select');
  			selector.className = 'nyingrd';
  			selector.name = 'ingrdfield2[]';
  			document.getElementById(box).appendChild(selector);

  			var option = document.createElement('option');
  			option.value = 'Kött';
  			option.appendChild(document.createTextNode('Kött & Fisk'));
  			selector.appendChild(option);

  			option = document.createElement('option');
  			option.value = 'Mejeri';
  			option.appendChild(document.createTextNode('Mejeri & Ägg'));
  			selector.appendChild(option);

  			option = document.createElement('option');
  			option.value = 'Pasta';
  			option.appendChild(document.createTextNode('Mjöl, Gryn & Pasta'));
  			selector.appendChild(option);

  			option = document.createElement('option');
  			option.value = 'Grönsak';
  			option.appendChild(document.createTextNode('Frukt & Grönt'));
  			selector.appendChild(option);

  			option = document.createElement('option');
  			option.value = 'Övrigt';
  			option.appendChild(document.createTextNode('Övrigt'));
  			selector.appendChild(option);
  		}
  	}
		</script>
	</head>
	<?php
		include('db.php');
		
		$db = new DB();
		if(!$db){
			echo $db->lastErrorMsg();
			exit();
		}
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Lägga till recept
			if ($_POST['addtype'] == 1) {
				$ingredients = $_POST['ingrdfield1'];
				$amounts = $_POST['ingrdfield2'];

				$instructions = str_replace(PHP_EOL, '<br>', $_POST['instructions']);
				
				$q = $db->prepare("INSERT INTO Recipes VALUES(:name,:image,:instructions,:description,:course,:views,:rating)");
				$q->bindValue(':name', $_POST['name'], SQLITE3_TEXT);
				$q->bindValue(':image', $_POST['pic'], SQLITE3_TEXT);
				$q->bindValue(':instructions', $instructions, SQLITE3_TEXT);
				$q->bindValue(':description', $_POST['description'], SQLITE3_TEXT);
				$q->bindValue(':course', mb_strtolower($_POST['course'], 'UTF-8'), SQLITE3_TEXT);
				$q->bindValue(':views', 0, SQLITE3_INTEGER);
				$q->bindValue(':rating', 0, SQLITE3_INTEGER);
				
				$ret = $q->execute();
				if(!$ret){
					echo $db->lastErrorMsg();
				}
		
				$id = $db->lastInsertRowID();
				
				// Gå genom alla ingredienser och lägg till koppling i RecipesIngredients.
				foreach ($ingredients as $key => $ingredient) {
					if ($ingredient !== '') {
						$q = $db->prepare("INSERT INTO RecipesIngredients VALUES(:id,:ingredient,:amount)");
						$q->bindValue(':id', $id, SQLITE3_INTEGER);
						$q->bindValue(':ingredient', trim(mb_strtolower($ingredient, 'UTF-8')), SQLITE3_TEXT);
						$q->bindValue(':amount', trim($amounts[$key]), SQLITE3_TEXT);
						$ret = $q->execute();
		
						if(!$ret){
							echo $db->lastErrorMsg();
						}

						// Lista de ingredienser som inte finns i Ingredients-tabellen.
						$q = $db->prepare("SELECT Count(*) FROM Ingredients WHERE UPPER(Ingredient) = UPPER(:ingredient)");
						$q->bindValue(':ingredient', trim($ingredient), SQLITE3_TEXT);
						$ret = $q->execute()->fetchArray()[0];
						if ($ret === 0) {
							$missing[] = $ingredient;
						}
					}
				}
				$message = sprintf('Lade till recept med id <a href=recipe.php?id=%d>%d</a>.', $id, $id);
				if (count($missing) > 0) {
					$message .= '<br>Ingredienser saknas i databasen: ' . implode(', ', $missing) . '.';
				}
			}
		
			// Lägga till ingredienser
			if ($_POST['addtype'] == 2) {
				$ingredients = $_POST['ingrdfield1'];
				$categories = $_POST['ingrdfield2'];

				foreach ($ingredients as $key => $ingredient) {
					if ($ingredient !== '') {
						$q = $db->prepare("INSERT OR IGNORE INTO Ingredients VALUES(:ingredient,:category)");
						$q->bindValue(':ingredient', trim(mb_strtolower($ingredient, 'UTF-8')), SQLITE3_TEXT);
						$q->bindValue(':category', $categories[$key], SQLITE3_TEXT);
						$ret = $q->execute();
		
						if(!$ret){
							echo $db->lastErrorMsg();
						}
					}
				}
				$message = 'Lade till ingrediens' . (count($ingredients) > 1 ? 'er' : '') . ': ';
				$message .= implode(', ', $ingredients) . '.';
			}
		
			// Nuka databasen och lägg till tables igen (om schemat ändras t ex)
			if ($_POST['addtype'] == 3) {
				$db->drop_tables();
				$db->init_tables();
				$message = 'Rensat databasen :(';
			}
		}
		
	?>
	<body>
		<div id="wrapper">
			<div id="main">
				<a href="index.html"><header>
						<h1>Receptsökare!</h1>
					</header></a>
				<?= $message ?>
				<div class="table">
					<div class="left">
						<h2>Lägg till recept</h2>
						<form method="post">
							<input type="text" name="name" id="name" placeholder="Namn" required><br>
							<input type="text" name="pic" id="pic" placeholder="Bild (filnamn)" required><br>
								<div id="ingrdbox">
									<input type="text" name="ingrdfield1[]" placeholder="Ingrediens" class="nyingrd"><input type="text" name="ingrdfield2[]" placeholder="Mängd" class="nyingrd" required>
								</div>
							<p><button type="button" onclick="addrow('ingrdbox','Ingrediens','Mängd')">+</button></p>


							<br>
							<textarea name="instructions" id="instructions" placeholder="Instruktioner" required></textarea><br>
							<input type="text" name="description" id="description" placeholder="Kort beskrivning" required><br>
							<select name="course">
								<option value="1">Förrätt</option>
								<option value="2">Huvudrätt</option>
								<option value="3">Efterrätt</option>
							</select>
							<input type="hidden" name="addtype" value="1">
							<input type="submit" value="Klar" style="width:100px;">
						</form>
					</div>
					<div class="right">
						<h2>Lägg till ingredienser</h2>
						<form method="post">
							<div id="nyingrdbox">
								<input type="text" name="ingrdfield1[]" placeholder="Ny ingrediens" class="nyingrd"><select name="ingrdfield2[]" class="nyingrd" required>
									<option value="Kött">Kött & Fisk</option>
									<option value="Mejeri">Mejeri & Ägg</option>
									<option value="Pasta">Mjöl, Gryn & Pasta</option>
									<option value="Grönsak">Frukt & Grönt</option>
									<option value="Övrigt">Övrigt</option>
								</select>
							</div>
							<p><button type="button" onclick="addrow('nyingrdbox','Ny ingrediens','Kategori')">+</button></p>
							<input type="hidden" name="addtype" value="2">
							<input type="submit" value="Klar" style="width:100px;">
						</form>
						
						<h2>Rensa databasen</h2>
						<form method="post">
							<input type="hidden" name="addtype" value="3">
							<input type="submit" value="Rensa" style="width:100px;">
						</form>
					</div>
				</div>
			</div>
			<footer>
				<p>Hannes Birgersson, Martin Gustavsson, Johan Stubbergaard, Maria Nguyen, Jenny Vuong</p>
			</footer>
		</div>
	</body>
</html>