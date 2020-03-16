//Projekt: Receptsökare
//Ansvarig: Hannes Birgersson
window.onload = setUp;
var ingrds = []; //Global array för att hantera valda ingredienser
var course;

function setUp() {
	//Lyssnare för att skapa händelser på tryck
	document.getElementById("knapp").onclick = skapalank;
	document.getElementById("rensa").onclick = rensa;
	document.getElementById("sokknapp").onclick = textSearch;
	if (document.body.addEventListener) { //För äldre versioner av IE
		document.body.addEventListener('click', addItem, false);
		document.body.addEventListener('click', removeItem, false);
	} else {
		document.body.attachEvent('onclick', addItem);
		document.body.attachEvent('onclick', removeItem);
	}
}

function skapalank() {
	//Tar alla rader i listan på valda ingredienser och gör en URL av dem
	if (ingrds.length <= 0) { //Ser till att inte listan är tom
		alert("Var god välj de ingredienser du vill använda");
		return false;
	}
	var courseID;
	switch (course) {
		case "förrätt":
			courseID = 1;
			break;
		case "huvudrätt":
			courseID = 2;
			break;
		case "efterrätt":
			courseID = 3;
			break;
		default:
			courseID = 0;
	}
	var ingrdsStr = ingrds.join(",");
	var destination = "search.php?c=" + courseID + "&s=" + ingrdsStr;
	window.location.href = destination;
}

function rensa() {
	//Tömmer både ingredienslistans innehåll, samt tar bort dem från att visas som valda på sidan.
	ingrds.length = 0;
	var div = document.getElementById('Ingredienslista');
	var x = document.getElementsByClassName("ItemMarked");
	var i;
	var l = x.length;
	for (i = 0; i < l; i++) {
		x[0].parentNode.innerHTML = x[0].parentNode.innerHTML.replace(
			"Item ItemMarked", "Item");
	}
	div.innerHTML = "";
	checkTutorial();
	courseselect(course);
}

function addItem(e) {
	//Lägger till en vald ingrediens i listan över valda ingredienser och visar upp dem som valda i sidans HTML. 
	//(Rader med "||" är för kompabilitetssyfte)
	e = e || window.event;
	var target;
	target = e.target || e.srcElement;
	if (target.className.match(/\bItem\b/)) {
		if (ingrds.indexOf(target.innerHTML) != -1) {
			//Kollar ifall ingrediensen redan är vald, och ifall den är vald så tas den bort istället
			target.className = "Item";
			var name = target.innerHTML;

			var index = ingrds.indexOf(name);
			ingrds.splice(index, 1);
			var x = document.getElementsByClassName("addedItem");
			console.log(x[0].parentNode.innerHTML);
			var i;
			for (i = 0; i < x.length; i++) {
				if (x[i].innerHTML.indexOf(name) != -1) {
					x[i].innerHTML = "";
					i = x.length;
				}
			}
			checkTutorial();
			return false;
		}
		if (ingrds.length >= 10) {
			//Ser till att det inte kan läggas till fler än 10 ingredienser
			alert("Du kan inte lägga till fler ingredienser");
			return false;
		}
		ingrds.push(target.innerHTML);
		/*Hitta det tryckta elementet och lägg till i lista och markeera*/
		var parent_Node = target.parentNode.innerHTML;
		parent_Node = parent_Node.replace("Item", "Item ItemMarked");
		target.parentNode.innerHTML = parent_Node;
		document.getElementById('Ingredienslista').innerHTML +=
			"<span class='addedItem'><li>" + target.innerHTML +
			'<span id="cross"><a href="javaScript:void(0);" class="RemoveCross">X</a></span></li></span>';
		checkTutorial();
		return true;
	}
	recreateMarkedItem();
}

function removeItem(e) {
	//Tar bort markerat objekt, och tar bort dem från att visas som valda. 
	e = e || window.event;
	var target;
	target = e.target || e.srcElement;
	if (target.className.match(/\bRemoveCross\b/)) {
		var fullRow = target.parentNode.parentNode.parentNode.innerHTML;
		var part = fullRow.substring(4, fullRow.lastIndexOf("<span id"));
		/*Hittar objektet som ska tas bort och tar bort det från ingrds och listan*/
		if (ingrds.indexOf(part) != -1) {
			var index = ingrds.indexOf(part);
			ingrds.splice(index, 1);
			target.parentNode.parentNode.parentNode.innerHTML = "";
			checkTutorial();
		} else {
			return false;
		}
		/*Hittar alla objekt som är markerade som valda, och tar bort stilklassen från den borttagna*/
		var x = document.getElementsByClassName("ItemMarked");
		var i;
		for (i = 0; i < x.length; i++) {
			if (x[i].parentNode.innerHTML.indexOf(part) != -1) {
				x[i].parentNode.innerHTML = x[i].parentNode.innerHTML.replace(
					"Item ItemMarked", "Item");
				i = x.length;
			}
		}
		return true;
	}
}

