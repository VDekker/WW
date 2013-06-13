<?php

//hierin komen de easter-eggs van het programma

//easter-eggs met rolverdelingen:
//bepaalde namen hebben altijd bepaalde rollen
//(mits deze in de rolverdeling zitten)
function easterRol($sid) {
	//Mark is altijd Slet
	verwisselRol('Mark','Slet',$sid);

	//Terrance is altijd Weerwolf
	verwisselRol('Terrance','Weerwolf',$sid);

	//Maya is altijd Welp
	verwisselRol('Maya','Welp',$sid);

	//June is altijd Weerwolf
	verwisselRol('June','Weerwolf',$sid);

	//Roy is altijd Goochelaar
	verwisselRol('Roy','Goochelaar',$sid);

	//Emma is altijd Jager
	verwisselRol('Emma','Jager',$sid);

	//Bas is altijd Zondebok
	verwisselRol('Bas','Zondebok',$sid);

	return;
}//easterRol

?>
