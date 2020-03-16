<?php 
class DB extends SQLite3
{
	// Konstruktorn öppnar anslutningen till databasen.
	function __construct()
	{
		$this->open('test2.db');
	}

	// Töm databasen.
	function drop_tables()
	{
		$sql = <<<SQL
			DROP TABLE Recipes;
			DROP TABLE Ingredients;
			DROP TABLE RecipesIngredients;
SQL;
		$ret = $this->exec($sql);
		if(!$ret){
			echo $this->lastErrorMsg();
		} else {
			echo "<br>" . "Dropped tables.";
		}
	}

	// Bygg tabellerna
	function init_tables()
	{
		$sql = <<<SQL
			CREATE TABLE Recipes
			(Name          TEXT NOT NULL,
			Picture        TEXT NOT NULL,
			Instructions   TEXT NOT NULL,
			Description    TEXT,
			Course 		   TEXT,
			Views		   INT,
			Rating         INT);
			
			CREATE TABLE Ingredients
			(Ingredient TEXT NOT NULL UNIQUE,
			Category    TEXT);

			CREATE TABLE RecipesIngredients
			(RecipeID  INT NOT NULL,
			Ingredient TEXT NOT NULL,
			Amount     TEXT NOT NULL);

			CREATE TABLE RecipesVotes
			(RecipeID INT NOT NULL,
			IP        TEXT NOT NULL);
SQL;
		$ret = $this->exec($sql);
		if(!$ret){
			echo $this->lastErrorMsg();
		} else {
			echo "<br>" . "Created tables.";
		}
	}
}
?>