function courseselect(ratt) {
	/*Hitta gamla markering och ta bort dem*/
	var x = document.getElementsByClassName("kursmark");
	var l = x.length;

	for (var i = 0; i < x.length; i=i) {
		x[i].className = "kursbox";
	}
	
	if (ratt == course) {
		course = 0;
		var y = document.getElementsByClassName("kursbox");
		y[0].className = "kursbox kursmark";
		y[1].className = "kursbox kursmark";
		y[2].className = "kursbox kursmark";

	} else {
		course = ratt;
		var diver = document.getElementById(ratt);
		var newdiv = diver.parentNode.innerHTML.replace("kursbox", "kursbox kursmark");
		document.getElementById(ratt).parentNode.innerHTML = newdiv;
		recreatePointers();
	}
}


function recreatePointers() {
	/*Återskapar onclick-pekarna på maträttsvalen*/
	document.getElementById("förrätt").onclick = forratt;
	document.getElementById("huvudrätt").onclick = huvudratt;
	document.getElementById("efterrätt").onclick = efterratt;
}

function checkTutorial() {
	/*Döljer tutorialen ifall några ingredienser är valda, och visar dem ifall inga är.*/
	var tut = document.getElementById("Tutorial");
	tutText = tut.innerHTML;
	if (ingrds.length == 0) {
		document.getElementById("Tutorial").style.display = "block";
		return true;
	} else {
		document.getElementById("Tutorial").style.display = "none";
		return false;
	}
}

function textSearch() {
	/*Skapar länken till php sidan för fritextssökning*/
	var searchVal = document.getElementById('sokruta').value;
	if (searchVal != "") {
		var destination = "search.php?r=" + searchVal;
		window.location.href = destination;
	}
}

function showIngrds(str, sort) {
	/*AJAX för att hämta ingredienser med motsvarande kategori (str). 
	 *str=get ger nuvarande vald kategori. sort=0 ger alla ingredienser*/


	if (str == "Default") {
		/*Tar tillbaka den sammanfattade ingredienslistan, och döljer den dynamiska*/
		document.getElementById("defaultingrds").style.display = "block";
		document.getElementById("refreshingrds").style.display = "none";
		document.getElementById("pagenav").style.visibility = "hidden";
	} else {
		/*Döljer den sammanfattade ingredienslistan, och tar fram den dynamiska*/
		document.getElementById("defaultingrds").style.display = "none";
		document.getElementById("refreshingrds").style.display = "block";
		document.getElementById("pagenav").style.visibility = "visible";
		if (str!='get') {
			document.getElementById("CurrentCat").innerHTML = str;
		} else {
			str = document.getElementById("CurrentCat").innerHTML;
		}
		
		if (window.XMLHttpRequest) {
            //IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            //IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange = function () {
        	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        		document.getElementById("refreshingrds").innerHTML = xmlhttp.responseText;

        		recreateMarkedItem();
        	}
        }
		xmlhttp.open("GET", "getIngrds.php?q=" + str + "&s=" + sort, true);
		xmlhttp.send();
	}
}

function markCat(e) {
	/*Markerar den kategorirutan som klickas*/
    var x = document.getElementsByClassName("catmark");
    if (e.target.parentNode.innerHTML.indexOf("kategoricenter") < 0) {
		x[0].className = "kategoribox";
    	e.target.className = "kategoribox catmark";
    }
}

function recreateMarkedItem() {
	/*När ingredienssidan updaterar de dynamiska ingredienserna försvinner markeringarna
	 *för valda ingredienser. Denna funktion återskapar dem*/
	var x = document.getElementsByClassName("Item");
	var i;
	var re = 1;

	var l = x.length;
	for (i = 0; i < l; i++) {
		if (ingrds.indexOf(x[i].innerHTML) != -1) {

			x[i].className = "Item ItemMarked";
		}
	}
}
